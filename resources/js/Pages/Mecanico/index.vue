<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import MecanicoFichaSlideover, { type MecanicoFicha } from '../../components/MecanicoFichaSlideover.vue'
import MetricStatCards, { type MetricCard } from '../../components/MetricStatCards.vue'
import ModulePanel from '../../components/ModulePanel.vue'

interface Mecanico extends MecanicoFicha {}

const page = usePage()
const mecanicos = computed(() => (page.props as any).mecanicos)
const stats = computed(() => (page.props as any).stats || {})

const fichaOpen = ref(false)
const mecanicoFicha = ref<MecanicoFicha | null>(null)

const abrirFicha = (item: Mecanico) => {
  mecanicoFicha.value = item
  fichaOpen.value = true
}

watch(mecanicos, (lista) => {
  if (!mecanicoFicha.value || !fichaOpen.value) return
  const actualizado = (lista?.data || []).find((m: Mecanico) => m.id === mecanicoFicha.value?.id)
  if (actualizado) mecanicoFicha.value = actualizado
  else {
    fichaOpen.value = false
    mecanicoFicha.value = null
  }
})

const metricCards = computed((): MetricCard[] => [
  {
    key: 'total',
    title: 'Total mecánicos',
    value: stats.value.total ?? 0,
    icon: 'i-lucide-wrench',
    tone: 'green'
  },
  {
    key: 'active',
    title: 'Activos',
    value: stats.value.active ?? 0,
    icon: 'i-lucide-user-check',
    tone: 'blue'
  },
  {
    key: 'inactive',
    title: 'Inactivos',
    value: stats.value.inactive ?? 0,
    icon: 'i-lucide-user-x',
    tone: 'purple'
  },
  {
    key: 'withSchedule',
    title: 'Con horario',
    value: stats.value.withSchedule ?? 0,
    icon: 'i-lucide-clock',
    tone: 'lime'
  }
])
</script>

<template>
  <AppDashboardPanel id="mecanicos">
    <template #header>
      <UDashboardNavbar title="Mecánicos">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <AppCreateButton :to="route('mecanicos.create')" label="Nuevo mecánico" />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <div class="space-y-4">
        <MetricStatCards :cards="metricCards" :columns="4" />

        <ModulePanel title="Mecánicos">
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left border-b border-default">
                  <th class="py-3 pr-3">Nombre</th>
                  <th class="py-3 pr-3">Documento</th>
                  <th class="py-3 pr-3">Especialidad</th>
                  <th class="py-3 pr-3">Horario</th>
                  <th class="py-3 pr-3">Estado</th>
                  <th class="py-3">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="item in (mecanicos?.data || []) as Mecanico[]"
                  :key="item.id"
                  class="border-b border-default/60 cursor-pointer hover:bg-elevated/40 transition-colors"
                  @click="abrirFicha(item)"
                >
                  <td class="py-3 pr-3 font-medium">{{ item.nombreCompleto }}</td>
                  <td class="py-3 pr-3">{{ item.documento }}</td>
                  <td class="py-3 pr-3">{{ item.especialidad }}</td>
                  <td class="py-3 pr-3">{{ item.horarioDisponible || '—' }}</td>
                  <td class="py-3 pr-3">
                    <span
                      class="autofix-badge-solid"
                      :class="item.activo ? 'autofix-badge-solid--ok' : 'autofix-badge-solid--neutral'"
                    >
                      {{ item.activo ? 'Activo' : 'Inactivo' }}
                    </span>
                  </td>
                  <td class="py-3" @click.stop>
                    <UButton
                      size="xs"
                      variant="soft"
                      icon="i-lucide-panel-right-open"
                      label="Gestionar"
                      @click="abrirFicha(item)"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <AppPagination :meta="mecanicos?.meta" />
        </ModulePanel>

        <MecanicoFichaSlideover
          v-model:open="fichaOpen"
          :mecanico="mecanicoFicha"
          @deleted="mecanicoFicha = null"
        />
      </div>
    </template>
  </AppDashboardPanel>
</template>
