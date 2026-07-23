<script setup lang="ts">
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

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

const formatMoney = (value: number) =>
  new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(value)

const estadoColor = (estado: string) => {
  const map: Record<string, string> = { pendiente: 'warning', pagado: 'success', anulado: 'error' }
  return map[estado] || 'neutral'
}

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
        <template #right>
          <UButton icon="i-lucide-plus" label="Nuevo pago" :to="route('pagos.create')" />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <UCard>
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
                  <UBadge :color="estadoColor(pago.estado) as any" variant="subtle">
                    {{ pago.estadoLabel }}
                  </UBadge>
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
      </UCard>
    </template>
  </AppDashboardPanel>
</template>
