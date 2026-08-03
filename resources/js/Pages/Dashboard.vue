<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import MetricStatCards, { type MetricCard } from '../components/MetricStatCards.vue'
import ModulePanel from '../components/ModulePanel.vue'

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

const metricCards = computed((): MetricCard[] => {
  const base: MetricCard[] = [
    {
      key: 'ordenes',
      title: vista.value === 'cliente' ? 'Mis órdenes abiertas' : 'Órdenes abiertas',
      value: metrics.value.ordenesAbiertas ?? 0,
      icon: 'i-lucide-clipboard-list',
      tone: 'warn',
      to: vista.value === 'cliente' ? route('portal.mis-ordenes') : route('ordenes.index')
    },
    {
      key: 'facturas',
      title: 'Facturas pendientes',
      value: metrics.value.facturasPendientes ?? 0,
      icon: 'i-lucide-file-text',
      tone: 'blue',
      to: route('facturas.index')
    },
    {
      key: 'ingresos',
      title: 'Ingresos del mes',
      value: formatMoney(metrics.value.ingresosMes ?? 0),
      icon: 'i-lucide-wallet',
      tone: 'ok',
      to: route('pagos.index')
    },
    {
      key: 'clientes',
      title: 'Clientes activos',
      value: metrics.value.clientesActivos ?? 0,
      icon: 'i-lucide-users',
      tone: 'green',
      to: route('clientes.index')
    }
  ]

  if (vista.value === 'cliente') {
    return [base[0]]
  }

  return base
})

const badgeClass = (estado: string) => {
  const map: Record<string, string> = {
    pendiente: 'autofix-badge-solid--warn',
    en_diagnostico: 'autofix-badge-solid--warn',
    en_reparacion: 'autofix-badge-solid--ok',
    finalizada: 'autofix-badge-solid--ok',
    entregada: 'autofix-badge-solid--ok',
    cancelada: 'autofix-badge-solid--danger'
  }
  return map[estado] || 'autofix-badge-solid--neutral'
}
</script>

<template>
  <AppDashboardPanel id="home">
    <template #header>
      <UDashboardNavbar title="Dashboard">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="space-y-4">
        <MetricStatCards :cards="metricCards" :columns="vista === 'cliente' ? 3 : 4" />

        <ModulePanel title="Órdenes recientes">
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
                    <span class="autofix-badge-solid" :class="badgeClass(orden.estado)">
                      {{ orden.estadoLabel }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
            <p v-if="!ordenesRecientes.length" class="py-6 text-center text-muted text-sm">
              Aún no hay órdenes registradas.
            </p>
          </div>
        </ModulePanel>
      </div>
    </template>
  </AppDashboardPanel>
</template>
