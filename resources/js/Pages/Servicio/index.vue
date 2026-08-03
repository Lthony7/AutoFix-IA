<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import ServicioFichaSlideover, { type ServicioFicha } from '../../components/ServicioFichaSlideover.vue'
import MetricStatCards, { type MetricCard } from '../../components/MetricStatCards.vue'
import ModulePanel from '../../components/ModulePanel.vue'

const page = usePage()
const servicios = computed(() => (page.props as any).servicios)
const stats = computed(() => (page.props as any).stats || {})

const fichaOpen = ref(false)
const servicioSeleccionado = ref<ServicioFicha | null>(null)

const formatMoney = (value: number) =>
  new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(value)

const abrirFicha = (servicio: ServicioFicha) => {
  servicioSeleccionado.value = servicio
  fichaOpen.value = true
}

watch(servicios, (lista) => {
  if (!servicioSeleccionado.value || !fichaOpen.value) return
  const actualizado = (lista?.data || []).find((s: ServicioFicha) => s.id === servicioSeleccionado.value?.id)
  if (actualizado) servicioSeleccionado.value = actualizado
  else {
    fichaOpen.value = false
    servicioSeleccionado.value = null
  }
})

const metricCards = computed((): MetricCard[] => [
  {
    key: 'total',
    title: 'Total servicios',
    value: stats.value.total ?? 0,
    icon: 'i-lucide-list-checks',
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
    key: 'avgPrice',
    title: 'Precio promedio',
    value: formatMoney(Number(stats.value.avgPrice ?? 0)),
    icon: 'i-lucide-banknote',
    tone: 'lime'
  }
])
</script>

<template>
  <AppDashboardPanel id="servicios">
    <template #header>
      <UDashboardNavbar title="Servicios">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="space-y-4">
        <MetricStatCards :cards="metricCards" :columns="4" />

        <ModulePanel title="Servicios">
          <template #actions>
            <UButton
              icon="i-lucide-plus"
              label="Nuevo servicio"
              color="success"
              :to="route('servicios.create')"
            />
          </template>

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
                  v-for="servicio in (servicios?.data || []) as ServicioFicha[]"
                  :key="servicio.id"
                  class="border-b border-default/60 cursor-pointer hover:bg-elevated/40 transition-colors"
                  @click="abrirFicha(servicio)"
                >
                  <td class="py-3 pr-3 font-medium">{{ servicio.nombre }}</td>
                  <td class="py-3 pr-3">{{ servicio.descripcion || '—' }}</td>
                  <td class="py-3 pr-3">{{ formatMoney(servicio.precioBase) }}</td>
                  <td class="py-3 pr-3">
                    <span
                      class="autofix-badge-solid"
                      :class="servicio.activo ? 'autofix-badge-solid--ok' : 'autofix-badge-solid--neutral'"
                    >
                      {{ servicio.activo ? 'Activo' : 'Inactivo' }}
                    </span>
                  </td>
                  <td class="py-3" @click.stop>
                    <UButton
                      size="xs"
                      variant="soft"
                      icon="i-lucide-panel-right-open"
                      label="Gestionar"
                      @click="abrirFicha(servicio)"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <AppPagination :meta="servicios?.meta" />
        </ModulePanel>

        <ServicioFichaSlideover
          v-model:open="fichaOpen"
          :servicio="servicioSeleccionado"
          @deleted="servicioSeleccionado = null"
        />
      </div>
    </template>
  </AppDashboardPanel>
</template>
