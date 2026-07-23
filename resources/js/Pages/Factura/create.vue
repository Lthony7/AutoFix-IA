<script setup lang="ts">
import { reactive, computed, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'

interface DetallePreview {
  descripcion: string
  tipo: string
  cantidad: number
  precio_unitario: number
  subtotal: number
}

interface OrdenOption {
  id: string
  label: string
  subtotal: number
  iva: number
  total: number
  detalles: DetallePreview[]
}

const page = usePage()
const ordenes = computed(() => ((page.props as any).ordenes || []) as OrdenOption[])
const ivaRate = computed(() => Number((page.props as any).ivaRate ?? 0.15))
const serieDefault = computed(() => String((page.props as any).serieDefault || 'F001'))
const ordenPreseleccionada = computed(() => String((page.props as any).ordenTrabajoId || ''))

const backendErrors = computed(() => page.props.errors || {})
const errors = computed(() => {
  const result: Record<string, string> = {}
  Object.keys(backendErrors.value).forEach((key) => {
    const error = (backendErrors.value as any)[key]
    result[key] = Array.isArray(error) ? error[0] : error
  })
  return result
})

const isLoading = ref(false)
const state = reactive({
  ordenTrabajoId: ordenPreseleccionada.value,
  serie: serieDefault.value,
  fechaEmision: new Date().toISOString().slice(0, 10),
  descuento: 0,
  observaciones: '',
  estado: 'emitida'
})

const ordenSeleccionada = computed(() =>
  ordenes.value.find(o => o.id === state.ordenTrabajoId) || null
)

const preview = computed(() => {
  const orden = ordenSeleccionada.value
  if (!orden) {
    return { subtotal: 0, iva: 0, descuento: 0, total: 0, detalles: [] as DetallePreview[] }
  }

  const descuento = Math.max(0, Number(state.descuento) || 0)
  const base = Math.max(0, orden.subtotal - descuento)
  const iva = Number((base * ivaRate.value).toFixed(2))
  const total = Number((base + iva).toFixed(2))

  return {
    subtotal: orden.subtotal,
    iva,
    descuento,
    total,
    detalles: orden.detalles || []
  }
})

watch(
  () => state.ordenTrabajoId,
  () => {
    state.descuento = 0
  }
)

const formatMoney = (value: number) =>
  new Intl.NumberFormat('es-EC', { style: 'currency', currency: 'USD' }).format(value)

const handleSubmit = () => {
  isLoading.value = true
  router.post(route('facturas.store'), {
    ordenTrabajoId: state.ordenTrabajoId,
    serie: state.serie,
    fechaEmision: state.fechaEmision,
    descuento: state.descuento,
    observaciones: state.observaciones || null,
    estado: state.estado
  }, {
    onFinish: () => { isLoading.value = false }
  })
}
</script>

<template>
  <AppDashboardPanel id="factura-create">
    <template #header>
      <UDashboardNavbar title="Generar factura">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <UCard class="xl:col-span-1">
          <form class="grid grid-cols-1 gap-4" @submit.prevent="handleSubmit">
            <FormField label="Orden de trabajo" name="ordenTrabajoId" required :error="errors.ordenTrabajoId || errors.orden_trabajo_id">
              <USelect
                v-model="state.ordenTrabajoId"
                :items="ordenes.map(o => ({ label: o.label, value: o.id }))"
                placeholder="OT sin factura con ítems"
                class="w-full"
              />
            </FormField>
            <FormField label="Serie" name="serie" :error="errors.serie">
              <UInput v-model="state.serie" class="w-full" />
            </FormField>
            <FormField label="Fecha de emisión" name="fechaEmision" required :error="errors.fechaEmision || errors.fecha_emision">
              <UInput v-model="state.fechaEmision" type="date" class="w-full" />
            </FormField>
            <FormField label="Descuento" name="descuento" :error="errors.descuento">
              <UInput v-model.number="state.descuento" type="number" min="0" step="0.01" class="w-full" />
            </FormField>
            <FormField label="Observaciones" name="observaciones" :error="errors.observaciones">
              <UTextarea v-model="state.observaciones" :rows="3" class="w-full" />
            </FormField>
            <div class="flex gap-3">
              <UButton type="submit" label="Generar" :loading="isLoading" :disabled="!state.ordenTrabajoId" />
              <UButton variant="ghost" color="neutral" label="Cancelar" :to="route('facturas.index')" />
            </div>
          </form>
        </UCard>

        <UCard class="xl:col-span-2">
          <h3 class="font-medium mb-3">Vista previa</h3>
          <p v-if="!ordenSeleccionada" class="text-sm text-muted">
            Selecciona una orden para ver líneas e IVA ({{ (ivaRate * 100).toFixed(0) }}%).
          </p>
          <template v-else>
            <div class="overflow-x-auto mb-4">
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
                    v-for="(d, idx) in preview.detalles"
                    :key="idx"
                    class="border-b border-default/50"
                  >
                    <td class="py-2 pr-2">{{ d.descripcion }}</td>
                    <td class="py-2 pr-2 capitalize">{{ d.tipo }}</td>
                    <td class="py-2 pr-2">{{ d.cantidad }}</td>
                    <td class="py-2 pr-2">{{ formatMoney(Number(d.precio_unitario)) }}</td>
                    <td class="py-2">{{ formatMoney(Number(d.subtotal)) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="grid grid-cols-2 gap-2 text-sm max-w-sm ml-auto">
              <span class="text-muted">Subtotal</span>
              <span class="text-right font-medium">{{ formatMoney(preview.subtotal) }}</span>
              <span class="text-muted">Descuento</span>
              <span class="text-right">{{ formatMoney(preview.descuento) }}</span>
              <span class="text-muted">IVA</span>
              <span class="text-right">{{ formatMoney(preview.iva) }}</span>
              <span class="font-medium">Total</span>
              <span class="text-right font-semibold">{{ formatMoney(preview.total) }}</span>
            </div>
          </template>
        </UCard>
      </div>
    </template>
  </AppDashboardPanel>
</template>
