<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

interface Orden {
  id: string
  numero: string
  estado: string
  estadoLabel: string
  tipoFalla: string | null
  fallaReportada: string | null
  prioridad: string | null
  vehiculoPlaca: string | null
  vehiculoDescripcion: string | null
  kilometrajeIngreso: number | null
  createdAt: string
}

interface ReporteIa {
  diagnosticoDetalle: string | null
  respuestaCompleta: string | null
  advertencia: string | null
  prioridad: string | null
  estadoLabel: string | null
  createdAt: string | null
}

interface Avance {
  id: string
  mensaje: string
  usuarioNombre: string
  createdAt: string
}

const page = usePage()
const orden = computed(() => (page.props as any).orden as Orden)
const reporteIa = computed(() => (page.props as any).reporteIa as ReporteIa | null)
const observacionesMecanico = computed(() => (page.props as any).observacionesMecanico as string | null)
const avances = computed(() => ((page.props as any).avances || []) as Avance[])
const factura = computed(() => (page.props as any).factura as { id: string, numero: string, total: number, estadoLabel: string } | null)

const formatMoney = (value: number) =>
  new Intl.NumberFormat('es-EC', { style: 'currency', currency: 'USD' }).format(value)

const estadoColor = (estado: string) => {
  const map: Record<string, string> = {
    pendiente: 'warning',
    en_diagnostico: 'info',
    en_reparacion: 'primary',
    finalizada: 'success',
    entregada: 'success',
    cancelada: 'error'
  }
  return map[estado] || 'neutral'
}
</script>

<template>
  <AppDashboardPanel id="portal-orden-show">
    <template #header>
      <UDashboardNavbar :title="`Orden ${orden.numero}`">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton
            v-if="factura"
            icon="i-lucide-file-text"
            label="Ver factura"
            :to="route('portal.facturas.show', factura.id)"
          />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="max-w-4xl space-y-4">
        <div class="flex flex-wrap gap-2 items-center">
          <UBadge :color="estadoColor(orden.estado) as any" variant="subtle" size="lg">
            {{ orden.estadoLabel }}
          </UBadge>
          <UBadge v-if="orden.prioridad" variant="subtle">Prioridad: {{ orden.prioridad }}</UBadge>
          <span class="text-sm text-muted">{{ orden.createdAt }}</span>
        </div>

        <UCard>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
            <div>
              <p class="text-muted">Vehículo</p>
              <p class="font-medium">{{ orden.vehiculoPlaca || '—' }}</p>
              <p v-if="orden.vehiculoDescripcion" class="text-xs text-muted">{{ orden.vehiculoDescripcion }}</p>
            </div>
            <div>
              <p class="text-muted">Kilometraje ingreso</p>
              <p class="font-medium">
                {{ orden.kilometrajeIngreso != null ? `${Number(orden.kilometrajeIngreso).toLocaleString()} km` : '—' }}
              </p>
            </div>
            <div class="sm:col-span-2">
              <p class="text-muted">Falla reportada</p>
              <p class="font-medium whitespace-pre-wrap">{{ orden.fallaReportada || orden.tipoFalla || '—' }}</p>
            </div>
          </div>
        </UCard>

        <UCard>
          <h3 class="font-semibold mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-sparkles" class="size-4" />
            Reporte generado por la IA
          </h3>
          <template v-if="reporteIa">
            <p v-if="reporteIa.diagnosticoDetalle" class="text-sm whitespace-pre-wrap leading-relaxed mb-3">
              {{ reporteIa.diagnosticoDetalle }}
            </p>
            <p
              v-else-if="reporteIa.respuestaCompleta"
              class="text-sm whitespace-pre-wrap leading-relaxed mb-3"
            >
              {{ reporteIa.respuestaCompleta }}
            </p>
            <p v-else class="text-sm text-muted">El reporte IA aún no está disponible.</p>
            <UAlert
              v-if="reporteIa.advertencia"
              class="mt-3"
              color="warning"
              variant="subtle"
              icon="i-lucide-alert-circle"
              :title="reporteIa.advertencia"
            />
          </template>
          <p v-else class="text-sm text-muted">Todavía no hay un diagnóstico IA para esta orden.</p>
        </UCard>

        <UCard>
          <h3 class="font-semibold mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-message-square-text" class="size-4" />
            Observaciones del mecánico
          </h3>
          <p v-if="observacionesMecanico" class="text-sm whitespace-pre-wrap leading-relaxed">
            {{ observacionesMecanico }}
          </p>
          <p v-else class="text-sm text-muted">
            El mecánico aún no ha registrado observaciones sobre el diagnóstico.
          </p>
        </UCard>

        <UCard>
          <h3 class="font-semibold mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-activity" class="size-4" />
            Avances de la reparación
          </h3>
          <div v-if="avances.length" class="space-y-3">
            <div
              v-for="avance in avances"
              :key="avance.id"
              class="rounded-lg border border-default/60 px-3 py-2.5"
            >
              <p class="text-sm whitespace-pre-wrap">{{ avance.mensaje }}</p>
              <p class="text-xs text-muted mt-1">
                {{ avance.usuarioNombre }} · {{ avance.createdAt }}
              </p>
            </div>
          </div>
          <p v-else class="text-sm text-muted">Sin avances registrados todavía.</p>
        </UCard>

        <UCard v-if="factura">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <h3 class="font-semibold flex items-center gap-2">
                <UIcon name="i-lucide-file-text" class="size-4" />
                Factura {{ factura.numero }}
              </h3>
              <p class="text-sm text-muted mt-1">
                {{ factura.estadoLabel }} · {{ formatMoney(factura.total) }}
              </p>
            </div>
            <UButton
              icon="i-lucide-eye"
              label="Ver desglose"
              :to="route('portal.facturas.show', factura.id)"
            />
          </div>
        </UCard>

        <UButton variant="ghost" color="neutral" label="Volver a mis órdenes" :to="route('portal.mis-ordenes')" />
      </div>
    </template>
  </AppDashboardPanel>
</template>
