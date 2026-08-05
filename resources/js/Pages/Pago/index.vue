<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import MetricStatCards, { type MetricCard } from '../../components/MetricStatCards.vue'
import ModulePanel from '../../components/ModulePanel.vue'

interface FacturaCobro {
  id: string
  numero: string
  ordenTrabajoId: string
  ordenNumero: string | null
  clienteNombre: string | null
  vehiculoPlaca: string | null
  subtotal: number
  iva: number
  descuento: number
  total: number
  estado: string
  estadoLabel: string
  pagoId: string | null
  pagoEstado: string | null
  pagoEstadoLabel: string | null
  createdAt: string
}

const page = usePage()
const facturas = computed(() => (page.props as any).facturas)
const stats = computed(() => (page.props as any).stats || {})
const filters = computed(() => (page.props as any).filters || { estado: '' })
const role = computed(() => String((page.props as any).auth?.user?.role || ''))

const formatMoney = (value: number) =>
  new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(value)

const badgeClass = (estado: string) => {
  const map: Record<string, string> = {
    emitida: 'autofix-badge-solid--warn',
    pagada: 'autofix-badge-solid--ok',
    anulada: 'autofix-badge-solid--danger',
    borrador: 'autofix-badge-solid--neutral'
  }
  return map[estado] || 'autofix-badge-solid--neutral'
}

const badgePagoClass = (estado: string | null) => {
  const map: Record<string, string> = {
    pagado: 'autofix-badge-solid--ok',
    pendiente: 'autofix-badge-solid--warn',
    anulado: 'autofix-badge-solid--danger'
  }
  return estado ? (map[estado] || 'autofix-badge-solid--neutral') : 'autofix-badge-solid--neutral'
}

const filterUrl = (estado: string) => {
  const qs = estado ? `?estado=${encodeURIComponent(estado)}` : ''
  return route('pagos.index') + qs
}

const esAdmin = computed(() => role.value === 'administrador')

const irAPagos = (factura: FacturaCobro) =>
  route('pagos.create', { ordenTrabajoId: factura.ordenTrabajoId })

const metricCards = computed((): MetricCard[] => [
  {
    key: 'emitida',
    title: 'Por cobrar',
    value: stats.value.emitida ?? 0,
    icon: 'i-lucide-clock',
    tone: 'warn',
    to: filterUrl('emitida')
  },
  {
    key: 'pagada',
    title: 'Cobradas',
    value: stats.value.pagada ?? 0,
    icon: 'i-lucide-circle-check',
    tone: 'ok',
    to: filterUrl('pagada')
  },
  {
    key: 'anulada',
    title: 'Anuladas',
    value: stats.value.anulada ?? 0,
    icon: 'i-lucide-ban',
    tone: 'danger',
    to: filterUrl('anulada')
  },
  {
    key: 'ingresosMes',
    title: 'Ingresos del mes',
    value: formatMoney(Number(stats.value.ingresosMes ?? 0)),
    icon: 'i-lucide-wallet',
    tone: 'green'
  }
])
</script>

<template>
  <AppDashboardPanel id="pagos">
    <template #header>
      <UDashboardNavbar title="Pagos">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <AppCreateButton :to="route('pagos.create')" label="Nuevo pago" />
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

        <ModulePanel title="Facturas y cobros">
          <template #actions>
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
                  <th class="py-3 pr-3">Factura</th>
                  <th class="py-3 pr-3">Orden</th>
                  <th class="py-3 pr-3">Cliente</th>
                  <th class="py-3 pr-3">Placa</th>
                  <th class="py-3 pr-3">Total</th>
                  <th class="py-3 pr-3">Estado</th>
                  <th class="py-3 pr-3">Pago</th>
                  <th class="py-3">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="factura in (facturas?.data || []) as FacturaCobro[]"
                  :key="factura.id"
                  class="border-b border-default/60"
                >
                  <td class="py-3 pr-3 font-medium">{{ factura.numero || '—' }}</td>
                  <td class="py-3 pr-3">{{ factura.ordenNumero || '—' }}</td>
                  <td class="py-3 pr-3">{{ factura.clienteNombre || '—' }}</td>
                  <td class="py-3 pr-3">{{ factura.vehiculoPlaca || '—' }}</td>
                  <td class="py-3 pr-3 font-medium">{{ formatMoney(factura.total) }}</td>
                  <td class="py-3 pr-3">
                    <span class="autofix-badge-solid" :class="badgeClass(factura.estado)">
                      {{ factura.estadoLabel }}
                    </span>
                  </td>
                  <td class="py-3 pr-3">
                    <span v-if="factura.pagoEstadoLabel" class="autofix-badge-solid" :class="badgePagoClass(factura.pagoEstado)">
                      {{ factura.pagoEstadoLabel }}
                    </span>
                    <span v-else class="text-sm text-gray-400">Sin pago</span>
                  </td>
                  <td class="py-3 flex flex-wrap gap-2">
                    <UButton
                      v-if="factura.estado === 'emitida' && factura.pagoEstado !== 'pagado'"
                      size="xs"
                      color="success"
                      variant="soft"
                      icon="i-lucide-wallet"
                      :label="factura.pagoId && factura.pagoEstado !== 'anulado' ? 'Completar cobro' : 'Ir a pagos'"
                      :to="factura.pagoId && factura.pagoEstado !== 'anulado' ? route('pagos.edit', factura.pagoId) : irAPagos(factura)"
                    />
                    <UButton size="xs" variant="ghost" icon="i-lucide-eye" :to="route('facturas.show', factura.id)" />
                    <UButton
                      v-if="esAdmin && factura.estado === 'emitida'"
                      size="xs"
                      variant="ghost"
                      icon="i-lucide-pencil"
                      :to="route('facturas.edit', factura.id)"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <AppPagination :meta="facturas?.meta" />
        </ModulePanel>
      </div>
    </template>
  </AppDashboardPanel>
</template>
