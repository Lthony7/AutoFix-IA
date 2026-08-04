<script setup lang="ts">
import { reactive, computed, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'

const page = usePage()
const pago = (page.props as any).pago
const ivaRate = computed(() => Number((page.props as any).ivaRate ?? 0.15))
const tieneFactura = computed(() => Boolean(pago.facturaId || pago.tieneFactura))

const backendErrors = computed(() => page.props.errors || {})
const errors = computed(() => {
  const result: Record<string, string> = {}
  Object.keys(backendErrors.value).forEach((key) => {
    const error = (backendErrors.value as any)[key]
    result[key] = Array.isArray(error) ? error[0] : error
  })
  return result
})

const estadoItems = [
  { label: 'Pendiente', value: 'pendiente' },
  { label: 'Pagado', value: 'pagado' },
  { label: 'Anulado', value: 'anulado' }
]

const metodoItems = [
  { label: 'Efectivo', value: 'efectivo' },
  { label: 'Tarjeta', value: 'tarjeta' },
  { label: 'Transferencia', value: 'transferencia' },
  { label: 'Otro', value: 'otro' }
]

const isLoading = ref(false)
const esCompletarCobro = computed(() => pago.estado === 'pendiente' || pago.estado === 'anulado')
const pageTitle = computed(() =>
  esCompletarCobro.value
    ? `Completar cobro — ${pago.ordenNumero || ''}`
    : `Editar pago — ${pago.ordenNumero || ''}`
)
const state = reactive({
  descuento: pago.descuento,
  estado: pago.estado,
  metodoPago: pago.metodoPago || ''
})

const valorServicios = computed(() => Number(pago.valorServicios || 0))
const valorRepuestos = computed(() => Number(pago.valorRepuestos || 0))
const subtotal = computed(() => valorServicios.value + valorRepuestos.value)

const descuentoAplicado = computed(() => {
  const d = Math.max(0, Number(state.descuento) || 0)
  return Math.min(d, subtotal.value)
})

const totalCalculado = computed(() => {
  const base = Math.max(0, subtotal.value - descuentoAplicado.value)
  if (tieneFactura.value) {
    const iva = Number((base * ivaRate.value).toFixed(2))
    return Number((base + iva).toFixed(2))
  }
  return Number(base.toFixed(2))
})

const ivaCalculado = computed(() => {
  if (!tieneFactura.value) return 0
  const base = Math.max(0, subtotal.value - descuentoAplicado.value)
  return Number((base * ivaRate.value).toFixed(2))
})

const formatMoney = (value: number) =>
  new Intl.NumberFormat('es-EC', { style: 'currency', currency: 'USD' }).format(value || 0)

watch(
  () => state.descuento,
  (d) => {
    if (Number(d) > subtotal.value) state.descuento = subtotal.value
  }
)

const handleSubmit = () => {
  isLoading.value = true
  router.put(route('pagos.update', pago.id), {
    descuento: descuentoAplicado.value,
    estado: state.estado,
    metodoPago: state.metodoPago || null
  }, {
    onFinish: () => { isLoading.value = false }
  })
}
</script>

<template>
  <AppDashboardPanel id="pago-edit">
    <template #header>
      <UDashboardNavbar :title="pageTitle">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <UCard class="w-full">
        <form class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 w-full" @submit.prevent="handleSubmit">
          <div class="md:col-span-2 xl:col-span-3 text-sm text-muted">
            Orden: <span class="font-medium text-foreground">{{ pago.ordenNumero }}</span>
            · Cliente: {{ pago.clienteNombre || '—' }}
            · Placa: {{ pago.vehiculoPlaca || '—' }}
          </div>

          <UAlert
            v-if="esCompletarCobro"
            class="md:col-span-2 xl:col-span-3"
            color="warning"
            variant="subtle"
            icon="i-lucide-wallet"
            title="Completar cobro"
            description="Marca el pago como Pagado y confirma el método para cerrar el cobro. La factura volverá a Pagada."
          />

          <UAlert
            class="md:col-span-2 xl:col-span-3"
            color="info"
            variant="subtle"
            icon="i-lucide-lock"
            title="Valores bloqueados"
            description="Servicios, repuestos y total no se editan. Solo puedes ajustar el descuento según el caso."
          />

          <div class="rounded-lg border border-default p-3">
            <p class="text-xs uppercase tracking-wide text-muted">Valor servicios</p>
            <p class="mt-1 text-sm font-semibold">{{ formatMoney(valorServicios) }}</p>
          </div>
          <div class="rounded-lg border border-default p-3">
            <p class="text-xs uppercase tracking-wide text-muted">Valor repuestos</p>
            <p class="mt-1 text-sm font-semibold">{{ formatMoney(valorRepuestos) }}</p>
          </div>

          <FormField label="Descuento" name="descuento" :error="errors.descuento" class="md:col-span-2 xl:col-span-3">
            <UInput
              v-model.number="state.descuento"
              type="number"
              min="0"
              :max="subtotal"
              step="0.01"
              class="w-full"
            />
            <p class="mt-1.5 text-xs text-muted">
              Opcional. No puede superar el subtotal ({{ formatMoney(subtotal) }}).
            </p>
          </FormField>

          <div v-if="tieneFactura" class="rounded-lg border border-default p-3">
            <p class="text-xs uppercase tracking-wide text-muted">IVA ({{ (ivaRate * 100).toFixed(0) }}%)</p>
            <p class="mt-1 text-sm font-semibold">{{ formatMoney(ivaCalculado) }}</p>
          </div>
          <div class="rounded-lg border border-default bg-elevated/40 p-3" :class="tieneFactura ? '' : 'md:col-span-2 xl:col-span-3'">
            <p class="text-xs uppercase tracking-wide text-muted">Total a pagar</p>
            <p class="mt-1 text-lg font-semibold">{{ formatMoney(totalCalculado) }}</p>
          </div>

          <FormField label="Estado" name="estado" :error="errors.estado">
            <USelect v-model="state.estado" :items="estadoItems" class="w-full" />
          </FormField>
          <FormField label="Método de pago" name="metodoPago" :error="errors.metodoPago || errors.metodo_pago">
            <USelect v-model="state.metodoPago" :items="metodoItems" placeholder="Opcional" class="w-full" />
          </FormField>
          <div class="md:col-span-2 xl:col-span-3 flex gap-3">
            <UButton
              type="submit"
              :label="esCompletarCobro ? 'Guardar cobro' : 'Actualizar'"
              :loading="isLoading"
            />
            <UButton variant="ghost" color="neutral" label="Cancelar" :to="route('pagos.index')" />
          </div>
        </form>
      </UCard>
    </template>
  </AppDashboardPanel>
</template>
