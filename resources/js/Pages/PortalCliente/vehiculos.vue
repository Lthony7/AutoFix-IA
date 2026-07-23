<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

interface Vehiculo {
  id: string
  placa: string
  marca: string
  modelo: string
  anio: number
  color: string | null
  kilometraje: number
  activo: boolean
}

const page = usePage()
const vehiculos = computed(() => (page.props as any).vehiculos)
const rows = computed(() => (vehiculos.value?.data || []) as Vehiculo[])
</script>

<template>
  <AppDashboardPanel id="portal-vehiculos">
    <template #header>
      <UDashboardNavbar title="Mis vehículos">
        <template #leading>
          <UDashboardSidebarCollapse />
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
                <th class="py-3 pr-3">Año</th>
                <th class="py-3 pr-3">Color</th>
                <th class="py-3 pr-3">Kilometraje</th>
                <th class="py-3">Estado</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="vehiculo in rows"
                :key="vehiculo.id"
                class="border-b border-default/60"
              >
                <td class="py-3 pr-3 font-medium">{{ vehiculo.placa }}</td>
                <td class="py-3 pr-3">{{ vehiculo.marca }} {{ vehiculo.modelo }}</td>
                <td class="py-3 pr-3">{{ vehiculo.anio }}</td>
                <td class="py-3 pr-3">{{ vehiculo.color || '—' }}</td>
                <td class="py-3 pr-3">{{ vehiculo.kilometraje.toLocaleString() }} km</td>
                <td class="py-3">
                  <UBadge :color="vehiculo.activo ? 'success' : 'neutral'" variant="subtle">
                    {{ vehiculo.activo ? 'Activo' : 'Inactivo' }}
                  </UBadge>
                </td>
              </tr>
              <tr v-if="!rows.length">
                <td colspan="6" class="py-6 text-center text-muted">No tienes vehículos registrados.</td>
              </tr>
            </tbody>
          </table>
        </div>
        <AppPagination :meta="vehiculos?.meta" />
      </UCard>
    </template>
  </AppDashboardPanel>
</template>
