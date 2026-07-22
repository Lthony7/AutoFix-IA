<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

interface Stats {
  totalOrdenes: number
  totalIngresos: number
  ordenesPorEstado: { estado: string, label: string, total: number }[]
  ingresosPorFecha: { fecha: string, total: number }[]
  serviciosTop: { nombre: string, total: number, ingresos: number }[]
  sugerenciasIa: { estado: string, label: string, total: number }[]
  sugerenciasIaResumen: { simulados: number, reales: number, total: number }
}

const page = usePage()
const stats = computed(() => (page.props as any).stats as Stats)

const formatMoney = (value: number) =>
  new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(value)
</script>

<template>
  <UDashboardPanel id="reportes">
    <template #header>
      <UDashboardNavbar title="Reportes">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <UCard>
            <p class="text-sm text-muted">Total órdenes</p>
            <p class="text-2xl font-semibold">{{ stats?.totalOrdenes ?? 0 }}</p>
          </UCard>
          <UCard>
            <p class="text-sm text-muted">Ingresos (pagados)</p>
            <p class="text-2xl font-semibold">{{ formatMoney(stats?.totalIngresos ?? 0) }}</p>
          </UCard>
          <UCard>
            <p class="text-sm text-muted">Diagnósticos IA</p>
            <p class="text-2xl font-semibold">{{ stats?.sugerenciasIaResumen?.total ?? 0 }}</p>
          </UCard>
          <UCard>
            <p class="text-sm text-muted">IA simulados / reales</p>
            <p class="text-2xl font-semibold">
              {{ stats?.sugerenciasIaResumen?.simulados ?? 0 }} / {{ stats?.sugerenciasIaResumen?.reales ?? 0 }}
            </p>
          </UCard>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <UCard>
            <h3 class="font-semibold mb-4 flex items-center gap-2">
              <UIcon name="i-lucide-clipboard-list" class="size-4" />
              Órdenes por estado
            </h3>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="text-left border-b border-default">
                    <th class="py-2 pr-3">Estado</th>
                    <th class="py-2">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="row in stats?.ordenesPorEstado || []"
                    :key="row.estado"
                    class="border-b border-default/60"
                  >
                    <td class="py-2 pr-3">{{ row.label }}</td>
                    <td class="py-2 font-medium">{{ row.total }}</td>
                  </tr>
                  <tr v-if="!(stats?.ordenesPorEstado?.length)">
                    <td colspan="2" class="py-4 text-muted text-center">Sin datos</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </UCard>

          <UCard>
            <h3 class="font-semibold mb-4 flex items-center gap-2">
              <UIcon name="i-lucide-brain" class="size-4" />
              Sugerencias IA por estado
            </h3>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="text-left border-b border-default">
                    <th class="py-2 pr-3">Estado</th>
                    <th class="py-2">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="row in stats?.sugerenciasIa || []"
                    :key="row.estado"
                    class="border-b border-default/60"
                  >
                    <td class="py-2 pr-3">{{ row.label }}</td>
                    <td class="py-2 font-medium">{{ row.total }}</td>
                  </tr>
                  <tr v-if="!(stats?.sugerenciasIa?.length)">
                    <td colspan="2" class="py-4 text-muted text-center">Sin datos</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </UCard>
        </div>

        <UCard>
          <h3 class="font-semibold mb-4 flex items-center gap-2">
            <UIcon name="i-lucide-trending-up" class="size-4" />
            Ingresos por fecha (últimos 30 días)
          </h3>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left border-b border-default">
                  <th class="py-2 pr-3">Fecha</th>
                  <th class="py-2">Total</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="row in stats?.ingresosPorFecha || []"
                  :key="row.fecha"
                  class="border-b border-default/60"
                >
                  <td class="py-2 pr-3">{{ row.fecha }}</td>
                  <td class="py-2 font-medium">{{ formatMoney(row.total) }}</td>
                </tr>
                <tr v-if="!(stats?.ingresosPorFecha?.length)">
                  <td colspan="2" class="py-4 text-muted text-center">Sin ingresos registrados</td>
                </tr>
              </tbody>
            </table>
          </div>
        </UCard>

        <UCard>
          <h3 class="font-semibold mb-4 flex items-center gap-2">
            <UIcon name="i-lucide-wrench" class="size-4" />
            Servicios más solicitados
          </h3>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left border-b border-default">
                  <th class="py-2 pr-3">Servicio</th>
                  <th class="py-2 pr-3">Cantidad</th>
                  <th class="py-2">Ingresos</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="row in stats?.serviciosTop || []"
                  :key="row.nombre"
                  class="border-b border-default/60"
                >
                  <td class="py-2 pr-3">{{ row.nombre }}</td>
                  <td class="py-2 pr-3">{{ row.total }}</td>
                  <td class="py-2 font-medium">{{ formatMoney(row.ingresos) }}</td>
                </tr>
                <tr v-if="!(stats?.serviciosTop?.length)">
                  <td colspan="3" class="py-4 text-muted text-center">Sin datos</td>
                </tr>
              </tbody>
            </table>
          </div>
        </UCard>
      </div>
    </template>
  </UDashboardPanel>
</template>
