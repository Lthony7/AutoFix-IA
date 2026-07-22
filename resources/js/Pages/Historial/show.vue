<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

interface Vehiculo {
  id: string
  placa: string
  marca: string
  modelo: string
  anio: number
  clienteNombre: string | null
}

interface OrdenHistorial {
  id: string
  numero: string
  estado: string
  estadoLabel: string
  tipoFalla: string | null
  fallaReportada: string | null
  kilometrajeIngreso: number | null
  mecanicoNombre: string | null
  totalPago: number | null
  createdAt: string
}

const page = usePage()
const vehiculo = computed(() => (page.props as any).vehiculo as Vehiculo)
const ordenes = computed(() => ((page.props as any).ordenes || []) as OrdenHistorial[])

const formatMoney = (value: number) =>
  new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(value)

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
  <UDashboardPanel id="historial">
    <template #header>
      <UDashboardNavbar :title="`Historial — ${vehiculo.placa}`">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton variant="ghost" icon="i-lucide-arrow-left" label="Vehículos" :to="route('vehiculos.index')" />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
          <UCard>
            <p class="text-sm text-muted">Placa</p>
            <p class="text-xl font-semibold">{{ vehiculo.placa }}</p>
          </UCard>
          <UCard>
            <p class="text-sm text-muted">Vehículo</p>
            <p class="text-xl font-semibold">{{ vehiculo.marca }} {{ vehiculo.modelo }}</p>
          </UCard>
          <UCard>
            <p class="text-sm text-muted">Año</p>
            <p class="text-xl font-semibold">{{ vehiculo.anio }}</p>
          </UCard>
          <UCard>
            <p class="text-sm text-muted">Cliente</p>
            <p class="text-xl font-semibold">{{ vehiculo.clienteNombre || '—' }}</p>
          </UCard>
        </div>

        <UCard>
          <h3 class="font-semibold mb-4 flex items-center gap-2">
            <UIcon name="i-lucide-history" class="size-4" />
            Órdenes de trabajo
          </h3>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left border-b border-default">
                  <th class="py-3 pr-3">Número</th>
                  <th class="py-3 pr-3">Fecha</th>
                  <th class="py-3 pr-3">Estado</th>
                  <th class="py-3 pr-3">Falla</th>
                  <th class="py-3 pr-3">Mecánico</th>
                  <th class="py-3 pr-3">Km ingreso</th>
                  <th class="py-3">Total pago</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="orden in ordenes"
                  :key="orden.id"
                  class="border-b border-default/60"
                >
                  <td class="py-3 pr-3 font-medium">{{ orden.numero }}</td>
                  <td class="py-3 pr-3">{{ orden.createdAt }}</td>
                  <td class="py-3 pr-3">
                    <UBadge :color="estadoColor(orden.estado) as any" variant="subtle">
                      {{ orden.estadoLabel }}
                    </UBadge>
                  </td>
                  <td class="py-3 pr-3 max-w-xs truncate" :title="orden.fallaReportada || ''">
                    {{ orden.tipoFalla || orden.fallaReportada || '—' }}
                  </td>
                  <td class="py-3 pr-3">{{ orden.mecanicoNombre || '—' }}</td>
                  <td class="py-3 pr-3">{{ orden.kilometrajeIngreso?.toLocaleString() ?? '—' }}</td>
                  <td class="py-3">
                    {{ orden.totalPago != null ? formatMoney(orden.totalPago) : '—' }}
                  </td>
                </tr>
                <tr v-if="!ordenes.length">
                  <td colspan="7" class="py-6 text-center text-muted">Sin órdenes registradas para este vehículo.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </UCard>
      </div>
    </template>
  </UDashboardPanel>
</template>
