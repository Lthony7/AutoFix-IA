<script setup lang="ts">
import { reactive, computed, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'

interface OrdenOption {
  id: string
  label: string
  numero: string
  clienteNombre?: string | null
  vehiculoPlaca?: string | null
  vehiculoMarca?: string | null
  vehiculoModelo?: string | null
  vehiculoAnio?: number | null
  vehiculoColor?: string | null
  vehiculoCombustible?: string | null
  kilometrajeIngreso?: number | null
  kilometrajeVehiculo?: number | null
  tipoFalla?: string | null
  fallaReportada?: string | null
  prioridad?: string | null
  estadoLabel?: string | null
}

const page = usePage()
const ordenes = computed(() => ((page.props as any).ordenes || []) as OrdenOption[])
const ordenPrefill = computed(() => (page.props as any).orden as OrdenOption | null)

const backendErrors = computed(() => page.props.errors || {})
const errors = computed(() => {
  const result: Record<string, string> = {}
  Object.keys(backendErrors.value).forEach((key) => {
    const error = (backendErrors.value as any)[key]
    result[key] = Array.isArray(error) ? error[0] : error
  })
  return result
})

const urgenciaItems = [
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
  ordenTrabajoId: ordenPrefill.value?.id || '',
  tipoFalla: ordenPrefill.value?.tipoFalla || '',
  descripcion: ordenPrefill.value?.fallaReportada || '',
  momento: '',
  lucesTablero: '',
  ruidos: '',
  puedeCircular: true,
  urgencia: (ordenPrefill.value?.prioridad as string) || 'media',
  observaciones: ''
})

const ordenSeleccionada = computed(() =>
  ordenes.value.find(o => o.id === state.ordenTrabajoId)
  || (ordenPrefill.value?.id === state.ordenTrabajoId ? ordenPrefill.value : null)
)

const kilometrajeMostrar = computed(() => {
  const orden = ordenSeleccionada.value
  if (!orden) return null
  return orden.kilometrajeIngreso ?? orden.kilometrajeVehiculo ?? null
})

const aplicarDatosOrden = (orden: OrdenOption | null | undefined) => {
  if (!orden) return
  if (orden.tipoFalla) state.tipoFalla = orden.tipoFalla
  if (orden.fallaReportada) state.descripcion = orden.fallaReportada
  if (orden.prioridad && ['baja', 'media', 'alta'].includes(orden.prioridad)) {
    state.urgencia = orden.prioridad
  }
}

watch(ordenPrefill, (orden) => {
  if (orden) {
    state.ordenTrabajoId = orden.id
    aplicarDatosOrden(orden)
  }
}, { immediate: true })

watch(() => state.ordenTrabajoId, (id) => {
  if (!id) return
  const orden = ordenes.value.find(o => o.id === id)
  aplicarDatosOrden(orden)
})

const handleSubmit = () => {
  if (!state.ordenTrabajoId) return
  isLoading.value = true
  router.post(route('diagnosticos-ia.store'), state, {
    onFinish: () => { isLoading.value = false }
  })
}
</script>

