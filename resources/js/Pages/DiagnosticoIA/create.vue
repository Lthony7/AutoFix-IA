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

const prioridadItems = [
  { label: 'Baja', value: 'baja', description: 'Puede esperar' },
  { label: 'Media', value: 'media', description: 'Atender pronto' },
  { label: 'Alta', value: 'alta', description: 'Urgente / seguridad' }
]

const tipoFallaItems = [
  { label: 'Sistema eléctrico', value: 'Eléctrico', icon: 'i-lucide-zap' },
  { label: 'Motor', value: 'Motor', icon: 'i-lucide-gauge' },
  { label: 'Frenos', value: 'Frenos', icon: 'i-lucide-circle-dot' },
  { label: 'Suspensión / dirección', value: 'Suspensión', icon: 'i-lucide-move' },
  { label: 'Transmisión / embrague', value: 'Transmisión', icon: 'i-lucide-cog' },
  { label: 'Inyección / sensores', value: 'Inyección', icon: 'i-lucide-cpu' },
  { label: 'Aire acondicionado', value: 'Aire acondicionado', icon: 'i-lucide-wind' },
  { label: 'Otro', value: 'Otro', icon: 'i-lucide-wrench' }
]

const isLoading = ref(false)
const detalleExtra = ref(false)

const state = reactive({
  ordenTrabajoId: ordenPrefill.value?.id || '',
  tipoFalla: ordenPrefill.value?.tipoFalla || '',
  urgencia: (ordenPrefill.value?.prioridad as string) || 'media',
  descripcion: ordenPrefill.value?.fallaReportada || '',
  momento: '',
  lucesTablero: '',
  ruidos: '',
  puedeCircular: true,
  observaciones: ''
})

const ordenSeleccionada = computed(() =>
  ordenes.value.find(o => o.id === state.ordenTrabajoId)
  || (ordenPrefill.value?.id === state.ordenTrabajoId ? ordenPrefill.value : null)
)

const vehiculoItems = computed(() =>
  ordenes.value.map(o => ({
    label: [
      o.vehiculoPlaca || 'Sin placa',
      [o.vehiculoMarca, o.vehiculoModelo].filter(Boolean).join(' '),
      o.vehiculoAnio ? `(${o.vehiculoAnio})` : null,
      o.clienteNombre ? `· ${o.clienteNombre}` : null,
      `· OT ${o.numero}`
    ].filter(Boolean).join(' '),
    value: o.id
  }))
)

const kilometrajeMostrar = computed(() => {
  const orden = ordenSeleccionada.value
  if (!orden) return null
  return orden.kilometrajeIngreso ?? orden.kilometrajeVehiculo ?? null
})

const puedeGenerar = computed(() =>
  Boolean(state.ordenTrabajoId && state.tipoFalla && state.urgencia && state.descripcion.trim().length >= 10)
)

const aplicarDatosOrden = (orden: OrdenOption | null | undefined) => {
  if (!orden) return
  if (orden.tipoFalla && !state.tipoFalla) state.tipoFalla = orden.tipoFalla
  if (orden.fallaReportada && !state.descripcion) state.descripcion = orden.fallaReportada
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
  aplicarDatosOrden(ordenes.value.find(o => o.id === id))
})

