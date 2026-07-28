<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import OrdenFichaSlideover, { type OrdenFicha } from '../../components/OrdenFichaSlideover.vue'

interface MecanicoOption {
  id: string
  label: string
}

const page = usePage()
const ordenes = computed(() => (page.props as any).ordenes)
const mecanicos = computed(() => ((page.props as any).mecanicos || []) as MecanicoOption[])
const role = computed(() => (page.props as any).auth?.user?.role as string | undefined)
const canDelete = computed(() => role.value === 'administrador' || role.value === 'recepcionista')
const canFacturar = computed(() => role.value === 'administrador' || role.value === 'recepcionista')
const canCambiarEstado = computed(() =>
  role.value === 'administrador' || role.value === 'recepcionista' || role.value === 'mecanico'
)
const canEditarDiagnostico = computed(() => role.value === 'administrador' || role.value === 'mecanico')

const fichaOpen = ref(false)
const ordenSeleccionada = ref<OrdenFicha | null>(null)

const abrirFicha = (orden: OrdenFicha) => {
  ordenSeleccionada.value = orden
  fichaOpen.value = true
}

watch(ordenes, (lista) => {
  if (!ordenSeleccionada.value || !fichaOpen.value) return
  const actualizado = (lista?.data || []).find((o: OrdenFicha) => o.id === ordenSeleccionada.value?.id)
  if (actualizado) ordenSeleccionada.value = actualizado
  else {
    fichaOpen.value = false
    ordenSeleccionada.value = null
  }
})

const estadoColor = (estado: string) => {
  const map: Record<string, string> = {
    pendiente: 'warning',
    en_diagnostico: 'info',
    en_reparacion: 'primary',
    finalizada: 'success',
    entregada: 'success',
    cancelada: 'error'
  }
  return map[estado] || 'neutral'
}
</script>

<template>
  <AppDashboardPanel id="ordenes">
    <template #header>
      <UDashboardNavbar title="Órdenes de trabajo">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton
            v-if="role !== 'mecanico'"
            icon="i-lucide-plus"
            label="Nueva orden"
            :to="route('ordenes.create')"
          />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <UCard>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left border-b border-default">
                <th class="py-3 pr-3">Número</th>
                <th class="py-3 pr-3">Cliente</th>
                <th class="py-3 pr-3">Placa</th>
                <th class="py-3 pr-3">Estado</th>
                <th class="py-3 pr-3">Mecánico</th>
                <th class="py-3">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="orden in (ordenes?.data || []) as OrdenFicha[]"
                :key="orden.id"
                class="border-b border-default/60 cursor-pointer hover:bg-elevated/40 transition-colors"
                @click="abrirFicha(orden)"
              >
                <td class="py-3 pr-3 font-medium">{{ orden.numero }}</td>
                <td class="py-3 pr-3">{{ orden.clienteNombre || '—' }}</td>
                <td class="py-3 pr-3">{{ orden.vehiculoPlaca || '—' }}</td>
                <td class="py-3 pr-3">
                  <UBadge :color="estadoColor(orden.estado) as any" variant="subtle">
                    {{ orden.estadoLabel }}
                  </UBadge>
                </td>
                <td class="py-3 pr-3">{{ orden.mecanicoNombre || '—' }}</td>
                <td class="py-3" @click.stop>
                  <UButton
                    size="xs"
                    variant="soft"
                    icon="i-lucide-panel-right-open"
                    label="Gestionar"
                    @click="abrirFicha(orden)"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <AppPagination :meta="ordenes?.meta" />
      </UCard>

      <OrdenFichaSlideover
        v-model:open="fichaOpen"
        :orden="ordenSeleccionada"
        :mecanicos="mecanicos"
        :can-delete="canDelete"
        :can-facturar="canFacturar"
        :can-cambiar-estado="canCambiarEstado"
        :can-editar-diagnostico="canEditarDiagnostico"
        @deleted="ordenSeleccionada = null"
      />
    </template>
  </AppDashboardPanel>
</template>
