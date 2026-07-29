<script setup lang="ts">
import { computed, reactive, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'

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

const estadoColor = (estado: string) => {
  const map: Record<string, string> = {
    borrador: 'neutral',
    guardado: 'info',
    vinculado_cita: 'success',
    vencido: 'warning',
    cancelado: 'error'
  }
  return map[estado] || 'neutral'
}

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
      <UCard>
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
          <div class="flex items-end">
            <UButton label="Filtrar" @click="buscar" />
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
                  <UBadge :color="estadoColor(p.estado) as any" variant="subtle">{{ p.estadoLabel }}</UBadge>
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
      </UCard>
    </template>
  </AppDashboardPanel>
</template>
