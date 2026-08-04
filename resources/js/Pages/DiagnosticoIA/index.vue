<script setup lang="ts">
import { computed } from 'vue'
import { router, usePage, Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import MetricStatCards, { type MetricCard } from '../../components/MetricStatCards.vue'
import ModulePanel from '../../components/ModulePanel.vue'

interface Diagnostico {
  id: string
  ordenTrabajoId: string
  ordenNumero: string | null
  clienteNombre: string | null
  vehiculoPlaca: string | null
  prioridad: string | null
  servicioRecomendado: string | null
  especialidadRecomendada: string | null
  estado: string
  estadoLabel: string
  esSimulado: boolean
  createdAt: string
}

const page = usePage()
const diagnosticos = computed(() => (page.props as any).diagnosticos)
const stats = computed(() => (page.props as any).stats || {})

const verDiagnostico = (ordenTrabajoId: string) => {
  router.get(route('diagnosticos-ia.show', ordenTrabajoId))
}

const badgeClass = (estado: string) => {
  const map: Record<string, string> = {
    generada: 'autofix-badge-solid--warn',
    en_revision: 'autofix-badge-solid--warn',
    confirmada: 'autofix-badge-solid--ok',
    modificada: 'autofix-badge-solid--ok',
    descartada: 'autofix-badge-solid--danger',
    cerrada: 'autofix-badge-solid--neutral'
  }
  return map[estado] || 'autofix-badge-solid--neutral'
}

const metricCards = computed((): MetricCard[] => [
  {
    key: 'total',
    title: 'Total',
    value: stats.value.total ?? 0,
    icon: 'i-lucide-sparkles',
    tone: 'green'
  },
  {
    key: 'pendientes',
    title: 'Pendientes',
    value: stats.value.pendientes ?? 0,
    icon: 'i-lucide-hourglass',
    tone: 'warn'
  },
  {
    key: 'confirmada',
    title: 'Confirmados',
    value: stats.value.confirmada ?? 0,
    icon: 'i-lucide-circle-check',
    tone: 'ok'
  },
  {
    key: 'descartada',
    title: 'Descartados',
    value: stats.value.descartada ?? 0,
    icon: 'i-lucide-x-circle',
    tone: 'danger'
  }
])
</script>

<template>
  <AppDashboardPanel id="diagnosticos-ia">
    <template #header>
      <UDashboardNavbar title="Diagnóstico IA">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <AppCreateButton :to="route('diagnosticos-ia.create')" label="Nuevo diagnóstico" />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="space-y-4">
        <MetricStatCards :cards="metricCards" :columns="4" />

        <ModulePanel title="Diagnósticos IA">
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left border-b border-default">
                  <th class="py-3 pr-3">OT</th>
                  <th class="py-3 pr-3">Cliente</th>
                  <th class="py-3 pr-3">Placa</th>
                  <th class="py-3 pr-3">Prioridad</th>
                  <th class="py-3 pr-3">Especialidad</th>
                  <th class="py-3 pr-3">Estado</th>
                  <th class="py-3">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="item in (diagnosticos?.data || []) as Diagnostico[]"
                  :key="item.id"
                  class="border-b border-default/60 cursor-pointer hover:bg-elevated/40 transition-colors"
                  @click="verDiagnostico(item.ordenTrabajoId)"
                >
                  <td class="py-3 pr-3 font-medium">{{ item.ordenNumero || '—' }}</td>
                  <td class="py-3 pr-3">{{ item.clienteNombre || '—' }}</td>
                  <td class="py-3 pr-3">{{ item.vehiculoPlaca || '—' }}</td>
                  <td class="py-3 pr-3 capitalize">{{ item.prioridad || '—' }}</td>
                  <td class="py-3 pr-3">{{ item.especialidadRecomendada || '—' }}</td>
                  <td class="py-3 pr-3">
                    <div class="flex items-center gap-2">
                      <span class="autofix-badge-solid" :class="badgeClass(item.estado)">
                        {{ item.estadoLabel }}
                      </span>
                      <span v-if="item.esSimulado" class="autofix-badge-solid autofix-badge-solid--neutral">
                        Mock
                      </span>
                    </div>
                  </td>
                  <td class="py-3" @click.stop>
                    <div class="flex gap-1.5">
                      <button
                        type="button"
                        class="autofix-action-btn"
                        title="Ver diagnóstico"
                        @click="verDiagnostico(item.ordenTrabajoId)"
                      >
                        <UIcon name="i-lucide-eye" class="size-4" />
                      </button>
                      <Link
                        :href="route('diagnosticos-ia.review', item.ordenTrabajoId)"
                        class="autofix-action-btn"
                        title="Revisar / confirmar"
                      >
                        <UIcon name="i-lucide-check-circle" class="size-4" />
                      </Link>
                    </div>
                  </td>
                </tr>
                <tr v-if="!(diagnosticos?.data || []).length">
                  <td colspan="7" class="py-8 text-center text-muted">No hay diagnósticos IA registrados.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <template #footer>
            <AppPagination :meta="diagnosticos?.meta" />
          </template>
        </ModulePanel>
      </div>
    </template>
  </AppDashboardPanel>
</template>
