<script setup lang="ts">
import { reactive, computed, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'

interface Option {
  id: string
  label: string
  clienteId?: string
}

interface VehiculoOption extends Option {
  placa?: string
  marca?: string
  modelo?: string
  anio?: number | null
  color?: string | null
  kilometraje?: number
  tipoCombustible?: string | null
}

const page = usePage()
const clientes = computed(() => ((page.props as any).clientes || []) as Option[])
const vehiculos = computed(() => ((page.props as any).vehiculos || []) as VehiculoOption[])

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
  { label: 'Media\u200C', value: 'media' },
  { label: 'Alta', value: 'alta' }
]

const tipoFallaItems = [
  { label: 'Frenos', value: 'Frenos' },
  { label: 'Motor', value: 'Motor' },
  { label: 'Suspensión', value: 'Suspensión' },
  { label: 'Eléctrico', value: 'Eléctrico' },
  { label: 'Transmisión', value: 'Transmisión' },
  { label: 'Aire acondicionado', value: 'Aire acondicionado' },
  { label: 'Inyección', value: 'Inyección' },
  { label: 'Otro', value: 'Otro' }
]

const isLoading = ref(false)
const state = reactive({
  clienteId: '',
  vehiculoId: '',
  tipoFalla: '',
  fallaReportada: '',
  kilometrajeIngreso: 0,
  observaciones: '',
  prioridad: 'media',
  fechaCita: '',
  tipoCita: 'reparacion'
})

const tipoCitaItems = [
  { label: 'Reparación', value: 'reparacion' },
  { label: 'Diagnóstico', value: 'diagnostico' },
  { label: 'Mantenimiento', value: 'mantenimiento' }
]

const vehiculosFiltrados = computed(() => {
  if (!state.clienteId) return vehiculos.value
  return vehiculos.value.filter(v => v.clienteId === state.clienteId)
})

const vehiculoSeleccionado = computed(() =>
  vehiculos.value.find(v => v.id === state.vehiculoId) ?? null
)

watch(() => state.clienteId, () => {
  if (state.vehiculoId && !vehiculosFiltrados.value.some(v => v.id === state.vehiculoId)) {
    state.vehiculoId = ''
    state.kilometrajeIngreso = 0
  }
})

watch(() => state.vehiculoId, (id) => {
  if (!id) {
    state.kilometrajeIngreso = 0
    return
  }

  const vehiculo = vehiculos.value.find(v => v.id === id)
  if (!vehiculo) return

  state.kilometrajeIngreso = vehiculo.kilometraje ?? 0

  if (vehiculo.clienteId && state.clienteId !== vehiculo.clienteId) {
    state.clienteId = vehiculo.clienteId
  }
})

const handleSubmit = () => {
  isLoading.value = true
  router.post(route('ordenes.store'), { ...state }, {
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
      <UCard class="w-full">
        <UAlert
          class="mb-4"
          color="info"
          variant="subtle"
          icon="i-lucide-brain"
          title="Flujo recomendado"
          description="Guarda la orden con la falla reportada. En el siguiente paso la IA generará el diagnóstico, asignará especialista, servicios y repuestos para que el mecánico los revise."
        />

        <form class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 w-full" @submit.prevent="handleSubmit">
          <FormField label="Cliente" name="clienteId" required :error="errors.clienteId" class="md:col-span-2 xl:col-span-3">
            <USelect
              v-model="state.clienteId"
              :items="clientes.map(c => ({ label: c.label, value: c.id }))"
              placeholder="Seleccionar cliente"
              class="w-full"
            />
          </FormField>
          <FormField label="Vehículo" name="vehiculoId" required :error="errors.vehiculoId" class="md:col-span-2 xl:col-span-3">
            <USelect
              v-model="state.vehiculoId"
              :items="vehiculosFiltrados.map(v => ({ label: v.label, value: v.id }))"
              placeholder="Seleccionar vehículo"
              class="w-full"
            />
            <p v-if="vehiculoSeleccionado" class="mt-2 text-sm text-muted">
              {{ vehiculoSeleccionado.marca }} {{ vehiculoSeleccionado.modelo }}
              <span v-if="vehiculoSeleccionado.anio"> ({{ vehiculoSeleccionado.anio }})</span>
              <span v-if="vehiculoSeleccionado.color"> · {{ vehiculoSeleccionado.color }}</span>
              · Km:
              <span class="font-medium text-highlighted">
                {{ Number(vehiculoSeleccionado.kilometraje ?? 0).toLocaleString() }}
              </span>
            </p>
          </FormField>
          <FormField label="Tipo de falla" name="tipoFalla" :error="errors.tipoFalla">
            <USelect
              v-model="state.tipoFalla"
              :items="tipoFallaItems"
              placeholder="Seleccionar tipo"
              class="w-full"
            />
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
          <FormField label="Falla reportada" name="fallaReportada" required :error="errors.fallaReportada" class="md:col-span-2 xl:col-span-3">
            <UTextarea
              v-model="state.fallaReportada"
              class="w-full"
              :rows="3"
              placeholder="Ej. Al querer encender el vehículo no enciende"
            />
          </FormField>
          <FormField label="Kilometraje ingreso" name="kilometrajeIngreso" :error="errors.kilometrajeIngreso">
            <UInput v-model.number="state.kilometrajeIngreso" type="number" min="0" class="w-full" />
          </FormField>
          <FormField label="Observaciones" name="observaciones" :error="errors.observaciones">
            <UTextarea v-model="state.observaciones" class="w-full" />
          </FormField>
          <FormField label="Cita en calendario (opcional)" name="fechaCita" :error="errors.fecha_cita || errors.fechaCita">
            <UInput v-model="state.fechaCita" type="datetime-local" class="w-full" />
            <p class="text-xs text-muted mt-1">Si la defines, el cliente y el mecánico la verán en Calendario.</p>
          </FormField>
          <FormField label="Tipo de cita" name="tipoCita" :error="errors.tipo_cita || errors.tipoCita">
            <USelect
              v-model="state.tipoCita"
              :items="tipoCitaItems"
              class="w-full"
            />
          </FormField>

          <div class="md:col-span-2 xl:col-span-3 flex gap-3">
            <UButton type="submit" label="Guardar y diagnosticar con IA" icon="i-lucide-brain" :loading="isLoading" />
            <UButton variant="ghost" color="neutral" label="Cancelar" :to="route('ordenes.index')" />
          </div>
        </form>
      </UCard>
    </template>
  </AppDashboardPanel>
</template>