const handleSubmit = () => {
  if (!puedeGenerar.value) return
  isLoading.value = true
  router.post(route('diagnosticos-ia.store'), {
    ordenTrabajoId: state.ordenTrabajoId,
    tipoFalla: state.tipoFalla,
    urgencia: state.urgencia,
    descripcion: state.descripcion.trim(),
    momento: state.momento || 'No especificado',
    lucesTablero: state.lucesTablero || null,
    ruidos: state.ruidos || null,
    puedeCircular: state.puedeCircular,
    observaciones: state.observaciones || null
  }, {
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
      <div class="w-full space-y-4">
        <UAlert
          color="warning"
          variant="subtle"
          icon="i-lucide-triangle-alert"
          title="Sugerencia inicial de IA"
          description="Al generar, la IA propondrá observaciones, mecánico, servicios y repuestos. El mecánico debe confirmar el diagnóstico final."
        />

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center text-xs sm:text-sm">
          <div class="rounded-lg border border-default px-2 py-2.5 bg-elevated/30">
            <p class="font-semibold text-highlighted">1. Vehículo</p>
            <p class="text-muted mt-0.5">OT / placa</p>
          </div>
          <div class="rounded-lg border border-default px-2 py-2.5 bg-elevated/30">
            <p class="font-semibold text-highlighted">2. Falla</p>
            <p class="text-muted mt-0.5">Eléctrico, motor…</p>
          </div>
          <div class="rounded-lg border border-default px-2 py-2.5 bg-elevated/30">
            <p class="font-semibold text-highlighted">3. Prioridad</p>
            <p class="text-muted mt-0.5">Baja / media / alta</p>
          </div>
          <div class="rounded-lg border border-default px-2 py-2.5 bg-elevated/30">
            <p class="font-semibold text-highlighted">4. Reporte</p>
            <p class="text-muted mt-0.5">Y generar IA</p>
          </div>
        </div>

        <UCard v-if="!ordenes.length">
          <div class="space-y-3 text-center py-4">
            <p class="text-sm text-muted">
              No hay vehículos con orden pendiente de diagnóstico. Crea una OT primero (cliente + vehículo + falla).
            </p>
            <UButton
              icon="i-lucide-clipboard-plus"
              label="Crear orden de trabajo"
              :to="route('ordenes.create')"
            />
          </div>
        </UCard>

        <form v-else class="space-y-4" @submit.prevent="handleSubmit">
          <UCard>
            <h3 class="font-semibold mb-1 flex items-center gap-2">
              <span class="inline-flex size-6 items-center justify-center rounded-full bg-primary/15 text-xs font-bold text-primary">1</span>
              Escoger vehículo
            </h3>
            <p class="text-sm text-muted mb-4">Selecciona el vehículo (vinculado a su orden de trabajo pendiente).</p>

            <FormField label="Vehículo" name="ordenTrabajoId" required :error="errors.ordenTrabajoId || errors.orden_trabajo_id">
              <USelect
                v-model="state.ordenTrabajoId"
                :items="vehiculoItems"
                placeholder="Placa — marca modelo — cliente"
                class="w-full"
              />
            </FormField>

            <div
              v-if="ordenSeleccionada"
              class="mt-4 rounded-lg border border-default/70 bg-elevated/40 p-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm"
            >
              <div>
                <p class="text-xs uppercase tracking-wide text-muted">Cliente</p>
                <p class="mt-1 font-medium">{{ ordenSeleccionada.clienteNombre || '—' }}</p>
              </div>
              <div>
                <p class="text-xs uppercase tracking-wide text-muted">Vehículo</p>
                <p class="mt-1 font-medium">
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
                  {{ kilometrajeMostrar != null ? `${Number(kilometrajeMostrar).toLocaleString()} km` : 'No registrado' }}
                </p>
              </div>
              <div>
                <p class="text-xs uppercase tracking-wide text-muted">Orden</p>
                <p class="mt-1 font-medium">{{ ordenSeleccionada.numero }}</p>
              </div>
            </div>
          </UCard>

          <UCard>
            <h3 class="font-semibold mb-1 flex items-center gap-2">
              <span class="inline-flex size-6 items-center justify-center rounded-full bg-primary/15 text-xs font-bold text-primary">2</span>
              ¿Qué falla presenta?
            </h3>
            <p class="text-sm text-muted mb-4">Elige el sistema o área donde se manifiesta el problema.</p>

            <FormField label="Tipo de falla" name="tipoFalla" required :error="errors.tipoFalla || errors.tipo_falla">
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <button
                  v-for="item in tipoFallaItems"
                  :key="item.value"
                  type="button"
                  class="rounded-lg border px-3 py-3 text-left text-sm transition-colors"
                  :class="state.tipoFalla === item.value
                    ? 'border-primary bg-primary/10 ring-1 ring-primary/40'
                    : 'border-default hover:bg-elevated/50'"
                  @click="state.tipoFalla = item.value"
                >
                  <UIcon :name="item.icon" class="size-4 mb-1.5 opacity-80" />
                  <span class="block font-medium leading-snug">{{ item.label }}</span>
                </button>
              </div>
            </FormField>
          </UCard>

          <UCard>
            <h3 class="font-semibold mb-1 flex items-center gap-2">
              <span class="inline-flex size-6 items-center justify-center rounded-full bg-primary/15 text-xs font-bold text-primary">3</span>
              Prioridad
            </h3>
            <p class="text-sm text-muted mb-4">Según urgencia y seguridad del caso.</p>

            <FormField label="Prioridad" name="urgencia" required :error="errors.urgencia">
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                <button
                  v-for="item in prioridadItems"
                  :key="item.value"
                  type="button"
                  class="rounded-lg border px-3 py-3 text-left transition-colors"
                  :class="state.urgencia === item.value
                    ? 'border-primary bg-primary/10 ring-1 ring-primary/40'
                    : 'border-default hover:bg-elevated/50'"
                  @click="state.urgencia = item.value"
                >
                  <span class="font-semibold capitalize">{{ item.label }}</span>
                  <span class="block text-xs text-muted mt-0.5">{{ item.description }}</span>
                </button>
              </div>
            </FormField>
          </UCard>

          <UCard>
            <h3 class="font-semibold mb-1 flex items-center gap-2">
              <span class="inline-flex size-6 items-center justify-center rounded-full bg-primary/15 text-xs font-bold text-primary">4</span>
              Detallar reporte
            </h3>
            <p class="text-sm text-muted mb-4">
              Describe brevemente qué ocurre. Con eso la IA generará observaciones, mecánico sugerido, servicios y repuestos.
            </p>

            <div class="space-y-4">
              <FormField label="Reporte de la falla" name="descripcion" required :error="errors.descripcion">
                <UTextarea
                  v-model="state.descripcion"
                  class="w-full"
                  :rows="4"
                  placeholder="Ej: El vehículo no enciende, solo hace click al girar la llave. Luces del tablero débiles. Empezó ayer después de dejar las luces encendidas."
                />
                <p class="mt-1.5 text-xs text-muted">Mínimo ~10 caracteres. Sé concreto: síntoma, cuándo ocurre y desde cuándo.</p>
              </FormField>

              <FormField label="Notas adicionales (opcional)" name="observaciones" :error="errors.observaciones">
                <UTextarea
                  v-model="state.observaciones"
                  class="w-full"
                  :rows="2"
                  placeholder="Garantía, trabajos previos, piezas ya cambiadas, etc."
                />
              </FormField>

              <div>
                <UButton
                  type="button"
                  variant="ghost"
                  color="neutral"
                  size="sm"
                  :icon="detalleExtra ? 'i-lucide-chevron-up' : 'i-lucide-chevron-down'"
                  :label="detalleExtra ? 'Ocultar detalle extra' : 'Agregar detalle extra (opcional)'"
                  @click="detalleExtra = !detalleExtra"
                />
              </div>

              <div v-if="detalleExtra" class="grid grid-cols-1 md:grid-cols-2 gap-4 rounded-lg border border-default/60 p-4">
                <FormField label="Momento en que ocurre" name="momento" :error="errors.momento">
                  <UInput v-model="state.momento" placeholder="Ej: al arrancar, en marcha, al frenar" class="w-full" />
                </FormField>
                <FormField label="Luces en tablero" name="lucesTablero" :error="errors.lucesTablero || errors.luces_tablero">
                  <UInput v-model="state.lucesTablero" placeholder="Ej: check engine, batería" class="w-full" />
                </FormField>
                <FormField label="Ruidos" name="ruidos" :error="errors.ruidos">
                  <UInput v-model="state.ruidos" placeholder="Ej: chirrido, golpe metálico" class="w-full" />
                </FormField>
                <div class="flex items-center">
                  <UCheckbox v-model="state.puedeCircular" label="El vehículo puede circular" />
                </div>
              </div>
            </div>
          </UCard>

          <UCard>
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div class="text-sm text-muted max-w-xl">
                Al generar, la IA devolverá: <strong class="text-highlighted">observaciones</strong>,
                <strong class="text-highlighted">mecánico</strong> sugerido,
                <strong class="text-highlighted">servicios</strong> y
                <strong class="text-highlighted">repuestos</strong>, y los aplicará a la orden para revisión.
              </div>
              <div class="flex gap-3">
                <UButton variant="ghost" color="neutral" label="Cancelar" :to="route('diagnosticos-ia.index')" />
                <UButton
                  type="submit"
                  label="Generar diagnóstico IA"
                  icon="i-lucide-sparkles"
                  size="lg"
                  :loading="isLoading"
                  :disabled="!puedeGenerar"
                />
              </div>
            </div>
          </UCard>
        </form>
      </div>
    </template>
  </AppDashboardPanel>
</template>
