<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import ClienteFichaSlideover, { type ClienteFicha } from '../../components/ClienteFichaSlideover.vue'
import MetricStatCards, { type MetricCard } from '../../components/MetricStatCards.vue'
import ModulePanel from '../../components/ModulePanel.vue'

const page = usePage()
const customers = computed(() => (page.props as any).customers)
const stats = computed(() => (page.props as any).stats || {})

const fichaOpen = ref(false)
const clienteSeleccionado = ref<ClienteFicha | null>(null)

const abrirFicha = (cliente: ClienteFicha) => {
  clienteSeleccionado.value = cliente
  fichaOpen.value = true
}

watch(customers, (lista) => {
  if (!clienteSeleccionado.value || !fichaOpen.value) return
  const actualizado = (lista?.data || []).find((c: ClienteFicha) => c.id === clienteSeleccionado.value?.id)
  if (actualizado) clienteSeleccionado.value = actualizado
  else {
    fichaOpen.value = false
    clienteSeleccionado.value = null
  }
})

const metricCards = computed((): MetricCard[] => [
  {
    key: 'total',
    title: 'Total clientes',
    value: stats.value.total ?? 0,
    icon: 'i-lucide-users',
    tone: 'green'
  },
  {
    key: 'active',
    title: 'Clientes activos',
    value: stats.value.active ?? 0,
    icon: 'i-lucide-user-check',
    tone: 'blue'
  },
  {
    key: 'inactive',
    title: 'Clientes inactivos',
    value: stats.value.inactive ?? 0,
    icon: 'i-lucide-user-x',
    tone: 'purple'
  },
  {
    key: 'month',
    title: 'Clientes del mes',
    value: stats.value.month ?? 0,
    icon: 'i-lucide-calendar-plus',
    tone: 'lime'
  }
])
</script>

<template>
  <AppDashboardPanel id="clientes">
    <template #header>
      <UDashboardNavbar title="Clientes">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="space-y-4">
        <MetricStatCards :cards="metricCards" :columns="4" />

        <ModulePanel title="Clientes">
          <template #actions>
            <UButton
              icon="i-lucide-plus"
              label="Nuevo cliente"
              color="success"
              :to="route('clientes.create')"
            />
          </template>

          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left border-b border-default">
                  <th class="py-3 pr-3">Cliente</th>
                  <th class="py-3 pr-3">Documento</th>
                  <th class="py-3 pr-3">Teléfono</th>
                  <th class="py-3 pr-3">Email</th>
                  <th class="py-3 pr-3">Vehículos</th>
                  <th class="py-3 pr-3">Estado</th>
                  <th class="py-3">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="cliente in (customers?.data || []) as ClienteFicha[]"
                  :key="cliente.id"
                  class="border-b border-default/60 cursor-pointer hover:bg-elevated/40 transition-colors"
                  @click="abrirFicha(cliente)"
                >
                  <td class="py-3 pr-3 font-medium">{{ cliente.nombreCompleto }}</td>
                  <td class="py-3 pr-3">{{ cliente.numeroDocumento }}</td>
                  <td class="py-3 pr-3">{{ cliente.telefono }}</td>
                  <td class="py-3 pr-3">{{ cliente.email }}</td>
                  <td class="py-3 pr-3">{{ cliente.vehiculos?.length ?? 0 }}</td>
                  <td class="py-3 pr-3">
                    <span
                      class="autofix-badge-solid"
                      :class="cliente.estado ? 'autofix-badge-solid--ok' : 'autofix-badge-solid--neutral'"
                    >
                      {{ cliente.estado ? 'Activo' : 'Inactivo' }}
                    </span>
                  </td>
                  <td class="py-3" @click.stop>
                    <div class="flex gap-1.5">
                      <button
                        type="button"
                        class="autofix-action-btn"
                        title="Ver ficha"
                        @click="abrirFicha(cliente)"
                      >
                        <UIcon name="i-lucide-eye" class="size-4" />
                      </button>
                      <Link
                        :href="route('clientes.edit', cliente.id)"
                        class="autofix-action-btn"
                        title="Editar"
                      >
                        <UIcon name="i-lucide-pencil" class="size-4" />
                      </Link>
                    </div>
                  </td>
                </tr>
                <tr v-if="!(customers?.data || []).length">
                  <td colspan="7" class="py-8 text-center text-muted">No hay clientes registrados.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <template #footer>
            <AppPagination :meta="customers?.meta" />
          </template>
        </ModulePanel>
      </div>

      <ClienteFichaSlideover v-model:open="fichaOpen" :cliente="clienteSeleccionado" />
    </template>
  </AppDashboardPanel>
</template>
