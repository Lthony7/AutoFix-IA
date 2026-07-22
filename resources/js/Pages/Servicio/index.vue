<script setup lang="ts">
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

interface Servicio {
  id: string
  nombre: string
  descripcion: string | null
  precioBase: number
  activo: boolean
}

const page = usePage()
const servicios = computed(() => (page.props as any).servicios)

const formatMoney = (value: number) =>
  new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(value)

const destroy = (id: string) => {
  if (!confirm('¿Eliminar este servicio?')) return
  router.delete(route('servicios.destroy', id))
}
</script>

<template>
  <UDashboardPanel id="servicios">
    <template #header>
      <UDashboardNavbar title="Servicios">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton icon="i-lucide-plus" label="Nuevo servicio" :to="route('servicios.create')" />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <UCard>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left border-b border-default">
                <th class="py-3 pr-3">Nombre</th>
                <th class="py-3 pr-3">Descripción</th>
                <th class="py-3 pr-3">Precio base</th>
                <th class="py-3 pr-3">Estado</th>
                <th class="py-3">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="servicio in (servicios?.data || []) as Servicio[]"
                :key="servicio.id"
                class="border-b border-default/60"
              >
                <td class="py-3 pr-3 font-medium">{{ servicio.nombre }}</td>
                <td class="py-3 pr-3">{{ servicio.descripcion || '—' }}</td>
                <td class="py-3 pr-3">{{ formatMoney(servicio.precioBase) }}</td>
                <td class="py-3 pr-3">
                  <UBadge :color="servicio.activo ? 'success' : 'neutral'" variant="subtle">
                    {{ servicio.activo ? 'Activo' : 'Inactivo' }}
                  </UBadge>
                </td>
                <td class="py-3 flex gap-2">
                  <UButton size="xs" variant="ghost" icon="i-lucide-pencil" :to="route('servicios.edit', servicio.id)" />
                  <UButton size="xs" color="error" variant="ghost" icon="i-lucide-trash" @click="destroy(servicio.id)" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </UCard>
    </template>
  </UDashboardPanel>
</template>
