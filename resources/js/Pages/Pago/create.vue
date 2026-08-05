<script setup lang="ts">
import { reactive, computed, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'

interface OrdenOption {
  id: string
  label: string
  valorServicios: number
  valorRepuestos: number
  descuento: number
  total: number
  tieneFactura: boolean
  facturaNumero: string | null
  pagoId?: string | null
  pagoEstado?: string | null
}

const page = usePage()
const ordenes = computed(() => ((page.props as any).ordenes || []) as OrdenOption[])
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
const state = reactive({
  ordenTrabajoId: String((page.props as any).ordenTrabajoId || ''),
  descuento: 0,
  estado: 'pendiente',
  metodoPago: ''
})

const ordenSeleccionada = computed(() =>
  ordenes.value.find(o => o.id === state.ordenTrabajoId) || null
)

const esCompletarCobro = computed(() => Boolean(ordenSeleccionada.value?.pagoId))

// Llegó con la OT preseleccionada desde el detalle de la factura → datos fijos de esa factura
const vieneDeFactura = computed(() => Boolean(state.ordenTrabajoId && ordenSeleccionada.value))

const valorServicios = computed(() => ordenSeleccionada.value?.valorServicios ?? 0)
const valorRepuestos = computed(() => ordenSeleccionada.value?.valorRepuestos ?? 0)
const subtotal = computed(() => valorServicios.value + valorRepuestos.value)

const descuentoAplicado = computed(() => {
  const d = Math.max(0, Number(state.descuento) || 0)
  return Math.min(d, subtotal.value)
})

const totalCalculado = computed(() => {
  if (!ordenSeleccionada.value) return 0
  const base = Math.max(0, subtotal.value - descuentoAplicado.value)
  if (ordenSeleccionada.value.tieneFactura) {
    const iva = Number((base * ivaRate.value).toFixed(2))
    return Number((base + iva).toFixed(2))
  }
  return Number(base.toFixed(2))
})

const ivaCalculado = computed(() => {
  if (!ordenSeleccionada.value?.tieneFactura) return 0
  const base = Math.max(0, subtotal.value - descuentoAplicado.value)
  return Number((base * ivaRate.value).toFixed(2))
})

const formatMoney = (value: number) =>
  new Intl.NumberFormat('es-EC', { style: 'currency', currency: 'USD' }).format(value || 0)

watch(
  () => state.ordenTrabajoId,
  (ordenId) => {
    const orden = ordenes.value.find(o => o.id === ordenId)
    if (!orden) {
      state.descuento = 0
      return
    }

    // Si ya existe pago pendiente/anulado → completar cobro en edit
    if (orden.pagoId) {
      router.visit(route('pagos.edit', orden.pagoId), {
        preserveScroll: true
      })
      return
    }

    state.descuento = orden.descuento
  },
  { immediate: true }
)

const handleSubmit = () => {
  if (!state.ordenTrabajoId || esCompletarCobro.value) return
  isLoading.value = true
  const payload: Record<string, unknown> = {
    ordenTrabajoId: state.ordenTrabajoId,
    descuento: descuentoAplicado.value,
    estado: state.estado
  }
  if (state.metodoPago) payload.metodoPago = state.metodoPago
  router.post(route('pagos.store'), payload, {
    onFinish: () => { isLoading.value = false }
  })
}
</script>

<template>
  <AppDashboardPanel id="pago-create">
    <template #header>
      <UDashboardNavbar title="Registrar pago">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <UCard class="w-full">
        <form class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 w-full" @submit.prevent="handleSubmit">
          <FormField
            label="Orden por cobrar"
            name="ordenTrabajoId"
            required
            :error="errors.ordenTrabajoId || errors.orden_trabajo_id"
            class="md:col-span-2 xl:col-span-3"
            hint="Solo órdenes Finalizadas o Entregadas, sin pago o con cobro pendiente/anulado."
          >
            <USelect
              v-model="state.ordenTrabajoId"
              :items="ordenes.map(o => ({ label: o.label, value: o.id }))"
              placeholder="Seleccionar orden por cobrar"
              class="w-full"
              :disabled="vieneDeFactura"
            />
          </FormField>

          <UAlert
            v-if="esCompletarCobro"
            class="md:col-span-2 xl:col-span-3"
            color="warning"
            variant="subtle"
            icon="i-lucide-wallet"
            title="Completar cobro"
            description="Esta orden ya tiene un pago pendiente o anulado. Te estamos llevando a completar ese cobro."
          />

          <UAlert
            v-else-if="ordenSeleccionada"
            class="md:col-span-2 xl:col-span-3"
            color="info"
            variant="subtle"
            icon="i-lucide-lock"
            :title="vieneDeFactura
              ? `Datos de la factura ${ordenSeleccionada.facturaNumero || ''} aplicados automáticamente`
              : (ordenSeleccionada.tieneFactura
                  ? `Valores fijos de la factura ${ordenSeleccionada.facturaNumero || ''}`
                  : 'Valores fijos desde servicios y repuestos de la OT')"
            :description="vieneDeFactura
              ? 'La orden está seleccionada y bloqueada; el total es el de la factura. Elige el método de pago y confirma el cobro.'
              : 'Servicios, repuestos y total no se editan. Solo puedes aplicar un descuento según el caso.'"
          />

          <template v-if="!esCompletarCobro">
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
                :disabled="!ordenSeleccionada || vieneDeFactura"
                placeholder="Aplicar descuento si corresponde"
              />
              <p class="mt-1.5 text-xs text-muted">
                {{ vieneDeFactura ? 'Descuento fijo tomado de la factura.' : 'Opcional. No puede superar el subtotal (' + formatMoney(subtotal) + ').' }}
              </p>
            </FormField>

            <div v-if="ordenSeleccionada?.tieneFactura" class="rounded-lg border border-default p-3">
              <p class="text-xs uppercase tracking-wide text-muted">IVA ({{ (ivaRate * 100).toFixed(0) }}%)</p>
              <p class="mt-1 text-sm font-semibold">{{ formatMoney(ivaCalculado) }}</p>
            </div>
            <div class="rounded-lg border border-default bg-elevated/40 p-3" :class="ordenSeleccionada?.tieneFactura ? '' : 'md:col-span-2 xl:col-span-3'">
              <p class="text-xs uppercase tracking-wide text-muted">Total a pagar</p>
              <p class="mt-1 text-lg font-semibold">{{ formatMoney(totalCalculado) }}</p>
            </div>

            <FormField label="Estado" name="estado" :error="errors.estado">
              <USelect v-model="state.estado" :items="estadoItems" class="w-full" />
            </FormField>
            <FormField label="Método de pago" name="metodoPago" :error="errors.metodoPago || errors.metodo_pago">
              <USelect
                v-model="state.metodoPago"
                :items="metodoItems"
                placeholder="Opcional"
                class="w-full"
              />
            </FormField>
            <div class="md:col-span-2 xl:col-span-3 flex gap-3">
              <UButton type="submit" label="Registrar pago" :loading="isLoading" :disabled="!state.ordenTrabajoId" />
              <UButton variant="ghost" color="neutral" label="Cancelar" :to="route('pagos.index')" />
            </div>
          </template>
        </form>
      </UCard>
    </template>
  </AppDashboardPanel>
</template>
