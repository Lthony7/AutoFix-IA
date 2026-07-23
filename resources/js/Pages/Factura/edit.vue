<script setup lang="ts">
import { reactive, computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'

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
  ordenNumero: string | null
  clienteNombre: string | null
  fechaEmision: string | null
  subtotal: number
  iva: number
  descuento: number
  total: number
  estado: string
  observaciones: string | null
  detalles?: Detalle[]
}

const page = usePage()
const factura = (page.props as any).factura as Factura
const estados = computed(() => ((page.props as any).estados || []) as { value: string, label: string }[])
const ivaRate = computed(() => Number((page.props as any).ivaRate ?? 0.15))

const backendErrors = computed(() => page.props.errors || {})
const errors = computed(() => {
  const result: Record<string, string> = {}
  Object.keys(backendErrors.value).forEach((key) => {
    const error = (backendErrors.value as any)[key]
    result[key] = Array.isArray(error) ? error[0] : error
  })
  return result
})

const isEmitidaOPagada = computed(() => ['emitida', 'pagada'].includes(factura.estado))

const isLoading = ref(false)
const state = reactive({
  fechaEmision: factura.fechaEmision || '',
  descuento: factura.descuento,
  estado: factura.estado,
  observaciones: factura.observaciones || ''
})

const previewTotales = computed(() => {
  if (isEmitidaOPagada.value && state.descuento === factura.descuento) {
    return {
      subtotal: factura.subtotal,
      iva: factura.iva,
      descuento: factura.descuento,
      total: factura.total
    }
  }

  const descuento = Math.max(0, Number(state.descuento) || 0)
  const base = Math.max(0, factura.subtotal - descuento)
  const iva = Number((base * ivaRate.value).toFixed(2))
  return {
    subtotal: factura.subtotal,
    iva,
    descuento,
    total: Number((base + iva).toFixed(2))
  }
})

const formatMoney = (value: number) =>
  new Intl.NumberFormat('es-EC', { style: 'currency', currency: 'USD' }).format(value)

const handleSubmit = () => {
  isLoading.value = true
  router.put(route('facturas.update', factura.id), {
    fechaEmision: state.fechaEmision,
    descuento: state.descuento,
    estado: state.estado,
    observaciones: state.observaciones || null
  }, {
    onFinish: () => { isLoading.value = false }
  })
}
</script>

<template>
  <AppDashboardPanel id="factura-edit">
    <template #header>
      <UDashboardNavbar :title="`Editar factura ${factura.numero}`">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <UCard class="xl:col-span-1">
          <p class="text-sm text-muted mb-4">
            OT: <span class="text-foreground font-medium">{{ factura.ordenNumero }}</span>
            · {{ factura.clienteNombre || '—' }}
          </p>
          <form class="grid grid-cols-1 gap-4" @submit.prevent="handleSubmit">
            <FormField label="Fecha de emisión" name="fechaEmision" :error="errors.fechaEmision || errors.fecha_emision">
              <UInput v-model="state.fechaEmision" type="date" class="w-full" />
            </FormField>
            <FormField label="Descuento" name="descuento" :error="errors.descuento">
              <UInput
                v-model.number="state.descuento"
                type="number"
                min="0"
                step="0.01"
                class="w-full"
                :disabled="factura.estado === 'anulada'"
              />
            </FormField>
            <FormField label="Estado" name="estado" :error="errors.estado">
              <USelect
                v-model="state.estado"
                :items="estados.map(e => ({ label: e.label, value: e.value }))"
                class="w-full"
              />
            </FormField>
            <FormField label="Observaciones" name="observaciones" :error="errors.observaciones">
              <UTextarea v-model="state.observaciones" :rows="3" class="w-full" />
            </FormField>
            <div class="flex gap-3">
              <UButton type="submit" label="Guardar" :loading="isLoading" />
              <UButton variant="ghost" color="neutral" label="Cancelar" :to="route('facturas.show', factura.id)" />
            </div>
          </form>
        </UCard>

        <UCard class="xl:col-span-2">
          <h3 class="font-medium mb-2">Líneas (solo lectura)</h3>
          <p class="text-xs text-muted mb-4">
            Las líneas se generan desde la OT al crear la factura.
          </p>
          <div class="overflow-x-auto mb-4">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left border-b border-default">
                  <th class="py-2 pr-2">Descripción</th>
                  <th class="py-2 pr-2">Tipo</th>
                  <th class="py-2 pr-2">Cant.</th>
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
                  <td class="py-2">{{ formatMoney(d.subtotal) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="grid grid-cols-2 gap-2 text-sm max-w-xs ml-auto">
            <span class="text-muted">Subtotal</span>
            <span class="text-right">{{ formatMoney(previewTotales.subtotal) }}</span>
            <span class="text-muted">Descuento</span>
            <span class="text-right">{{ formatMoney(previewTotales.descuento) }}</span>
            <span class="text-muted">IVA</span>
            <span class="text-right">{{ formatMoney(previewTotales.iva) }}</span>
            <span class="font-semibold">Total</span>
            <span class="text-right font-semibold">{{ formatMoney(previewTotales.total) }}</span>
          </div>
        </UCard>
      </div>
    </template>
  </AppDashboardPanel>
</template>
