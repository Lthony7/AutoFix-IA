<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import ModulePanel from '../../components/ModulePanel.vue'
import OrdenFichaSlideover, { type OrdenFicha } from '../../components/OrdenFichaSlideover.vue'
import SemaforoFilterCards, { type SemaforoCard } from '../../components/SemaforoFilterCards.vue'

interface MecanicoOption {
  id: string
  label: string
}

const page = usePage()
const ordenes = computed(() => (page.props as any).ordenes)
const mecanicos = computed(() => ((page.props as any).mecanicos || []) as MecanicoOption[])
const filtersProp = computed(() => (page.props as any).filters || {})
const resumenSemaforo = computed(() => ((page.props as any).resumenSemaforo || {
  ok: 0,
  atencion: 0,
  critico: 0
}) as { ok: number, atencion: number, critico: number })

const role = computed(() => (page.props as any).auth?.user?.role as string | undefined)
const canDelete = computed(() => role.value === 'administrador' || role.value === 'recepcionista')
const canFacturar = computed(() => role.value === 'administrador' || role.value === 'recepcionista')
const canCambiarEstado = computed(() =>
  role.value === 'administrador' || role.value === 'recepcionista' || role.value === 'mecanico'
)
const canEditarDiagnostico = computed(() => role.value === 'administrador' || role.value === 'mecanico')

const semaforo = computed(() => (filtersProp.value.semaforo || '') as string)

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

const badgeClass = (estado: string) => {
  const map: Record<string, string> = {
    pendiente: 'autofix-badge-solid--danger',
    en_diagnostico: 'autofix-badge-solid--warn',
    en_reparacion: 'autofix-badge-solid--warn',
    finalizada: 'autofix-badge-solid--ok',
    entregada: 'autofix-badge-solid--ok',
    cancelada: 'autofix-badge-solid--neutral'
  }
  return map[estado] || 'autofix-badge-solid--neutral'
}

const ordenesFilterUrl = (key: string) => {
  if (semaforo.value === key) {
    return route('ordenes.index')
  }
  return route('ordenes.index') + `?semaforo=${encodeURIComponent(key)}`
}

const semaforoCards = computed((): SemaforoCard[] => [
  {
    key: 'ok',
    title: 'Cerradas OK',
    value: resumenSemaforo.value.ok,
    icon: 'i-lucide-circle-check',
    tone: 'ok',
    to: ordenesFilterUrl('ok')
  },
  {
    key: 'atencion',
    title: 'En curso',
    value: resumenSemaforo.value.atencion,
    icon: 'i-lucide-wrench',
    tone: 'warn',
    to: ordenesFilterUrl('atencion')
  },
  {
    key: 'critico',
    title: 'Pendientes',
    value: resumenSemaforo.value.critico,
    icon: 'i-lucide-clock-alert',
    tone: 'danger',
    to: ordenesFilterUrl('critico')
  }
])
</script>

<template>
  <AppDashboardPanel id="ordenes">
    <template #header>
      <UDashboardNavbar title="Órdenes">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <AppCreateButton v-if="role !== 'mecanico'" :to="route('ordenes.create')" label="Nueva orden" />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="space-y-4">
        <SemaforoFilterCards
          :cards="semaforoCards"
          :model-value="semaforo || null"
        />

        <ModulePanel title="Órdenes">
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
                  v-for="orden in (ordenes?.data || []) as Array<OrdenFicha & { tieneDiagnostico?: boolean }>"
                  :key="orden.id"
                  class="border-b border-default/60 cursor-pointer hover:bg-elevated/40 transition-colors"
                  @click="abrirFicha(orden)"
                >
                  <td class="py-3 pr-3 font-medium">{{ orden.numero }}</td>
                  <td class="py-3 pr-3">{{ orden.clienteNombre || '—' }}</td>
                  <td class="py-3 pr-3">{{ orden.vehiculoPlaca || '—' }}</td>
                  <td class="py-3 pr-3">
                    <span class="autofix-badge-solid" :class="badgeClass(orden.estado)">
                      {{ orden.estadoLabel }}
                    </span>
                  </td>
                  <td class="py-3 pr-3">{{ orden.mecanicoNombre || '—' }}</td>
                  <td class="py-3" @click.stop>
                    <div class="flex gap-1.5">
                      <Link
                        v-if="!orden.tieneDiagnostico"
                        :href="route('diagnosticos-ia.create', { ordenTrabajoId: orden.id })"
                        class="autofix-action-btn autofix-action-btn--primary"
                        title="Diagnosticar con IA"
                      >
                        <UIcon name="i-lucide-sparkles" class="size-4" />
                      </Link>
                      <button
                        type="button"
                        class="autofix-action-btn"
                        title="Gestionar"
                        @click="abrirFicha(orden)"
                      >
                        <UIcon name="i-lucide-panel-right-open" class="size-4" />
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="!(ordenes?.data || []).length">
                  <td colspan="6" class="py-8 text-center text-muted">No hay órdenes con ese filtro.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <template #footer>
            <AppPagination :meta="ordenes?.meta" :query="{ semaforo: semaforo || undefined }" />
          </template>
        </ModulePanel>
      </div>

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
