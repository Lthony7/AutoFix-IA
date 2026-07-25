<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

interface ServicioItem {
  nombre: string
  precio: number
}

interface HistorialItem {
  id: string
  numero: string
  estado: string
  estadoLabel: string
  vehiculoPlaca: string | null
  vehiculoDescripcion: string | null
  tipoFalla: string | null
  diagnosticoTecnico: string | null
  kilometrajeIngreso: number | null
  servicios: ServicioItem[]
  avancesRecientes: Array<{ mensaje: string; createdAt: string }>
  createdAt: string
}

const page = usePage()
const historial = computed(() => (page.props as any).historial)
const rows = computed(() => (historial.value?.data || []) as HistorialItem[])

const formatMoney = (value: number) =>
  new Intl.NumberFormat('es-EC', { style: 'currency', currency: 'USD' }).format(value)

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
  <AppDashboardPanel id="portal-historial">
    <template #header>
      <UDashboardNavbar title="Historial de servicios">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="space-y-4">
        <UCard v-for="item in rows" :key="item.id">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <p class="text-base font-semibold">{{ item.numero }}</p>
              <p class="text-sm text-muted">
                {{ item.vehiculoPlaca || 'Sin placa' }}
                <span v-if="item.vehiculoDescripcion"> — {{ item.vehiculoDescripcion }}</span>
              </p>
            </div>
            <div class="text-right space-y-1">
              <UBadge :color="estadoColor(item.estado) as any" variant="subtle">
                {{ item.estadoLabel }}
              </UBadge>
              <p class="text-xs text-muted">{{ item.createdAt }}</p>
            </div>
          </div>

          <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Falla / tipo</p>
              <p class="mt-1">{{ item.tipoFalla || '—' }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Kilometraje ingreso</p>
              <p class="mt-1">{{ item.kilometrajeIngreso ?? '—' }}</p>
            </div>
            <div class="md:col-span-2">
              <p class="text-xs uppercase tracking-wide text-muted">Diagnóstico técnico</p>
              <p class="mt-1 whitespace-pre-wrap">{{ item.diagnosticoTecnico || 'Pendiente de diagnóstico' }}</p>
            </div>
          </div>

          <div v-if="item.servicios.length" class="mt-4">
            <p class="text-xs uppercase tracking-wide text-muted mb-2">Servicios realizados</p>
            <ul class="space-y-1 text-sm">
              <li v-for="(servicio, idx) in item.servicios" :key="idx" class="flex justify-between gap-3">
                <span>{{ servicio.nombre }}</span>
                <span class="text-muted">{{ formatMoney(servicio.precio) }}</span>
              </li>
            </ul>
          </div>

          <div v-if="item.avancesRecientes.length" class="mt-4">
            <p class="text-xs uppercase tracking-wide text-muted mb-2">Últimos avances</p>
            <ul class="space-y-2 text-sm">
              <li
                v-for="(avance, idx) in item.avancesRecientes"
                :key="idx"
                class="rounded-md border border-default/50 bg-elevated/30 p-2"
              >
                <p>{{ avance.mensaje }}</p>
                <p class="mt-1 text-xs text-muted">{{ avance.createdAt }}</p>
              </li>
            </ul>
          </div>
        </UCard>

        <UCard v-if="!rows.length">
          <p class="text-sm text-muted text-center py-4">Aún no tienes historial de servicios.</p>
        </UCard>

        <AppPagination :meta="historial?.meta" />
      </div>
    </template>
  </AppDashboardPanel>
</template>
