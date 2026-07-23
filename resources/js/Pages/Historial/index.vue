<script setup lang="ts">
import { computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

interface VehiculoHistorial {
  id: string
  placa: string
  marca: string
  modelo: string
  anio: number
  clienteNombre: string | null
  ordenesCount: number
}

const page = usePage()
const vehiculos = computed(() => (page.props as any).vehiculos)
const filters = computed(() => (page.props as any).filters || { q: '' })
const q = ref(filters.value.q || '')

const buscar = () => {
  router.get(route('historial.index'), { q: q.value || undefined }, {
    preserveState: true,
    replace: true
  })
}
</script>

<template>
  <AppDashboardPanel id="historial-index">
    <template #header>
      <UDashboardNavbar title="Historial de vehículos">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <UCard>
        <form class="flex flex-wrap gap-3 mb-4" @submit.prevent="buscar">
          <UInput
            v-model="q"
            placeholder="Buscar por placa, marca, modelo o cliente"
            class="w-full max-w-md"
            icon="i-lucide-search"
          />
          <UButton type="submit" label="Buscar" />
        </form>

        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left border-b border-default">
                <th class="py-3 pr-3">Placa</th>
                <th class="py-3 pr-3">Vehículo</th>
                <th class="py-3 pr-3">Cliente</th>
                <th class="py-3 pr-3">Órdenes</th>
                <th class="py-3">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in (vehiculos?.data || []) as VehiculoHistorial[]"
                :key="item.id"
                class="border-b border-default/60"
              >
                <td class="py-3 pr-3 font-medium">{{ item.placa }}</td>
                <td class="py-3 pr-3">{{ item.marca }} {{ item.modelo }} ({{ item.anio }})</td>
                <td class="py-3 pr-3">{{ item.clienteNombre || '—' }}</td>
                <td class="py-3 pr-3">{{ item.ordenesCount }}</td>
                <td class="py-3">
                  <UButton
                    size="xs"
                    variant="soft"
                    icon="i-lucide-history"
                    label="Ver historial"
                    :to="route('historial.vehiculo', item.id)"
                  />
                </td>
              </tr>
            </tbody>
          </table>
          <p v-if="!(vehiculos?.data || []).length" class="py-8 text-center text-muted">
            No hay vehículos que coincidan con la búsqueda.
          </p>
        </div>
        <AppPagination :meta="vehiculos?.meta" :query="{ q: filters.q }" />
      </UCard>
    </template>
  </AppDashboardPanel>
</template>
