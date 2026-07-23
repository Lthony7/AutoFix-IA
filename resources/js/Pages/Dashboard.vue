<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

interface Metrics {
  ordenesAbiertas: number
  facturasPendientes: number
  ingresosMes: number
  clientesActivos: number
}

interface OrdenReciente {
  id: string
  numero: string
  estado: string
  estadoLabel: string
  clienteNombre: string | null
  vehiculoPlaca: string | null
}

const page = usePage()
const metrics = computed(() => ((page.props as any).metrics || {}) as Metrics)
const ordenesRecientes = computed(() => ((page.props as any).ordenesRecientes || []) as OrdenReciente[])
const vista = computed(() => ((page.props as any).vista as string) || 'taller')

const formatMoney = (value: number) =>
  new Intl.NumberFormat('es-EC', { style: 'currency', currency: 'USD' }).format(value || 0)

const cards = computed(() => {
  const base = [
    {
      title: vista.value === 'cliente' ? 'Mis órdenes abiertas' : 'Órdenes abiertas',
      value: metrics.value.ordenesAbiertas ?? 0,
      icon: 'i-lucide-clipboard-list',
      to: vista.value === 'cliente' ? route('portal.mis-ordenes') : route('ordenes.index'),
      show: true
    },
    {
      title: 'Facturas pendientes',
      value: metrics.value.facturasPendientes ?? 0,
      icon: 'i-lucide-file-text',
      to: route('facturas.index'),
      show: vista.value === 'taller'
    },
    {
      title: 'Ingresos del mes',
      value: formatMoney(metrics.value.ingresosMes ?? 0),
      icon: 'i-lucide-wallet',
      to: route('pagos.index'),
      show: vista.value === 'taller'
    },
    {
      title: 'Clientes activos',
      value: metrics.value.clientesActivos ?? 0,
      icon: 'i-lucide-users',
      to: route('clientes.index'),
      show: vista.value === 'taller'
    }
  ]

  return base.filter(c => c.show)
})

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
  <AppDashboardPanel id="home">
    <template #header>
      <UDashboardNavbar title="Dashboard AUTOFIX IA">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <UPageCard
          v-for="card in cards"
          :key="card.title"
          :icon="card.icon"
          :title="card.title"
          :to="card.to"
          variant="subtle"
        >
          <span class="text-2xl font-semibold">{{ card.value }}</span>
        </UPageCard>
      </div>

      <UCard>
        <template #header>
          <h3 class="font-medium">Órdenes recientes</h3>
        </template>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left border-b border-default">
                <th class="py-2 pr-2">Número</th>
                <th class="py-2 pr-2">Cliente</th>
                <th class="py-2 pr-2">Placa</th>
                <th class="py-2">Estado</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="orden in ordenesRecientes"
                :key="orden.id"
                class="border-b border-default/50"
              >
                <td class="py-2 pr-2 font-medium">{{ orden.numero }}</td>
                <td class="py-2 pr-2">{{ orden.clienteNombre || '—' }}</td>
                <td class="py-2 pr-2">{{ orden.vehiculoPlaca || '—' }}</td>
                <td class="py-2">
                  <UBadge :color="estadoColor(orden.estado) as any" variant="subtle">
                    {{ orden.estadoLabel }}
                  </UBadge>
                </td>
              </tr>
            </tbody>
          </table>
          <p v-if="!ordenesRecientes.length" class="py-6 text-center text-muted text-sm">
            Aún no hay órdenes registradas.
          </p>
        </div>
      </UCard>
    </template>
  </AppDashboardPanel>
</template>
