<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import ServicioFichaSlideover, { type ServicioFicha } from '../../components/ServicioFichaSlideover.vue'

const page = usePage()
const servicios = computed(() => (page.props as any).servicios)

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
</script>

<template>
  <AppDashboardPanel id="servicios">
    <template #header>
      <UDashboardNavbar title="Servicios">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton icon="i-lucide-plus" label="Nuevo servicio" :to="route('servicios.create')" />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <UCard>
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
                  <UBadge :color="servicio.activo ? 'success' : 'neutral'" variant="subtle">
                    {{ servicio.activo ? 'Activo' : 'Inactivo' }}
                  </UBadge>
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
      </UCard>

      <ServicioFichaSlideover
        v-model:open="fichaOpen"
        :servicio="servicioSeleccionado"
        @deleted="servicioSeleccionado = null"
      />
    </template>
  </AppDashboardPanel>
</template>
