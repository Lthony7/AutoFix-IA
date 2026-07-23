<script setup lang="ts">
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

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

const formatMoney = (value: number) =>
  new Intl.NumberFormat('es-EC', { style: 'currency', currency: 'USD' }).format(value)

const estadoColor = (estado: string) => {
  const map: Record<string, string> = {
    borrador: 'neutral',
    emitida: 'info',
    pagada: 'success',
    anulada: 'error'
  }
  return map[estado] || 'neutral'
}

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
          <UButton icon="i-lucide-plus" label="Generar factura" :to="route('facturas.create')" />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <UCard>
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
                  <UBadge :color="estadoColor(factura.estado) as any" variant="subtle">
                    {{ factura.estadoLabel }}
                  </UBadge>
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
      </UCard>
    </template>
  </AppDashboardPanel>
</template>
