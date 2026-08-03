<script setup lang="ts">
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import MetricStatCards, { type MetricCard } from '../../components/MetricStatCards.vue'
import ModulePanel from '../../components/ModulePanel.vue'

interface Pago {
  id: string
  ordenNumero: string | null
  clienteNombre: string | null
  vehiculoPlaca: string | null
  valorServicios: number
  valorRepuestos: number
  descuento: number
  total: number
  estado: string
  estadoLabel: string
  metodoPago: string | null
  createdAt: string
}

const page = usePage()
const pagos = computed(() => (page.props as any).pagos)
const stats = computed(() => (page.props as any).stats || {})
const filters = computed(() => (page.props as any).filters || { estado: '' })

const formatMoney = (value: number) =>
  new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(value)

const badgeClass = (estado: string) => {
  const map: Record<string, string> = {
    pendiente: 'autofix-badge-solid--warn',
    pagado: 'autofix-badge-solid--ok',
    anulado: 'autofix-badge-solid--danger'
  }
  return map[estado] || 'autofix-badge-solid--neutral'
}

const filterUrl = (estado: string) => {
  const qs = estado ? `?estado=${encodeURIComponent(estado)}` : ''
  return route('pagos.index') + qs
}

const metricCards = computed((): MetricCard[] => [
  {
    key: 'pagado',
    title: 'Pagados',
    value: stats.value.pagado ?? 0,
    icon: 'i-lucide-circle-check',
    tone: 'ok',
    to: filterUrl('pagado')
  },
  {
    key: 'pendiente',
    title: 'Pendientes',
    value: stats.value.pendiente ?? 0,
    icon: 'i-lucide-clock',
    tone: 'warn',
    to: filterUrl('pendiente')
  },
  {
    key: 'anulado',
    title: 'Anulados',
    value: stats.value.anulado ?? 0,
    icon: 'i-lucide-ban',
    tone: 'danger',
    to: filterUrl('anulado')
  },
  {
    key: 'ingresosMes',
    title: 'Ingresos del mes',
    value: formatMoney(Number(stats.value.ingresosMes ?? 0)),
    icon: 'i-lucide-wallet',
    tone: 'green'
  }
])

const destroy = (id: string) => {
  if (!confirm('¿Eliminar este pago?')) return
  router.delete(route('pagos.destroy', id))
}
</script>

<template>
  <AppDashboardPanel id="pagos">
    <template #header>
      <UDashboardNavbar title="Pagos">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="space-y-4">
        <MetricStatCards
          :cards="metricCards"
          :columns="4"
          :model-value="filters.estado || null"
        />

        <ModulePanel title="Pagos">
          <template #actions>
            <UButton
              icon="i-lucide-plus"
              label="Nuevo pago"
              color="success"
              :to="route('pagos.create')"
            />
            <UButton
              v-if="filters.estado"
              size="sm"
              variant="soft"
              label="Ver todos"
              :to="route('pagos.index')"
            />
          </template>

          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left border-b border-default">
                  <th class="py-3 pr-3">Orden</th>
                  <th class="py-3 pr-3">Cliente</th>
                  <th class="py-3 pr-3">Placa</th>
                  <th class="py-3 pr-3">Total</th>
                  <th class="py-3 pr-3">Estado</th>
                  <th class="py-3 pr-3">Método</th>
                  <th class="py-3">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="pago in (pagos?.data || []) as Pago[]"
                  :key="pago.id"
                  class="border-b border-default/60"
                >
                  <td class="py-3 pr-3 font-medium">{{ pago.ordenNumero || '—' }}</td>
                  <td class="py-3 pr-3">{{ pago.clienteNombre || '—' }}</td>
                  <td class="py-3 pr-3">{{ pago.vehiculoPlaca || '—' }}</td>
                  <td class="py-3 pr-3 font-medium">{{ formatMoney(pago.total) }}</td>
                  <td class="py-3 pr-3">
                    <span class="autofix-badge-solid" :class="badgeClass(pago.estado)">
                      {{ pago.estadoLabel }}
                    </span>
                  </td>
                  <td class="py-3 pr-3">{{ pago.metodoPago || '—' }}</td>
                  <td class="py-3 flex gap-2">
                    <UButton size="xs" variant="ghost" icon="i-lucide-pencil" :to="route('pagos.edit', pago.id)" />
                    <UButton size="xs" color="error" variant="ghost" icon="i-lucide-trash" @click="destroy(pago.id)" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <AppPagination :meta="pagos?.meta" />
        </ModulePanel>
      </div>
    </template>
  </AppDashboardPanel>
</template>
