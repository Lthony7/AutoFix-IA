<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

interface Detalle {
  id: string
  descripcion: string
  tipo: string
  cantidad: number
  precioUnitario: number
  subtotal: number
}

interface Factura {
  id: string
  numero: string
  serie: string
  ordenNumero: string | null
  clienteNombre: string | null
  vehiculoPlaca: string | null
  fechaEmision: string | null
  subtotal: number
  iva: number
  descuento: number
  total: number
  estado: string
  estadoLabel: string
  observaciones: string | null
  tienePago: boolean
  detalles?: Detalle[]
}

const page = usePage()
const factura = computed(() => (page.props as any).factura as Factura)
const ivaRate = computed(() => Number((page.props as any).ivaRate ?? 0.15))

const formatMoney = (value: number) =>
  new Intl.NumberFormat('es-EC', { style: 'currency', currency: 'USD' }).format(value)

const estadoColor = (estado: string) => {
  const map: Record<string, string> = {
    borrador: 'neutral',
    emitida: 'info',
    pagada: 'success',
    anulada: 'error'
  }
  return map[estado] || 'neutral'
}
</script>

<template>
  <AppDashboardPanel id="factura-show">
    <template #header>
      <UDashboardNavbar :title="`Factura ${factura.numero}`">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <div class="flex gap-2">
            <UButton
              v-if="!factura.tienePago"
              icon="i-lucide-wallet"
              label="Registrar pago"
              :to="route('pagos.create')"
              variant="soft"
            />
            <UButton icon="i-lucide-pencil" label="Editar" :to="route('facturas.edit', factura.id)" />
            <UButton variant="ghost" color="neutral" label="Volver" :to="route('facturas.index')" />
          </div>
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
          <UBadge :color="estadoColor(factura.estado) as any" variant="subtle" size="lg">
            {{ factura.estadoLabel }}
          </UBadge>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm mb-6">
          <div>
            <p class="text-muted">Cliente</p>
            <p class="font-medium">{{ factura.clienteNombre || '—' }}</p>
          </div>
          <div>
            <p class="text-muted">Orden de trabajo</p>
            <p class="font-medium">{{ factura.ordenNumero || '—' }}</p>
          </div>
          <div>
            <p class="text-muted">Vehículo</p>
            <p class="font-medium">{{ factura.vehiculoPlaca || '—' }}</p>
          </div>
        </div>

        <div class="overflow-x-auto mb-6">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left border-b border-default">
                <th class="py-2 pr-2">Descripción</th>
                <th class="py-2 pr-2">Tipo</th>
                <th class="py-2 pr-2">Cant.</th>
                <th class="py-2 pr-2">P. unit.</th>
                <th class="py-2">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="d in (factura.detalles || [])"
                :key="d.id"
                class="border-b border-default/50"
              >
                <td class="py-2 pr-2">{{ d.descripcion }}</td>
                <td class="py-2 pr-2 capitalize">{{ d.tipo }}</td>
                <td class="py-2 pr-2">{{ d.cantidad }}</td>
                <td class="py-2 pr-2">{{ formatMoney(d.precioUnitario) }}</td>
                <td class="py-2">{{ formatMoney(d.subtotal) }}</td>
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

        <p v-if="factura.observaciones" class="text-sm text-muted border-t border-default pt-4">
          {{ factura.observaciones }}
        </p>
      </UCard>
    </template>
  </AppDashboardPanel>
</template>
