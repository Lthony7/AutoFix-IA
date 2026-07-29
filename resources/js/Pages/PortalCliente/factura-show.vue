<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

interface Detalle {
  id: string
  descripcion: string
  tipo: string
  tipoLabel: string
  cantidad: number
  precioUnitario: number
  subtotal: number
}

interface Factura {
  id: string
  numero: string
  serie: string
  ordenNumero: string | null
  ordenId: string | null
  vehiculoPlaca: string | null
  fechaEmision: string | null
  subtotal: number
  iva: number
  descuento: number
  total: number
  estado: string
  estadoLabel: string
  observaciones: string | null
  detalles: Detalle[]
  totalServicios: number
  totalPiezas: number
}

const page = usePage()
const factura = computed(() => (page.props as any).factura as Factura)
const ivaRate = computed(() => Number((page.props as any).ivaRate ?? 0.15))

const formatMoney = (value: number) =>
  new Intl.NumberFormat('es-EC', { style: 'currency', currency: 'USD' }).format(value)

const servicios = computed(() => factura.value.detalles.filter(d => d.tipo === 'servicio'))
const piezas = computed(() => factura.value.detalles.filter(d => d.tipo === 'repuesto'))
</script>

<template>
  <AppDashboardPanel id="portal-factura-show">
    <template #header>
      <UDashboardNavbar :title="`Factura ${factura.numero}`">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton
            v-if="factura.ordenId"
            variant="soft"
            icon="i-lucide-clipboard-list"
            label="Ver orden"
            :to="route('portal.mis-ordenes.show', factura.ordenId)"
          />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <UCard class="max-w-4xl">
        <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
          <div>
            <p class="text-xs uppercase tracking-wide text-muted">AUTOFIX IA</p>
            <h2 class="text-xl font-semibold">{{ factura.numero }}</h2>
            <p class="text-sm text-muted">Serie {{ factura.serie }} · {{ factura.fechaEmision }}</p>
          </div>
          <UBadge color="success" variant="subtle" size="lg">{{ factura.estadoLabel }}</UBadge>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm mb-6">
          <div>
            <p class="text-muted">Orden</p>
            <p class="font-medium">{{ factura.ordenNumero || '—' }}</p>
          </div>
          <div>
            <p class="text-muted">Vehículo</p>
            <p class="font-medium">{{ factura.vehiculoPlaca || '—' }}</p>
          </div>
          <div>
            <p class="text-muted">Total</p>
            <p class="font-medium">{{ formatMoney(factura.total) }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
          <div class="rounded-lg border border-default/60 p-3">
            <p class="text-xs text-muted">Servicios / reparaciones</p>
            <p class="text-lg font-semibold">{{ formatMoney(factura.totalServicios) }}</p>
          </div>
          <div class="rounded-lg border border-default/60 p-3">
            <p class="text-xs text-muted">Piezas colocadas</p>
            <p class="text-lg font-semibold">{{ formatMoney(factura.totalPiezas) }}</p>
          </div>
        </div>

        <h3 class="font-semibold mb-2">Servicios y reparaciones</h3>
        <div class="overflow-x-auto mb-6">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left border-b border-default">
                <th class="py-2 pr-2">Descripción</th>
                <th class="py-2 pr-2">Cant.</th>
                <th class="py-2 pr-2">P. unit.</th>
                <th class="py-2">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in servicios" :key="d.id" class="border-b border-default/50">
                <td class="py-2 pr-2">{{ d.descripcion }}</td>
                <td class="py-2 pr-2">{{ d.cantidad }}</td>
                <td class="py-2 pr-2">{{ formatMoney(d.precioUnitario) }}</td>
                <td class="py-2">{{ formatMoney(d.subtotal) }}</td>
              </tr>
              <tr v-if="!servicios.length">
                <td colspan="4" class="py-4 text-center text-muted">Sin servicios facturados</td>
              </tr>
            </tbody>
          </table>
        </div>

        <h3 class="font-semibold mb-2">Piezas</h3>
        <div class="overflow-x-auto mb-6">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left border-b border-default">
                <th class="py-2 pr-2">Descripción</th>
                <th class="py-2 pr-2">Cant.</th>
                <th class="py-2 pr-2">P. unit.</th>
                <th class="py-2">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in piezas" :key="d.id" class="border-b border-default/50">
                <td class="py-2 pr-2">{{ d.descripcion }}</td>
                <td class="py-2 pr-2">{{ d.cantidad }}</td>
                <td class="py-2 pr-2">{{ formatMoney(d.precioUnitario) }}</td>
                <td class="py-2">{{ formatMoney(d.subtotal) }}</td>
              </tr>
              <tr v-if="!piezas.length">
                <td colspan="4" class="py-4 text-center text-muted">Sin piezas facturadas</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="grid grid-cols-2 gap-2 text-sm max-w-xs ml-auto mb-4">
          <span class="text-muted">Subtotal</span>
          <span class="text-right">{{ formatMoney(factura.subtotal) }}</span>
          <span class="text-muted">Descuento</span>
          <span class="text-right">{{ formatMoney(factura.descuento) }}</span>
          <span class="text-muted">IVA ({{ (ivaRate * 100).toFixed(0) }}%)</span>
          <span class="text-right">{{ formatMoney(factura.iva) }}</span>
          <span class="font-semibold">Total</span>
          <span class="text-right font-semibold">{{ formatMoney(factura.total) }}</span>
        </div>

        <div v-if="factura.observaciones" class="border-t border-default pt-4">
          <p class="text-xs uppercase tracking-wide text-muted mb-1">Observaciones</p>
          <p class="text-sm whitespace-pre-wrap">{{ factura.observaciones }}</p>
        </div>
      </UCard>
    </template>
  </AppDashboardPanel>
</template>
