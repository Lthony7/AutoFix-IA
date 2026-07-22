<script setup lang="ts">
import { reactive, computed, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'

interface Option {
  id: string
  label: string
  precioBase?: number
  precio?: number
  clienteId?: string
}

interface OrdenServicio {
  servicioId: string
  precio: number
}

interface OrdenRepuesto {
  productoId: string
  cantidad: number
  precioUnitario: number
}

const page = usePage()
const orden = (page.props as any).orden
const soloDiagnostico = !!(page.props as any).soloDiagnostico
const clientes = computed(() => ((page.props as any).clientes || []) as Option[])
const vehiculos = computed(() => ((page.props as any).vehiculos || []) as Option[])
const mecanicos = computed(() => ((page.props as any).mecanicos || []) as Option[])
const serviciosOpts = computed(() => ((page.props as any).servicios || []) as Option[])
const repuestosOpts = computed(() => ((page.props as any).repuestos || []) as Option[])

const backendErrors = computed(() => page.props.errors || {})
const errors = computed(() => {
  const result: Record<string, string> = {}
  Object.keys(backendErrors.value).forEach((key) => {
    const error = (backendErrors.value as any)[key]
    result[key] = Array.isArray(error) ? error[0] : error
  })
  return result
})

const estadoItems = [
  { label: 'Pendiente', value: 'pendiente' },
  { label: 'En diagnóstico', value: 'en_diagnostico' },
  { label: 'En reparación', value: 'en_reparacion' },
  { label: 'Finalizada', value: 'finalizada' },
  { label: 'Entregada', value: 'entregada' },
  { label: 'Cancelada', value: 'cancelada' }
]

const prioridadItems = [
  { label: 'Baja', value: 'baja' },
  { label: 'Media', value: 'media' },
  { label: 'Alta', value: 'alta' }
]

const incluirServicio = ref((orden.servicios?.length ?? 0) > 0)
const incluirRepuesto = ref((orden.repuestos?.length ?? 0) > 0)
const servicioSeleccionado = ref(orden.servicios?.[0]?.servicioId || '')
const repuestoSeleccionado = ref(orden.repuestos?.[0]?.productoId || '')
const servicioPrecio = ref(orden.servicios?.[0]?.precio ?? 0)
const repuestoCantidad = ref(orden.repuestos?.[0]?.cantidad ?? 1)
const repuestoPrecio = ref(orden.repuestos?.[0]?.precioUnitario ?? 0)
const nuevoEstado = ref(orden.estado)
const cambiandoEstado = ref(false)

const isLoading = ref(false)
const state = reactive({
  clienteId: orden.clienteId,
  vehiculoId: orden.vehiculoId,
  mecanicoId: orden.mecanicoId || '',
  tipoFalla: orden.tipoFalla || '',
  fallaReportada: orden.fallaReportada || '',
  kilometrajeIngreso: orden.kilometrajeIngreso ?? 0,
  observaciones: orden.observaciones || '',
  diagnosticoTecnico: orden.diagnosticoTecnico || '',
  prioridad: orden.prioridad || 'media'
})

const vehiculosFiltrados = computed(() => {
  if (!state.clienteId) return vehiculos.value
  return vehiculos.value.filter(v => v.clienteId === state.clienteId)
})

watch(servicioSeleccionado, (id) => {
  const servicio = serviciosOpts.value.find(s => s.id === id)
  if (servicio && !orden.servicios?.some((s: OrdenServicio) => s.servicioId === id)) {
    servicioPrecio.value = servicio.precioBase ?? 0
  }
})

watch(repuestoSeleccionado, (id) => {
  const repuesto = repuestosOpts.value.find(r => r.id === id)
  if (repuesto && !orden.repuestos?.some((r: OrdenRepuesto) => r.productoId === id)) {
    repuestoPrecio.value = repuesto.precio ?? 0
  }
})

const handleSubmit = () => {
  isLoading.value = true
  const payload: Record<string, unknown> = soloDiagnostico
    ? { diagnosticoTecnico: state.diagnosticoTecnico, observaciones: state.observaciones }
    : { ...state }

  if (!soloDiagnostico) {
    if (incluirServicio.value && servicioSeleccionado.value) {
      payload.servicios = [{ servicioId: servicioSeleccionado.value, precio: servicioPrecio.value }]
    } else {
      payload.servicios = []
    }

    if (incluirRepuesto.value && repuestoSeleccionado.value) {
      payload.repuestos = [{
        productoId: repuestoSeleccionado.value,
        cantidad: repuestoCantidad.value,
        precioUnitario: repuestoPrecio.value
      }]
    } else {
      payload.repuestos = []
    }

    if (!payload.mecanicoId) payload.mecanicoId = null
  }

  router.put(route('ordenes.update', orden.id), payload, {
    onFinish: () => { isLoading.value = false }
  })
}

const cambiarEstado = () => {
  if (nuevoEstado.value === orden.estado) return
  cambiandoEstado.value = true
  router.put(route('ordenes.cambiar-estado', orden.id), { estado: nuevoEstado.value }, {
    onFinish: () => { cambiandoEstado.value = false }
  })
}
</script>

<template>
  <UDashboardPanel id="orden-edit">
    <template #header>
      <UDashboardNavbar :title="`Editar orden ${orden.numero}`">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton
            variant="ghost"
            icon="i-lucide-brain"
            label="Diagnóstico IA"
            :to="route('diagnosticos-ia.create', { ordenTrabajoId: orden.id })"
          />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <UCard class="max-w-4xl">
        <form class="grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="handleSubmit">
          <template v-if="soloDiagnostico">
            <FormField label="Diagnóstico técnico" name="diagnosticoTecnico" :error="errors.diagnosticoTecnico" class="md:col-span-2">
              <UTextarea v-model="state.diagnosticoTecnico" class="w-full" :rows="5" />
            </FormField>
            <FormField label="Observaciones" name="observaciones" :error="errors.observaciones" class="md:col-span-2">
              <UTextarea v-model="state.observaciones" class="w-full" />
            </FormField>
          </template>

          <template v-else>
            <FormField label="Cliente" name="clienteId" required :error="errors.clienteId" class="md:col-span-2">
              <USelect
                v-model="state.clienteId"
                :items="clientes.map(c => ({ label: c.label, value: c.id }))"
                class="w-full"
              />
            </FormField>
            <FormField label="Vehículo" name="vehiculoId" required :error="errors.vehiculoId" class="md:col-span-2">
              <USelect
                v-model="state.vehiculoId"
                :items="vehiculosFiltrados.map(v => ({ label: v.label, value: v.id }))"
                class="w-full"
              />
            </FormField>
            <FormField label="Mecánico" name="mecanicoId" :error="errors.mecanicoId" class="md:col-span-2">
              <USelect
                v-model="state.mecanicoId"
                :items="mecanicos.map(m => ({ label: m.label, value: m.id }))"
                placeholder="Opcional"
                class="w-full"
              />
            </FormField>
            <FormField label="Tipo de falla" name="tipoFalla" :error="errors.tipoFalla">
              <UInput v-model="state.tipoFalla" class="w-full" />
            </FormField>
            <FormField label="Prioridad" name="prioridad" :error="errors.prioridad">
              <USelect v-model="state.prioridad" :items="prioridadItems" class="w-full" />
            </FormField>
            <FormField label="Falla reportada" name="fallaReportada" :error="errors.fallaReportada" class="md:col-span-2">
              <UTextarea v-model="state.fallaReportada" class="w-full" :rows="3" />
            </FormField>
            <FormField label="Kilometraje ingreso" name="kilometrajeIngreso" :error="errors.kilometrajeIngreso">
              <UInput v-model.number="state.kilometrajeIngreso" type="number" min="0" class="w-full" />
            </FormField>
            <FormField label="Diagnóstico técnico" name="diagnosticoTecnico" :error="errors.diagnosticoTecnico">
              <UTextarea v-model="state.diagnosticoTecnico" class="w-full" />
            </FormField>
            <FormField label="Observaciones" name="observaciones" :error="errors.observaciones" class="md:col-span-2">
              <UTextarea v-model="state.observaciones" class="w-full" />
            </FormField>

            <div class="md:col-span-2 border-t border-default pt-4 space-y-4">
              <UCheckbox v-model="incluirServicio" label="Incluir servicio" />
              <div v-if="incluirServicio" class="grid grid-cols-1 md:grid-cols-2 gap-4 pl-6">
                <FormField label="Servicio" name="servicioId">
                  <USelect
                    v-model="servicioSeleccionado"
                    :items="serviciosOpts.map(s => ({ label: s.label, value: s.id }))"
                    class="w-full"
                  />
                </FormField>
                <FormField label="Precio" name="servicioPrecio">
                  <UInput v-model.number="servicioPrecio" type="number" min="0" step="0.01" class="w-full" />
                </FormField>
              </div>
            </div>

            <div class="md:col-span-2 border-t border-default pt-4 space-y-4">
              <UCheckbox v-model="incluirRepuesto" label="Incluir repuesto" />
              <div v-if="incluirRepuesto" class="grid grid-cols-1 md:grid-cols-3 gap-4 pl-6">
                <FormField label="Repuesto" name="productoId">
                  <USelect
                    v-model="repuestoSeleccionado"
                    :items="repuestosOpts.map(r => ({ label: r.label, value: r.id }))"
                    class="w-full"
                  />
                </FormField>
                <FormField label="Cantidad" name="cantidad">
                  <UInput v-model.number="repuestoCantidad" type="number" min="1" class="w-full" />
                </FormField>
                <FormField label="Precio unitario" name="precioUnitario">
                  <UInput v-model.number="repuestoPrecio" type="number" min="0" step="0.01" class="w-full" />
                </FormField>
              </div>
            </div>

            <div class="md:col-span-2 border-t border-default pt-4">
              <div class="flex flex-wrap items-end gap-3">
                <FormField label="Cambiar estado" name="estado" class="flex-1 min-w-[200px]">
                  <USelect v-model="nuevoEstado" :items="estadoItems" class="w-full" />
                </FormField>
                <UButton
                  type="button"
                  variant="outline"
                  label="Aplicar estado"
                  icon="i-lucide-refresh-cw"
                  :loading="cambiandoEstado"
                  :disabled="nuevoEstado === orden.estado"
                  @click="cambiarEstado"
                />
              </div>
            </div>
          </template>

          <div class="md:col-span-2 flex gap-3">
            <UButton type="submit" label="Actualizar" :loading="isLoading" />
            <UButton variant="ghost" color="neutral" label="Cancelar" :to="route('ordenes.index')" />
          </div>
        </form>
      </UCard>
    </template>
  </UDashboardPanel>
</template>
