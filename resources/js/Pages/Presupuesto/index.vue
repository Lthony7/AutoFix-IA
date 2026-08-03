<script setup lang="ts">
import { computed, reactive, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'
import MetricStatCards, { type MetricCard } from '../../components/MetricStatCards.vue'
import ModulePanel from '../../components/ModulePanel.vue'

interface Presupuesto {
  id: string
  numero: string
  estado: string
  estadoLabel: string
  clienteNombre: string | null
  vehiculoPlaca: string | null
  total: number
  validoHasta: string | null
  createdAt: string
}

const page = usePage()
const presupuestos = computed(() => (page.props as any).presupuestos)
const rows = computed(() => (presupuestos.value?.data || []) as Presupuesto[])
const filtersProp = computed(() => (page.props as any).filters || {})
const stats = computed(() => (page.props as any).stats || {})

const filters = reactive({
  q: filtersProp.value.q || '',
  estado: filtersProp.value.estado || ''
})

watch(filtersProp, (f) => {
  filters.q = f.q || ''
  filters.estado = f.estado || ''
})

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

const filterUrl = (estado: string) => {
  const params = new URLSearchParams()
  if (filters.q) params.set('q', filters.q)
  if (estado) params.set('estado', estado)
  const qs = params.toString()
  return route('presupuestos.index') + (qs ? `?${qs}` : '')
}

const metricCards = computed((): MetricCard[] => [
  {
    key: 'guardado',
    title: 'Guardados',
    value: stats.value.guardado ?? 0,
    icon: 'i-lucide-bookmark',
    tone: 'blue',
    to: filterUrl('guardado')
  },
  {
    key: 'vinculado_cita',
    title: 'Vinculados',
    value: stats.value.vinculado ?? 0,
    icon: 'i-lucide-calendar-check',
    tone: 'ok',
    to: filterUrl('vinculado_cita')
  },
  {
    key: 'vencido',
    title: 'Vencidos',
    value: stats.value.vencido ?? 0,
    icon: 'i-lucide-calendar-x',
    tone: 'warn',
    to: filterUrl('vencido')
  },
  {
    key: 'cancelado',
    title: 'Cancelados',
    value: stats.value.cancelado ?? 0,
    icon: 'i-lucide-ban',
    tone: 'danger',
    to: filterUrl('cancelado')
  }
])

const buscar = () => {
  router.get(route('presupuestos.index'), {
    q: filters.q || undefined,
    estado: filters.estado || undefined
  }, { preserveState: true, replace: true })
}
</script>

<template>
  <AppDashboardPanel id="presupuestos-staff">
    <template #header>
      <UDashboardNavbar title="Presupuestos">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="space-y-4">
        <MetricStatCards
          :cards="metricCards"
          :columns="4"
          :model-value="filters.estado || null"
        />

        <ModulePanel title="Presupuestos">
          <div class="flex flex-wrap gap-3 mb-4">
            <FormField label="Buscar" name="q" class="min-w-56 flex-1">
              <UInput v-model="filters.q" placeholder="Número, cliente o placa" class="w-full" @keyup.enter="buscar" />
            </FormField>
            <FormField label="Estado" name="estado" class="min-w-44">
              <USelect
                v-model="filters.estado"
                :items="[
                  { label: 'Todos', value: '' },
                  { label: 'Guardado', value: 'guardado' },
                  { label: 'Vinculado a cita', value: 'vinculado_cita' },
                  { label: 'Vencido', value: 'vencido' },
                  { label: 'Cancelado', value: 'cancelado' }
                ]"
                class="w-full"
              />
            </FormField>
            <div class="flex items-end gap-2">
              <UButton label="Filtrar" @click="buscar" />
              <UButton
                v-if="filters.estado || filters.q"
                variant="soft"
                label="Limpiar"
                :to="route('presupuestos.index')"
              />
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left border-b border-default">
                  <th class="py-3 pr-3">Número</th>
                  <th class="py-3 pr-3">Cliente</th>
                  <th class="py-3 pr-3">Vehículo</th>
                  <th class="py-3 pr-3">Estado</th>
                  <th class="py-3 pr-3">Total</th>
                  <th class="py-3 pr-3">Fecha</th>
                  <th class="py-3">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="p in rows" :key="p.id" class="border-b border-default/60">
                  <td class="py-3 pr-3 font-medium">{{ p.numero }}</td>
                  <td class="py-3 pr-3">{{ p.clienteNombre || '—' }}</td>
                  <td class="py-3 pr-3">{{ p.vehiculoPlaca || '—' }}</td>
                  <td class="py-3 pr-3">
                    <span class="autofix-badge-solid" :class="badgeClass(p.estado)">{{ p.estadoLabel }}</span>
                  </td>
                  <td class="py-3 pr-3">{{ formatMoney(p.total) }}</td>
                  <td class="py-3 pr-3">{{ p.createdAt }}</td>
                  <td class="py-3">
                    <UButton size="xs" variant="soft" label="Ver" :to="route('presupuestos.show', p.id)" />
                  </td>
                </tr>
                <tr v-if="!rows.length">
                  <td colspan="7" class="py-8 text-center text-muted">No hay presupuestos.</td>
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
