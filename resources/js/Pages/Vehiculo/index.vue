<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import VehiculoFichaSlideover, {
  type ClienteOption,
  type VehiculoFicha
} from '../../components/VehiculoFichaSlideover.vue'
import MetricStatCards, { type MetricCard } from '../../components/MetricStatCards.vue'
import ModulePanel from '../../components/ModulePanel.vue'

const page = usePage()
const vehiculos = computed(() => (page.props as any).vehiculos)
const clientes = computed(() => ((page.props as any).clientes || []) as ClienteOption[])
const stats = computed(() => (page.props as any).stats || {})

const fichaOpen = ref(false)
const vehiculoSeleccionado = ref<VehiculoFicha | null>(null)

const abrirFicha = (vehiculo: VehiculoFicha) => {
  vehiculoSeleccionado.value = vehiculo
  fichaOpen.value = true
}

watch(vehiculos, (lista) => {
  if (!vehiculoSeleccionado.value || !fichaOpen.value) return
  const actualizado = (lista?.data || []).find((v: VehiculoFicha) => v.id === vehiculoSeleccionado.value?.id)
  if (actualizado) vehiculoSeleccionado.value = actualizado
  else {
    fichaOpen.value = false
    vehiculoSeleccionado.value = null
  }
})

const metricCards = computed((): MetricCard[] => [
  {
    key: 'total',
    title: 'Total vehículos',
    value: stats.value.total ?? 0,
    icon: 'i-lucide-car',
    tone: 'green'
  },
  {
    key: 'active',
    title: 'Activos',
    value: stats.value.active ?? 0,
    icon: 'i-lucide-circle-check',
    tone: 'blue'
  },
  {
    key: 'inactive',
    title: 'Inactivos',
    value: stats.value.inactive ?? 0,
    icon: 'i-lucide-circle-off',
    tone: 'purple'
  },
  {
    key: 'month',
    title: 'Del mes',
    value: stats.value.month ?? 0,
    icon: 'i-lucide-calendar-plus',
    tone: 'lime'
  }
])
</script>

<template>
  <AppDashboardPanel id="vehiculos">
    <template #header>
      <UDashboardNavbar title="Vehículos">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <AppCreateButton :to="route('vehiculos.create')" label="Nuevo vehículo" />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <div class="space-y-4">
        <MetricStatCards :cards="metricCards" :columns="4" />

        <ModulePanel title="Vehículos">
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
                <tr
                  v-for="item in (vehiculos?.data || []) as VehiculoFicha[]"
                  :key="item.id"
                  class="border-b border-default/60 cursor-pointer hover:bg-elevated/40 transition-colors"
                  @click="abrirFicha(item)"
                >
                  <td class="py-3 pr-3 font-medium">{{ item.placa }}</td>
                  <td class="py-3 pr-3">{{ item.marca }} {{ item.modelo }} ({{ item.anio }})</td>
                  <td class="py-3 pr-3">{{ item.clienteNombre || '—' }}</td>
                  <td class="py-3 pr-3">{{ Number(item.kilometraje || 0).toLocaleString() }}</td>
                  <td class="py-3 pr-3 capitalize">{{ item.tipoCombustible }}</td>
                  <td class="py-3 pr-3">
                    <span
                      class="autofix-badge-solid"
                      :class="item.activo ? 'autofix-badge-solid--ok' : 'autofix-badge-solid--neutral'"
                    >
                      {{ item.activo ? 'Activo' : 'Inactivo' }}
                    </span>
                  </td>
                  <td class="py-3 flex gap-2" @click.stop>
                    <UButton
                      size="xs"
                      variant="ghost"
                      icon="i-lucide-eye"
                      title="Ver ficha"
                      @click="abrirFicha(item)"
                    />
                    <UButton
                      size="xs"
                      variant="ghost"
                      icon="i-lucide-history"
                      title="Historial"
                      :to="route('historial.vehiculo', item.id)"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <AppPagination :meta="vehiculos?.meta" />
        </ModulePanel>

        <VehiculoFichaSlideover
          v-model:open="fichaOpen"
          :vehiculo="vehiculoSeleccionado"
          :clientes="clientes"
        />
      </div>
    </template>
  </AppDashboardPanel>
</template>
