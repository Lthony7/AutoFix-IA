<script setup lang="ts">
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

interface Vehiculo {
  id: string
  placa: string
  marca: string
  modelo: string
  anio: number
  clienteNombre?: string
  kilometraje: number
  tipoCombustible: string
  activo: boolean
}

const page = usePage()
const vehiculos = computed(() => (page.props as any).vehiculos)

const destroy = (id: string) => {
  if (!confirm('¿Eliminar este vehículo?')) return
  router.delete(route('vehiculos.destroy', id))
}
</script>

<template>
  <UDashboardPanel id="vehiculos">
    <template #header>
      <UDashboardNavbar title="Vehículos">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton icon="i-lucide-plus" label="Nuevo vehículo" :to="route('vehiculos.create')" />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <UCard>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left border-b border-default">
                <th class="py-3 pr-3">Placa</th>
                <th class="py-3 pr-3">Vehículo</th>
                <th class="py-3 pr-3">Cliente</th>
                <th class="py-3 pr-3">Km</th>
                <th class="py-3 pr-3">Combustible</th>
                <th class="py-3 pr-3">Estado</th>
                <th class="py-3">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in (vehiculos?.data || []) as Vehiculo[]" :key="item.id" class="border-b border-default/60">
                <td class="py-3 pr-3 font-medium">{{ item.placa }}</td>
                <td class="py-3 pr-3">{{ item.marca }} {{ item.modelo }} ({{ item.anio }})</td>
                <td class="py-3 pr-3">{{ item.clienteNombre || '—' }}</td>
                <td class="py-3 pr-3">{{ item.kilometraje.toLocaleString() }}</td>
                <td class="py-3 pr-3 capitalize">{{ item.tipoCombustible }}</td>
                <td class="py-3 pr-3">
                  <UBadge :color="item.activo ? 'success' : 'neutral'" variant="subtle">
                    {{ item.activo ? 'Activo' : 'Inactivo' }}
                  </UBadge>
                </td>
                <td class="py-3 flex gap-2">
                  <UButton size="xs" variant="ghost" icon="i-lucide-pencil" :to="route('vehiculos.edit', item.id)" />
                  <UButton size="xs" color="error" variant="ghost" icon="i-lucide-trash" @click="destroy(item.id)" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </UCard>
    </template>
  </UDashboardPanel>
</template>
