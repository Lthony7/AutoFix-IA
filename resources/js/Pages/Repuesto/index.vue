<script setup lang="ts">
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

interface Repuesto {
  id: string
  codigo: string
  nombre: string
  descripcion: string
  precio: number
  stock: number
  stockMinimo: number
  categoria: string | null
  proveedor: string | null
  tipoProducto: string
  activo: boolean
}

const page = usePage()
const repuestos = computed(() => (page.props as any).repuestos)

const formatMoney = (value: number) =>
  new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(value)

const destroy = (id: string) => {
  if (!confirm('¿Eliminar este repuesto?')) return
  router.delete(route('repuestos.destroy', id))
}
</script>

<template>
  <AppDashboardPanel id="repuestos">
    <template #header>
      <UDashboardNavbar title="Repuestos">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton icon="i-lucide-plus" label="Nuevo repuesto" :to="route('repuestos.create')" />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <UCard>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left border-b border-default">
                <th class="py-3 pr-3">Código</th>
                <th class="py-3 pr-3">Nombre</th>
                <th class="py-3 pr-3">Precio</th>
                <th class="py-3 pr-3">Stock</th>
                <th class="py-3 pr-3">Categoría</th>
                <th class="py-3 pr-3">Estado</th>
                <th class="py-3">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="repuesto in (repuestos?.data || []) as Repuesto[]"
                :key="repuesto.id"
                class="border-b border-default/60"
              >
                <td class="py-3 pr-3 font-medium">{{ repuesto.codigo }}</td>
                <td class="py-3 pr-3">{{ repuesto.nombre }}</td>
                <td class="py-3 pr-3">{{ formatMoney(repuesto.precio) }}</td>
                <td class="py-3 pr-3">
                  <span :class="repuesto.stock <= repuesto.stockMinimo ? 'text-error font-medium' : ''">
                    {{ repuesto.stock }}
                  </span>
                </td>
                <td class="py-3 pr-3">{{ repuesto.categoria || '—' }}</td>
                <td class="py-3 pr-3">
                  <UBadge :color="repuesto.activo ? 'success' : 'neutral'" variant="subtle">
                    {{ repuesto.activo ? 'Activo' : 'Inactivo' }}
                  </UBadge>
                </td>
                <td class="py-3 flex gap-2">
                  <UButton size="xs" variant="ghost" icon="i-lucide-pencil" :to="route('repuestos.edit', repuesto.id)" />
                  <UButton size="xs" color="error" variant="ghost" icon="i-lucide-trash" @click="destroy(repuesto.id)" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <AppPagination :meta="repuestos?.meta" />
      </UCard>
    </template>
  </AppDashboardPanel>
</template>
