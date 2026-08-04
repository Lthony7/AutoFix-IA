<script setup lang="ts">
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import MetricStatCards, { type MetricCard } from '../../../components/MetricStatCards.vue'
import ModulePanel from '../../../components/ModulePanel.vue'

interface Presupuesto {
  id: string
  numero: string
  estado: string
  estadoLabel: string
  vehiculoPlaca: string | null
  total: number
  validoHasta: string | null
  createdAt: string
}

const page = usePage()
const presupuestos = computed(() => (page.props as any).presupuestos)
const rows = computed(() => (presupuestos.value?.data || []) as Presupuesto[])
const stats = computed(() => (page.props as any).stats || {})

const formatMoney = (value: number) =>
  new Intl.NumberFormat('es-EC', { style: 'currency', currency: 'USD' }).format(value)

const badgeClass = (estado: string) => {
  const map: Record<string, string> = {
    borrador: 'autofix-badge-solid--neutral',
    guardado: 'autofix-badge-solid--warn',
    vinculado_cita: 'autofix-badge-solid--ok',
    vencido: 'autofix-badge-solid--warn',
    cancelado: 'autofix-badge-solid--danger'
  }
  return map[estado] || 'autofix-badge-solid--neutral'
}

const metricCards = computed((): MetricCard[] => [
  {
    key: 'total',
    title: 'Total',
    value: stats.value.total ?? 0,
    icon: 'i-lucide-file-stack',
    tone: 'green'
  },
  {
    key: 'guardado',
    title: 'Activos',
    value: stats.value.guardado ?? 0,
    icon: 'i-lucide-bookmark',
    tone: 'blue'
  },
  {
    key: 'vinculado',
    title: 'Con cita',
    value: stats.value.vinculado ?? 0,
    icon: 'i-lucide-calendar-check',
    tone: 'ok'
  },
  {
    key: 'cancelado',
    title: 'Cancelados / vencidos',
    value: (stats.value.cancelado ?? 0) + (stats.value.vencido ?? 0),
    icon: 'i-lucide-ban',
    tone: 'danger'
  }
])

const cancelar = (p: Presupuesto) => {
  if (!confirm(`¿Cancelar el presupuesto ${p.numero}?`)) return
  router.post(route('portal.presupuestos.cancelar', p.id))
}
</script>

<template>
  <AppDashboardPanel id="portal-presupuestos">
    <template #header>
      <UDashboardNavbar title="Presupuestos">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="space-y-4">
        <MetricStatCards :cards="metricCards" :columns="4" />

        <ModulePanel title="Mis presupuestos">
          <template #actions>
            <UButton
              icon="i-lucide-plus"
              label="Nuevo presupuesto"
              color="success"
              :to="route('portal.presupuestos.create')"
            />
          </template>

          <p class="text-sm text-muted mb-4">
            Arma un estimado con servicios del taller y repuestos del inventario. Luego puedes usarlo al agendar tu cita.
          </p>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left border-b border-default">
                  <th class="py-3 pr-3">Número</th>
                  <th class="py-3 pr-3">Fecha</th>
                  <th class="py-3 pr-3">Vehículo</th>
                  <th class="py-3 pr-3">Estado</th>
                  <th class="py-3 pr-3">Válido hasta</th>
                  <th class="py-3 pr-3">Total</th>
                  <th class="py-3">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="p in rows" :key="p.id" class="border-b border-default/60">
                  <td class="py-3 pr-3 font-medium">{{ p.numero }}</td>
                  <td class="py-3 pr-3">{{ p.createdAt }}</td>
                  <td class="py-3 pr-3">{{ p.vehiculoPlaca || '—' }}</td>
                  <td class="py-3 pr-3">
                    <span class="autofix-badge-solid" :class="badgeClass(p.estado)">{{ p.estadoLabel }}</span>
                  </td>
                  <td class="py-3 pr-3">{{ p.validoHasta || '—' }}</td>
                  <td class="py-3 pr-3 font-medium">{{ formatMoney(p.total) }}</td>
                  <td class="py-3">
                    <div class="flex flex-wrap gap-1">
                      <UButton size="xs" variant="soft" label="Ver" :to="route('portal.presupuestos.show', p.id)" />
                      <UButton
                        v-if="p.estado === 'guardado' || p.estado === 'borrador'"
                        size="xs"
                        variant="ghost"
                        color="error"
                        label="Cancelar"
                        @click="cancelar(p)"
                      />
                    </div>
                  </td>
                </tr>
                <tr v-if="!rows.length">
                  <td colspan="7" class="py-8 text-center text-muted">
                    Aún no tienes presupuestos.
                    <UButton
                      class="ml-2"
                      size="xs"
                      variant="soft"
                      label="Crear uno"
                      :to="route('portal.presupuestos.create')"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <AppPagination :meta="presupuestos?.meta" />
        </ModulePanel>
      </div>
    </template>
  </AppDashboardPanel>
</template>
