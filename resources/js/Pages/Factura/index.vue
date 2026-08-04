<script setup lang="ts">
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import MetricStatCards, { type MetricCard } from '../../components/MetricStatCards.vue'
import ModulePanel from '../../components/ModulePanel.vue'

interface Factura {
  id: string
  numero: string
  ordenNumero: string | null
  clienteNombre: string | null
  total: number
  estado: string
  estadoLabel: string
  fechaEmision: string | null
}

const page = usePage()
const facturas = computed(() => (page.props as any).facturas)
const stats = computed(() => (page.props as any).stats || {})
const filters = computed(() => (page.props as any).filters || { estado: '' })

const formatMoney = (value: number) =>
  new Intl.NumberFormat('es-EC', { style: 'currency', currency: 'USD' }).format(value)

const badgeClass = (estado: string) => {
  const map: Record<string, string> = {
    borrador: 'autofix-badge-solid--neutral',
    emitida: 'autofix-badge-solid--warn',
    pagada: 'autofix-badge-solid--ok',
    anulada: 'autofix-badge-solid--danger'
  }
  return map[estado] || 'autofix-badge-solid--neutral'
}

const filterUrl = (estado: string) => {
  const qs = estado ? `?estado=${encodeURIComponent(estado)}` : ''
  return route('facturas.index') + qs
}

const metricCards = computed((): MetricCard[] => [
  {
    key: 'pagada',
    title: 'Pagadas',
    value: stats.value.pagada ?? 0,
    icon: 'i-lucide-circle-check',
    tone: 'ok',
    to: filterUrl('pagada')
  },
  {
    key: 'emitida',
    title: 'Emitidas',
    value: stats.value.emitida ?? 0,
    icon: 'i-lucide-file-text',
    tone: 'warn',
    to: filterUrl('emitida')
  },
  {
    key: 'borrador',
    title: 'Borrador',
    value: stats.value.borrador ?? 0,
    icon: 'i-lucide-file-pen',
    tone: 'blue',
    to: filterUrl('borrador')
  },
  {
    key: 'anulada',
    title: 'Anuladas',
    value: stats.value.anulada ?? 0,
    icon: 'i-lucide-file-x',
    tone: 'danger',
    to: filterUrl('anulada')
  }
])

const destroy = (id: string) => {
  if (!confirm('¿Eliminar esta factura?')) return
  router.delete(route('facturas.destroy', id))
}
</script>

<template>
  <AppDashboardPanel id="facturas">
    <template #header>
      <UDashboardNavbar title="Facturas">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <AppCreateButton :to="route('facturas.create')" label="Generar factura" />
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

        <ModulePanel title="Facturas">
          <template #actions>
            <UButton
              v-if="filters.estado"
              size="sm"
              variant="soft"
              label="Ver todas"
              :to="route('facturas.index')"
            />
          </template>

          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left border-b border-default">
                  <th class="py-3 pr-3">Número</th>
                  <th class="py-3 pr-3">OT</th>
                  <th class="py-3 pr-3">Cliente</th>
                  <th class="py-3 pr-3">Fecha</th>
                  <th class="py-3 pr-3">Total</th>
                  <th class="py-3 pr-3">Estado</th>
                  <th class="py-3">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="factura in (facturas?.data || []) as Factura[]"
                  :key="factura.id"
                  class="border-b border-default/60"
                >
                  <td class="py-3 pr-3 font-medium">{{ factura.numero }}</td>
                  <td class="py-3 pr-3">{{ factura.ordenNumero || '—' }}</td>
                  <td class="py-3 pr-3">{{ factura.clienteNombre || '—' }}</td>
                  <td class="py-3 pr-3">{{ factura.fechaEmision || '—' }}</td>
                  <td class="py-3 pr-3 font-medium">{{ formatMoney(factura.total) }}</td>
                  <td class="py-3 pr-3">
                    <span class="autofix-badge-solid" :class="badgeClass(factura.estado)">
                      {{ factura.estadoLabel }}
                    </span>
                  </td>
                  <td class="py-3 flex gap-2">
                    <UButton size="xs" variant="ghost" icon="i-lucide-eye" :to="route('facturas.show', factura.id)" />
                    <UButton size="xs" variant="ghost" icon="i-lucide-pencil" :to="route('facturas.edit', factura.id)" />
                    <UButton size="xs" color="error" variant="ghost" icon="i-lucide-trash" @click="destroy(factura.id)" />
                  </td>
                </tr>
              </tbody>
            </table>
            <p v-if="!(facturas?.data || []).length" class="py-8 text-center text-muted">
              No hay facturas registradas.
            </p>
          </div>
          <AppPagination :meta="facturas?.meta" />
        </ModulePanel>
      </div>
    </template>
  </AppDashboardPanel>
</template>
