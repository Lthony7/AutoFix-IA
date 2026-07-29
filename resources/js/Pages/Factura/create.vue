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

interface ClienteFactura {
  tipoDocumento: string
  numeroDocumento: string
  nombres: string
  apellidos: string
  direccion: string
  telefono: string
  email: string
}

interface OrdenOption {
  id: string
  label: string
  subtotal: number
  iva: number
  total: number
  detalles: DetallePreview[]
  cliente: ClienteFactura | null
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
  estado: 'emitida',
  clienteTipoDocumento: 'CEDULA',
  clienteNumeroDocumento: '',
  clienteNombres: '',
  clienteApellidos: '',
  clienteDireccion: '',
  clienteTelefono: '',
  clienteEmail: '',
  actualizarCliente: false
})

const tiposDocumento = [
  { label: 'Cédula / C.I.', value: 'CEDULA' },
  { label: 'DNI', value: 'DNI' },
  { label: 'RUC', value: 'RUC' },
  { label: 'Pasaporte', value: 'PASAPORTE' },
  { label: 'Carnet de extranjería', value: 'CE' }
]

const ordenSeleccionada = computed(() =>
  ordenes.value.find(o => o.id === state.ordenTrabajoId) || null
)

const cargarClienteDeOrden = (orden: OrdenOption | null) => {
  const c = orden?.cliente
  state.clienteTipoDocumento = c?.tipoDocumento || 'CEDULA'
  state.clienteNumeroDocumento = (c?.numeroDocumento || '').toUpperCase().startsWith('TMP')
    ? ''
    : (c?.numeroDocumento || '')
  state.clienteNombres = c?.nombres || ''
  state.clienteApellidos = c?.apellidos || ''
  state.clienteDireccion = c?.direccion === 'Por completar' ? '' : (c?.direccion || '')
  state.clienteTelefono = c?.telefono === '0000000000' ? '' : (c?.telefono || '')
  state.clienteEmail = c?.email || ''
}

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
    cargarClienteDeOrden(ordenSeleccionada.value)
  },
  { immediate: true }
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
    estado: state.estado,
    clienteTipoDocumento: state.clienteTipoDocumento,
    clienteNumeroDocumento: state.clienteNumeroDocumento,
    clienteNombres: state.clienteNombres,
    clienteApellidos: state.clienteApellidos,
    clienteDireccion: state.clienteDireccion,
    clienteTelefono: state.clienteTelefono,
    clienteEmail: state.clienteEmail,
    actualizarCliente: state.actualizarCliente
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
      <form class="space-y-4" @submit.prevent="handleSubmit">
        <UAlert
          v-if="Object.keys(errors).length"
          color="error"
          variant="subtle"
          title="Revisa los datos"
          :description="String(Object.values(errors)[0] || '')"
        />

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
          <UCard class="xl:col-span-1">
            <h3 class="font-medium mb-3">Datos de emisión</h3>
            <div class="grid grid-cols-1 gap-4">
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
            </div>
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

        <UCard>
          <h3 class="font-medium mb-1">Datos del cliente para la factura</h3>
          <p class="text-sm text-muted mb-4">
            Se cargan desde la ficha del cliente. Puedes ajustarlos si el cliente lo solicita al facturar.
          </p>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <FormField label="Tipo de documento" name="clienteTipoDocumento" required :error="errors.cliente_tipo_documento || errors.clienteTipoDocumento">
              <USelect v-model="state.clienteTipoDocumento" :items="tiposDocumento" class="w-full" />
            </FormField>
            <FormField label="Número de documento" name="clienteNumeroDocumento" required :error="errors.cliente_numero_documento || errors.clienteNumeroDocumento">
              <UInput v-model="state.clienteNumeroDocumento" class="w-full" />
            </FormField>
            <FormField label="Nombres" name="clienteNombres" required :error="errors.cliente_nombres || errors.clienteNombres">
              <UInput v-model="state.clienteNombres" class="w-full" />
            </FormField>
            <FormField label="Apellidos" name="clienteApellidos" required :error="errors.cliente_apellidos || errors.clienteApellidos" hint="Dos apellidos">
              <UInput v-model="state.clienteApellidos" class="w-full" />
            </FormField>
            <FormField label="Teléfono" name="clienteTelefono" required :error="errors.cliente_telefono || errors.clienteTelefono">
              <UInput v-model="state.clienteTelefono" class="w-full" maxlength="10" />
            </FormField>
            <FormField label="Correo" name="clienteEmail" required :error="errors.cliente_email || errors.clienteEmail">
              <UInput v-model="state.clienteEmail" type="email" class="w-full" />
            </FormField>
            <FormField label="Dirección" name="clienteDireccion" required :error="errors.cliente_direccion || errors.clienteDireccion" class="md:col-span-2">
              <UInput v-model="state.clienteDireccion" class="w-full" />
            </FormField>
            <div class="md:col-span-2">
              <UCheckbox
                v-model="state.actualizarCliente"
                label="También actualizar la ficha del cliente con estos datos"
              />
            </div>
          </div>
        </UCard>

        <UCard>
          <div class="space-y-4">
            <FormField
              label="Observaciones"
              name="observaciones"
              :error="errors.observaciones"
            >
              <UTextarea
                v-model="state.observaciones"
                :rows="4"
                class="w-full"
                placeholder="Notas del caso: garantía, condiciones de entrega, detalles del cobro, acuerdos con el cliente, etc."
              />
            </FormField>

            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-default pt-4">
              <UButton
                type="button"
                variant="ghost"
                color="neutral"
                label="Cancelar"
                :to="route('facturas.index')"
              />
              <UButton
                type="submit"
                label="Generar factura"
                icon="i-lucide-file-check"
                :loading="isLoading"
                :disabled="!state.ordenTrabajoId"
              />
            </div>
          </div>
        </UCard>
      </form>
    </template>
  </AppDashboardPanel>
</template>
