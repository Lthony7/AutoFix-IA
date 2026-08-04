<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
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

        <UAlert
          color="info"
          variant="subtle"
          icon="i-lucide-sparkles"
          title="Flujo del diagnóstico"
          description="Escoge el vehículo → tipo de falla (eléctrico, motor, etc.) → prioridad → reporte breve → Generar. La IA responde con observaciones, mecánico, servicios y repuestos."
        />

        <UAlert
          color="warning"
          variant="subtle"
          icon="i-lucide-triangle-alert"
          title="Aviso importante"
          description="La información generada por Inteligencia Artificial es únicamente una sugerencia inicial. El diagnóstico final debe ser realizado y confirmado por un mecánico autorizado."
        />

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
                  <th class="py-3 pr-3">Servicio</th>
                  <th class="py-3 pr-3">Estado</th>
                  <th class="py-3">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="item in (diagnosticos?.data || []) as Diagnostico[]"
                  :key="item.id"
                  class="border-b border-default/60"
                >
                  <td class="py-3 pr-3 font-medium">{{ item.ordenNumero || '—' }}</td>
                  <td class="py-3 pr-3">{{ item.clienteNombre || '—' }}</td>
                  <td class="py-3 pr-3">{{ item.vehiculoPlaca || '—' }}</td>
                  <td class="py-3 pr-3 capitalize">{{ item.prioridad || '—' }}</td>
                  <td class="py-3 pr-3">{{ item.especialidadRecomendada || '—' }}</td>
                  <td class="py-3 pr-3">{{ item.servicioRecomendado || '—' }}</td>
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
                  <td class="py-3 flex gap-2">
                    <UButton
                      size="xs"
                      variant="ghost"
                      icon="i-lucide-eye"
                      :to="route('diagnosticos-ia.show', item.ordenTrabajoId)"
                    />
                    <UButton
                      size="xs"
                      variant="ghost"
                      icon="i-lucide-check-circle"
                      :to="route('diagnosticos-ia.review', item.ordenTrabajoId)"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
            <p v-if="!(diagnosticos?.data || []).length" class="py-8 text-center text-muted">
              No hay diagnósticos IA registrados.
            </p>
          </div>
          <AppPagination :meta="diagnosticos?.meta" />
        </ModulePanel>
      </div>
    </template>
  </AppDashboardPanel>
</template>
