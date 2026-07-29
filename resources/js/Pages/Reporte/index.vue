<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

interface Stats {
  totalOrdenes: number
  totalIngresos: number
  ordenesPorEstado: { estado: string, label: string, total: number }[]
  ingresosPorFecha: { fecha: string, total: number }[]
  serviciosTop: { nombre: string, total: number, ingresos: number }[]
  repuestosTop: { nombre: string, cantidad: number, ordenes: number, ingresos: number }[]
  inventarioResumen: {
    totalItems: number
    activos: number
    stockBajo: number
    sinStock: number
    valorStock: number
  }
  stockBajo: {
    codigo: string
    nombre: string
    categoria: string | null
    stock: number
    stockMinimo: number
    precio: number
  }[]
  vehiculosPorCliente: { cliente: string, vehiculos: number, ordenes: number }[]
  sugerenciasIa: { estado: string, label: string, total: number }[]
  sugerenciasIaResumen: { simulados: number, reales: number, total: number }
  generadoEn?: string
}

const page = usePage()
const stats = computed(() => (page.props as any).stats as Stats)
const role = computed(() => String((page.props as any).auth?.user?.role || ''))
const puedeExportar = computed(() => role.value === 'administrador' || role.value === 'recepcionista')

const formatMoney = (value: number) =>
  new Intl.NumberFormat('es-EC', { style: 'currency', currency: 'USD' }).format(value)
</script>

<template>
  <AppDashboardPanel id="reportes">
    <template #header>
      <UDashboardNavbar title="Reportes">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <div v-if="puedeExportar" class="flex gap-2">
            <UButton
              icon="i-lucide-package"
              label="Inventario"
              variant="soft"
              :to="route('inventario.index')"
            />
            <a :href="route('reportes.export.excel')">
              <UButton
                icon="i-lucide-file-spreadsheet"
                label="Excel"
                variant="soft"
                color="success"
              />
            </a>
            <a :href="route('reportes.export.pdf')">
              <UButton
                icon="i-lucide-file-text"
                label="PDF"
                variant="soft"
                color="error"
              />
            </a>
          </div>
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
            <p class="text-sm text-muted">Ítems inventario</p>
            <p class="text-2xl font-semibold">{{ stats?.inventarioResumen?.activos ?? 0 }}</p>
            <p class="text-xs text-muted mt-1">
              Valor stock: {{ formatMoney(stats?.inventarioResumen?.valorStock ?? 0) }}
            </p>
          </UCard>
          <UCard>
            <p class="text-sm text-muted">Stock bajo / sin stock</p>
            <p class="text-2xl font-semibold">
              {{ stats?.inventarioResumen?.stockBajo ?? 0 }}
              <span class="text-base text-muted font-normal">
                / {{ stats?.inventarioResumen?.sinStock ?? 0 }}
              </span>
            </p>
          </UCard>
        </div>

        <UCard>
          <div class="flex items-center justify-between gap-3 mb-4">
            <h3 class="font-semibold flex items-center gap-2">
              <UIcon name="i-lucide-package-x" class="size-4" />
              Inventario con stock bajo
            </h3>
            <UButton
              size="xs"
              variant="soft"
              icon="i-lucide-package"
              label="Gestionar inventario"
              :to="route('inventario.index', { stock_bajo: '1' })"
            />
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left border-b border-default">
                  <th class="py-2 pr-3">Código</th>
                  <th class="py-2 pr-3">Ítem</th>
                  <th class="py-2 pr-3">Categoría</th>
                  <th class="py-2 pr-3">Stock</th>
                  <th class="py-2">Mínimo</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="row in stats?.stockBajo || []"
                  :key="row.codigo"
                  class="border-b border-default/60"
                >
                  <td class="py-2 pr-3 font-medium">{{ row.codigo }}</td>
                  <td class="py-2 pr-3">{{ row.nombre }}</td>
                  <td class="py-2 pr-3">{{ row.categoria || '—' }}</td>
                  <td class="py-2 pr-3 text-error font-medium">{{ row.stock }}</td>
                  <td class="py-2">{{ row.stockMinimo }}</td>
                </tr>
                <tr v-if="!(stats?.stockBajo?.length)">
                  <td colspan="5" class="py-4 text-muted text-center">Sin alertas de stock</td>
                </tr>
              </tbody>
            </table>
          </div>
        </UCard>

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

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <UCard>
            <h3 class="font-semibold mb-4 flex items-center gap-2">
              <UIcon name="i-lucide-package" class="size-4" />
              Inventario más usado en órdenes
            </h3>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="text-left border-b border-default">
                    <th class="py-2 pr-3">Ítem</th>
                    <th class="py-2 pr-3">Cantidad</th>
                    <th class="py-2 pr-3">Órdenes</th>
                    <th class="py-2">Ingresos</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="row in stats?.repuestosTop || []"
                    :key="row.nombre"
                    class="border-b border-default/60"
                  >
                    <td class="py-2 pr-3">{{ row.nombre }}</td>
                    <td class="py-2 pr-3">{{ row.cantidad }}</td>
                    <td class="py-2 pr-3">{{ row.ordenes }}</td>
                    <td class="py-2 font-medium">{{ formatMoney(row.ingresos) }}</td>
                  </tr>
                  <tr v-if="!(stats?.repuestosTop?.length)">
                    <td colspan="4" class="py-4 text-muted text-center">Sin datos</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </UCard>

          <UCard>
            <h3 class="font-semibold mb-4 flex items-center gap-2">
              <UIcon name="i-lucide-users" class="size-4" />
              Vehículos atendidos por cliente
            </h3>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="text-left border-b border-default">
                    <th class="py-2 pr-3">Cliente</th>
                    <th class="py-2 pr-3">Vehículos</th>
                    <th class="py-2">Órdenes</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="row in stats?.vehiculosPorCliente || []"
                    :key="row.cliente"
                    class="border-b border-default/60"
                  >
                    <td class="py-2 pr-3">{{ row.cliente }}</td>
                    <td class="py-2 pr-3">{{ row.vehiculos }}</td>
                    <td class="py-2 font-medium">{{ row.ordenes }}</td>
                  </tr>
                  <tr v-if="!(stats?.vehiculosPorCliente?.length)">
                    <td colspan="3" class="py-4 text-muted text-center">Sin datos</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </UCard>
        </div>
      </div>
    </template>
  </AppDashboardPanel>
</template>