<template>
  <AppDashboardPanel id="diagnostico-ia-create">
    <template #header>
      <UDashboardNavbar title="Nuevo diagnóstico IA">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <div class="max-w-4xl space-y-4">
        <UAlert
          color="warning"
          variant="subtle"
          icon="i-lucide-triangle-alert"
          title="Aviso importante"
          description="La información generada por Inteligencia Artificial es únicamente una sugerencia inicial. El diagnóstico final debe ser realizado y confirmado por un mecánico autorizado."
        />

        <UAlert
          color="info"
          variant="subtle"
          icon="i-lucide-link"
          title="Siempre enlazado a una orden de trabajo"
          description="El diagnóstico IA se genera sobre una OT existente: usa el cliente, vehículo y kilometraje de esa orden. Si no hay órdenes pendientes, crea una primero."
        />

        <UCard v-if="!ordenes.length">
          <div class="space-y-3 text-center py-4">
            <p class="text-sm text-muted">
              No hay órdenes pendientes de diagnóstico IA.
            </p>
            <UButton
              icon="i-lucide-clipboard-plus"
              label="Crear orden de trabajo"
              :to="route('ordenes.create')"
            />
          </div>
        </UCard>

        <UCard v-else>
          <form class="grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="handleSubmit">
            <FormField label="Orden de trabajo" name="ordenTrabajoId" required :error="errors.ordenTrabajoId" class="md:col-span-2">
              <USelect
                v-model="state.ordenTrabajoId"
                :items="ordenes.map(o => ({ label: o.label, value: o.id }))"
                placeholder="Seleccionar orden"
                class="w-full"
              />
            </FormField>

            <div
              v-if="ordenSeleccionada"
              class="md:col-span-2 rounded-lg border border-default/70 bg-elevated/40 p-4 space-y-3"
            >
              <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="font-semibold">Datos de la orden {{ ordenSeleccionada.numero }}</p>
                <UBadge v-if="ordenSeleccionada.estadoLabel" color="neutral" variant="subtle">
                  {{ ordenSeleccionada.estadoLabel }}
                </UBadge>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div>
                  <p class="text-xs uppercase tracking-wide text-muted">Cliente</p>
                  <p class="mt-1">{{ ordenSeleccionada.clienteNombre || '—' }}</p>
                </div>
                <div>
                  <p class="text-xs uppercase tracking-wide text-muted">Vehículo</p>
                  <p class="mt-1">
                    {{ ordenSeleccionada.vehiculoPlaca || '—' }}
                    <span v-if="ordenSeleccionada.vehiculoMarca">
                      — {{ ordenSeleccionada.vehiculoMarca }} {{ ordenSeleccionada.vehiculoModelo }}
                      <span v-if="ordenSeleccionada.vehiculoAnio">({{ ordenSeleccionada.vehiculoAnio }})</span>
                    </span>
                  </p>
                </div>
                <div>
                  <p class="text-xs uppercase tracking-wide text-muted">Kilometraje</p>
                  <p class="mt-1 font-medium">
                    {{ kilometrajeMostrar != null ? `${Number(kilometrajeMostrar).toLocaleString()} km` : 'No registrado en la OT' }}
                  </p>
                  <p v-if="ordenSeleccionada.kilometrajeIngreso == null && ordenSeleccionada.kilometrajeVehiculo != null" class="text-xs text-muted">
                    Tomado del registro del vehículo
                  </p>
                </div>
                <div>
                  <p class="text-xs uppercase tracking-wide text-muted">Combustible / color</p>
                  <p class="mt-1 capitalize">
                    {{ ordenSeleccionada.vehiculoCombustible || '—' }}
                    <span v-if="ordenSeleccionada.vehiculoColor"> · {{ ordenSeleccionada.vehiculoColor }}</span>
                  </p>
                </div>
              </div>
            </div>

            <FormField label="Tipo de falla" name="tipoFalla" required :error="errors.tipoFalla">
              <USelect
                v-model="state.tipoFalla"
                :items="tipoFallaItems"
                placeholder="Seleccionar tipo"
                class="w-full"
              />
            </FormField>
            <FormField label="Urgencia" name="urgencia" required :error="errors.urgencia">
              <div translate="no">
                <USelect v-model="state.urgencia" :items="urgenciaItems" class="w-full">
                  <template #default="{ modelValue }">
                    <span translate="no">{{ urgenciaItems.find(i => i.value === modelValue)?.label || modelValue }}</span>
                  </template>
                  <template #item-label="{ item }">
                    <span translate="no">{{ item.label }}</span>
                  </template>
                </USelect>
              </div>
            </FormField>
            <FormField label="Descripción del problema" name="descripcion" required :error="errors.descripcion" class="md:col-span-2">
              <UTextarea v-model="state.descripcion" class="w-full" :rows="3" />
            </FormField>
            <FormField label="Momento en que ocurre" name="momento" required :error="errors.momento">
              <UInput v-model="state.momento" placeholder="Ej: al arrancar, en marcha, al frenar" class="w-full" />
            </FormField>
            <FormField label="Luces en tablero" name="lucesTablero" :error="errors.lucesTablero">
              <UInput v-model="state.lucesTablero" class="w-full" />
            </FormField>
            <FormField label="Ruidos" name="ruidos" :error="errors.ruidos">
              <UInput v-model="state.ruidos" class="w-full" />
            </FormField>
            <div class="flex items-center">
              <UCheckbox v-model="state.puedeCircular" label="El vehículo puede circular" />
            </div>
            <FormField label="Observaciones adicionales" name="observaciones" :error="errors.observaciones" class="md:col-span-2">
              <UTextarea v-model="state.observaciones" class="w-full" />
            </FormField>
            <div class="md:col-span-2 flex gap-3">
              <UButton
                type="submit"
                label="Generar diagnóstico"
                icon="i-lucide-sparkles"
                :loading="isLoading"
                :disabled="!state.ordenTrabajoId"
              />
              <UButton variant="ghost" color="neutral" label="Cancelar" :to="route('diagnosticos-ia.index')" />
            </div>
          </form>
        </UCard>
      </div>
    </template>
  </AppDashboardPanel>
</template>
