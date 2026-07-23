<script setup lang="ts">
import { reactive, computed, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'
import MecanicoFichaSlideover, { type MecanicoFicha } from '../../components/MecanicoFichaSlideover.vue'

interface Option {
  id: string
  label: string
  precioBase?: number
  precio?: number
  stock?: number
  clienteId?: string
}

interface MecanicoOption extends MecanicoFicha {
  label: string
}

const page = usePage()
const clientes = computed(() => ((page.props as any).clientes || []) as Option[])
const vehiculos = computed(() => ((page.props as any).vehiculos || []) as Option[])
const mecanicos = computed(() => ((page.props as any).mecanicos || []) as MecanicoOption[])
const servicios = computed(() => ((page.props as any).servicios || []) as Option[])
const repuestos = computed(() => ((page.props as any).repuestos || []) as Option[])

const backendErrors = computed(() => page.props.errors || {})
const errors = computed(() => {
  const result: Record<string, string> = {}
  Object.keys(backendErrors.value).forEach((key) => {
    const error = (backendErrors.value as any)[key]
    result[key] = Array.isArray(error) ? error[0] : error
  })
  return result
})

const prioridadItems = [
  { label: 'Baja', value: 'baja' },
  // ZWNJ evita que Chrome traduzca "Media" → "Medios de comunicación"
  { label: 'Media\u200C', value: 'media' },
  { label: 'Alta', value: 'alta' }
]

const incluirServicio = ref(false)
const incluirRepuesto = ref(false)
const servicioSeleccionado = ref('')
const repuestoSeleccionado = ref('')
const servicioPrecio = ref(0)
const repuestoCantidad = ref(1)
const repuestoPrecio = ref(0)

const isLoading = ref(false)
const state = reactive({
  clienteId: '',
  vehiculoId: '',
  mecanicoId: '',
  tipoFalla: '',
  fallaReportada: '',
  kilometrajeIngreso: 0,
  observaciones: '',
  prioridad: 'media'
})

const fichaOpen = ref(false)
const mecanicoSeleccionado = computed(() =>
  mecanicos.value.find(m => m.id === state.mecanicoId) ?? null
)

watch(() => state.mecanicoId, (id) => {
  if (id) fichaOpen.value = true
})

const vehiculosFiltrados = computed(() => {
  if (!state.clienteId) return vehiculos.value
  return vehiculos.value.filter(v => v.clienteId === state.clienteId)
})

watch(() => state.clienteId, () => {
  if (state.vehiculoId && !vehiculosFiltrados.value.some(v => v.id === state.vehiculoId)) {
    state.vehiculoId = ''
  }
})

watch(servicioSeleccionado, (id) => {
  const servicio = servicios.value.find(s => s.id === id)
  servicioPrecio.value = servicio?.precioBase ?? 0
})

watch(repuestoSeleccionado, (id) => {
  const repuesto = repuestos.value.find(r => r.id === id)
  repuestoPrecio.value = repuesto?.precio ?? 0
})

const handleSubmit = () => {
  isLoading.value = true
  const payload: Record<string, unknown> = { ...state }

  if (incluirServicio.value && servicioSeleccionado.value) {
    payload.servicios = [{ servicioId: servicioSeleccionado.value, precio: servicioPrecio.value }]
  }

  if (incluirRepuesto.value && repuestoSeleccionado.value) {
    payload.repuestos = [{
      productoId: repuestoSeleccionado.value,
      cantidad: repuestoCantidad.value,
      precioUnitario: repuestoPrecio.value
    }]
  }

  if (!payload.mecanicoId) delete payload.mecanicoId

  router.post(route('ordenes.store'), payload, {
    onFinish: () => { isLoading.value = false }
  })
}
</script>

<template>
  <AppDashboardPanel id="orden-create">
    <template #header>
      <UDashboardNavbar title="Nueva orden de trabajo">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <UCard class="max-w-4xl">
        <form class="grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="handleSubmit">
          <FormField label="Cliente" name="clienteId" required :error="errors.clienteId" class="md:col-span-2">
            <USelect
              v-model="state.clienteId"
              :items="clientes.map(c => ({ label: c.label, value: c.id }))"
              placeholder="Seleccionar cliente"
              class="w-full"
            />
          </FormField>
          <FormField label="Vehículo" name="vehiculoId" required :error="errors.vehiculoId" class="md:col-span-2">
            <USelect
              v-model="state.vehiculoId"
              :items="vehiculosFiltrados.map(v => ({ label: v.label, value: v.id }))"
              placeholder="Seleccionar vehículo"
              class="w-full"
            />
          </FormField>
          <FormField label="Mecánico" name="mecanicoId" :error="errors.mecanicoId" class="md:col-span-2">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start">
              <USelect
                v-model="state.mecanicoId"
                :items="mecanicos.map(m => ({ label: m.label, value: m.id }))"
                placeholder="Opcional"
                class="w-full"
              />
              <UButton
                type="button"
                variant="soft"
                icon="i-lucide-id-card"
                label="Ver ficha"
                :disabled="!state.mecanicoId"
                @click="fichaOpen = true"
              />
            </div>
            <p v-if="mecanicoSeleccionado" class="mt-2 text-sm text-muted">
              Especialidad: <span class="font-medium text-highlighted">{{ mecanicoSeleccionado.especialidad }}</span>
            </p>
          </FormField>
          <FormField label="Tipo de falla" name="tipoFalla" :error="errors.tipoFalla">
            <UInput v-model="state.tipoFalla" class="w-full" />
          </FormField>
          <FormField label="Prioridad" name="prioridad" :error="errors.prioridad">
            <div translate="no">
              <USelect
                v-model="state.prioridad"
                :items="prioridadItems"
                value-key="value"
                label-key="label"
                class="w-full"
              >
                <template #default="{ modelValue }">
                  <span translate="no">{{ prioridadItems.find(i => i.value === modelValue)?.label || modelValue }}</span>
                </template>
                <template #item-label="{ item }">
                  <span translate="no">{{ item.label }}</span>
                </template>
              </USelect>
            </div>
          </FormField>
          <FormField label="Falla reportada" name="fallaReportada" required :error="errors.fallaReportada" class="md:col-span-2">
            <UTextarea v-model="state.fallaReportada" class="w-full" :rows="3" />
          </FormField>
          <FormField label="Kilometraje ingreso" name="kilometrajeIngreso" :error="errors.kilometrajeIngreso">
            <UInput v-model.number="state.kilometrajeIngreso" type="number" min="0" class="w-full" />
          </FormField>
          <FormField label="Observaciones" name="observaciones" :error="errors.observaciones">
            <UTextarea v-model="state.observaciones" class="w-full" />
          </FormField>

          <div class="md:col-span-2 border-t border-default pt-4 space-y-4">
            <UCheckbox v-model="incluirServicio" label="Agregar servicio (opcional)" />
            <div v-if="incluirServicio" class="grid grid-cols-1 md:grid-cols-2 gap-4 pl-6">
              <FormField label="Servicio" name="servicioId" :error="errors['servicios.0.servicioId']">
                <USelect
                  v-model="servicioSeleccionado"
                  :items="servicios.map(s => ({ label: s.label, value: s.id }))"
                  placeholder="Seleccionar servicio"
                  class="w-full"
                />
              </FormField>
              <FormField label="Precio" name="servicioPrecio">
                <UInput v-model.number="servicioPrecio" type="number" min="0" step="0.01" class="w-full" />
              </FormField>
            </div>
          </div>

          <div class="md:col-span-2 border-t border-default pt-4 space-y-4">
            <UCheckbox v-model="incluirRepuesto" label="Agregar repuesto (opcional)" />
            <div v-if="incluirRepuesto" class="grid grid-cols-1 md:grid-cols-3 gap-4 pl-6">
              <FormField label="Repuesto" name="productoId" :error="errors['repuestos.0.productoId']">
                <USelect
                  v-model="repuestoSeleccionado"
                  :items="repuestos.map(r => ({ label: r.label, value: r.id }))"
                  placeholder="Seleccionar repuesto"
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

          <div class="md:col-span-2 flex gap-3">
            <UButton type="submit" label="Guardar" :loading="isLoading" />
            <UButton variant="ghost" color="neutral" label="Cancelar" :to="route('ordenes.index')" />
          </div>
        </form>
      </UCard>

      <MecanicoFichaSlideover v-model:open="fichaOpen" :mecanico="mecanicoSeleccionado" />
    </template>
  </AppDashboardPanel>
</template>
