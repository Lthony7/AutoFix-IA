<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

interface Orden {
  id: string
  numero: string
  estado: string
  estadoLabel: string
  vehiculoPlaca: string | null
  tipoFalla: string | null
  prioridad: string | null
  totalPago: number | null
  tieneReporteIa?: boolean
  facturaId?: string | null
  createdAt: string
}

interface Notificacion {
  id: string
  mensaje: string
  url: string | null
  readAt: string | null
  createdAt: string | null
}

const page = usePage()
const ordenes = computed(() => (page.props as any).ordenes)
const rows = computed(() => (ordenes.value?.data || []) as Orden[])
const notificaciones = computed(() => ((page.props as any).notificaciones || []) as Notificacion[])

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

const prioridadColor = (prioridad: string | null) => {
  if (!prioridad) return 'neutral'
  const map: Record<string, string> = { baja: 'success', media: 'warning', alta: 'error' }
  return map[prioridad] || 'neutral'
}
</script>

<template>
  <AppDashboardPanel id="portal-ordenes">
    <template #header>
      <UDashboardNavbar title="Mis órdenes">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="space-y-4">
        <UCard v-if="notificaciones.length">
          <h3 class="font-semibold mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-bell" class="size-4" />
            Notificaciones
          </h3>
          <ul class="space-y-2">
            <li
              v-for="n in notificaciones"
              :key="n.id"
              class="text-sm flex flex-wrap items-center justify-between gap-2 border-b border-default/50 pb-2 last:border-0"
            >
              <span :class="n.readAt ? 'text-muted' : 'font-medium'">{{ n.mensaje }}</span>
              <UButton
                v-if="n.url"
                size="xs"
                variant="soft"
                label="Abrir"
                :to="n.url"
              />
            </li>
          </ul>
        </UCard>

        <UCard>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left border-b border-default">
                  <th class="py-3 pr-3">Número</th>
                  <th class="py-3 pr-3">Fecha</th>
                  <th class="py-3 pr-3">Vehículo</th>
                  <th class="py-3 pr-3">Estado</th>
                  <th class="py-3 pr-3">Falla</th>
                  <th class="py-3 pr-3">Prioridad</th>
                  <th class="py-3">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="orden in rows"
                  :key="orden.id"
                  class="border-b border-default/60"
                >
                  <td class="py-3 pr-3 font-medium">{{ orden.numero }}</td>
                  <td class="py-3 pr-3">{{ orden.createdAt }}</td>
                  <td class="py-3 pr-3">{{ orden.vehiculoPlaca || '—' }}</td>
                  <td class="py-3 pr-3">
                    <UBadge :color="estadoColor(orden.estado) as any" variant="subtle">
                      {{ orden.estadoLabel }}
                    </UBadge>
                  </td>
                  <td class="py-3 pr-3">{{ orden.tipoFalla || '—' }}</td>
                  <td class="py-3 pr-3">
                    <UBadge v-if="orden.prioridad" :color="prioridadColor(orden.prioridad) as any" variant="subtle">
                      {{ orden.prioridad }}
                    </UBadge>
                    <span v-else>—</span>
                  </td>
                  <td class="py-3 flex flex-wrap gap-1">
                    <UButton
                      size="xs"
                      variant="soft"
                      icon="i-lucide-eye"
                      label="Detalle"
                      :to="route('portal.mis-ordenes.show', orden.id)"
                    />
                    <UButton
                      v-if="orden.facturaId"
                      size="xs"
                      variant="ghost"
                      icon="i-lucide-file-text"
                      label="Factura"
                      :to="route('portal.facturas.show', orden.facturaId)"
                    />
                  </td>
                </tr>
                <tr v-if="!rows.length">
                  <td colspan="7" class="py-6 text-center text-muted">No tienes órdenes registradas.</td>
                </tr>
              </tbody>
            </table>
          </div>
          <AppPagination :meta="ordenes?.meta" />
        </UCard>
      </div>
    </template>
  </AppDashboardPanel>
</template>
