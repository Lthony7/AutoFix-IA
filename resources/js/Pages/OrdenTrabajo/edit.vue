<script setup lang="ts">
import { reactive, computed, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'
import MecanicoFichaSlideover, { type MecanicoFicha } from '../../components/MecanicoFichaSlideover.vue'

interface Option {
  id: string
  label: string
  precioBase?: number
  precio?: number
  clienteId?: string
}

interface VehiculoOption extends Option {
  placa?: string
  marca?: string
  modelo?: string
  anio?: number | null
  color?: string | null
  kilometraje?: number
  tipoCombustible?: string | null
}

interface MecanicoOption extends MecanicoFicha {
  label: string
}

interface OrdenServicio {
  servicioId: string
  precio: number
}

interface OrdenRepuesto {
  productoId: string
  cantidad: number
  precioUnitario: number
}

interface Avance {
  id: string
  mensaje: string
  usuarioNombre: string
  createdAt: string
}

const page = usePage()
const orden = computed(() => (page.props as any).orden)
const sugerenciaIa = computed(() => (page.props as any).sugerenciaIa)
const soloDiagnostico = computed(() => !!(page.props as any).soloDiagnostico)
const puedeEditarDiagnostico = computed(() => !!(page.props as any).puedeEditarDiagnostico)
const puedeRegistrarAvance = computed(() => !!(page.props as any).puedeRegistrarAvance)
const esMecanico = computed(() => !!(page.props as any).esMecanico)
const clientes = computed(() => ((page.props as any).clientes || []) as Option[])
const vehiculos = computed(() => ((page.props as any).vehiculos || []) as VehiculoOption[])
const mecanicos = computed(() => ((page.props as any).mecanicos || []) as MecanicoOption[])
const serviciosOpts = computed(() => ((page.props as any).servicios || []) as Option[])
const repuestosOpts = computed(() => ((page.props as any).repuestos || []) as Option[])
const avances = computed(() => ((orden.value?.avances || []) as Avance[]))

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
  { label: 'En diagnóstico', value: 'en_diagnostico' },
  { label: 'En reparación', value: 'en_reparacion' },
  { label: 'Finalizada', value: 'finalizada' },
  { label: 'Entregada', value: 'entregada' },
  { label: 'Cancelada', value: 'cancelada' }
]

const prioridadItems = [
  { label: 'Baja', value: 'baja' },
  // ZWNJ evita que Chrome traduzca "Media" → "Medios de comunicación"
  { label: 'Media\u200C', value: 'media' },
  { label: 'Alta', value: 'alta' }
]

const tipoFallaItems = [
  { label: 'Frenos', value: 'Frenos' },
  { label: 'Motor', value: 'Motor' },
  { label: 'Suspensión', value: 'Suspensión' },
  { label: 'Eléctrico', value: 'Eléctrico' },
  { label: 'Transmisión', value: 'Transmisión' },
  { label: 'Aire acondicionado', value: 'Aire acondicionado' },
  { label: 'Inyección', value: 'Inyección' },
  { label: 'Otro', value: 'Otro' }
]

const ordenInicial = (page.props as any).orden
const role = computed(() => String((page.props as any).auth?.user?.role || ''))
const puedeGestionarServicios = computed(() =>
  !!(page.props as any).puedeGestionarServicios
  || role.value === 'mecanico'
  || role.value === 'administrador'
)
const puedeGestionarRepuestos = computed(() =>
  !!(page.props as any).puedeGestionarRepuestos
  || role.value === 'mecanico'
  || role.value === 'administrador'
)
const nuevoEstado = ref(ordenInicial.estado)
const cambiandoEstado = ref(false)
const nuevoAvance = ref('')
const guardandoAvance = ref(false)
const servicioParaAgregar = ref('')
const repuestoParaAgregar = ref('')
const repuestoClienteNuevo = ref('')

type LineaServicio = { servicioId: string, precio: number }
type LineaRepuesto = { productoId: string, cantidad: number, precioUnitario: number }

const MARCADOR_CLIENTE = 'Repuestos que aporta el cliente:'

const parseRepuestosCliente = (obs: string | null | undefined): string[] => {
  if (!obs) return []
  const match = obs.match(/Repuestos que aporta el cliente:\s*(.+?)(?:\.|$)/is)
  if (!match?.[1]) return []
  return match[1]
    .split(';')
    .map(s => s.trim().replace(/\.$/, ''))
    .filter(Boolean)
}

const lineasServicios = ref<LineaServicio[]>(
  (ordenInicial.servicios || []).map((s: OrdenServicio) => ({
    servicioId: s.servicioId,
    precio: s.precio
  }))
)

const lineasRepuestos = ref<LineaRepuesto[]>(
  (ordenInicial.repuestos || []).map((r: OrdenRepuesto) => ({
    productoId: r.productoId,
    cantidad: r.cantidad,
    precioUnitario: r.precioUnitario
  }))
)

const repuestosCliente = ref<string[]>(parseRepuestosCliente(ordenInicial.observaciones))

const isLoading = ref(false)
const state = reactive({
  clienteId: ordenInicial.clienteId,
  vehiculoId: ordenInicial.vehiculoId,
  mecanicoId: ordenInicial.mecanicoId || '',
  tipoFalla: ordenInicial.tipoFalla || '',
  fallaReportada: ordenInicial.fallaReportada || '',
  kilometrajeIngreso: ordenInicial.kilometrajeIngreso ?? 0,
  observaciones: String(ordenInicial.observaciones || '')
    .replace(/\n?Repuestos que aporta el cliente:.*$/is, '')
    .trim(),
  diagnosticoTecnico: ordenInicial.diagnosticoTecnico || '',
  prioridad: ordenInicial.prioridad || 'media'
})

const fichaOpen = ref(false)
const mecanicoSeleccionado = computed(() =>
  mecanicos.value.find(m => m.id === state.mecanicoId) ?? null
)

watch(() => state.mecanicoId, (id) => {
  if (id) fichaOpen.value = true
})

const vehiculosFiltrados = computed(() => {
  if (!state.clienteId) return vehiculos.value
  return vehiculos.value.filter(v => v.clienteId === state.clienteId)
})

const vehiculoSeleccionado = computed(() =>
  vehiculos.value.find(v => v.id === state.vehiculoId) ?? null
)

watch(() => state.vehiculoId, (id) => {
  if (!id) return

  const vehiculo = vehiculos.value.find(v => v.id === id)
  if (!vehiculo) return

  state.kilometrajeIngreso = vehiculo.kilometraje ?? 0

  if (vehiculo.clienteId && state.clienteId !== vehiculo.clienteId) {
    state.clienteId = vehiculo.clienteId
  }
})

const nombreServicio = (id: string) =>
  serviciosOpts.value.find(s => s.id === id)?.label || 'Servicio'

const nombreRepuesto = (id: string) =>
  repuestosOpts.value.find(r => r.id === id)?.label || 'Repuesto'

const serviciosDisponiblesParaAgregar = computed(() => {
  const usados = new Set(lineasServicios.value.map(l => l.servicioId))
  return serviciosOpts.value.filter(s => !usados.has(s.id))
})

const repuestosDisponiblesParaAgregar = computed(() => {
  const usados = new Set(lineasRepuestos.value.map(l => l.productoId))
  return repuestosOpts.value.filter(r => !usados.has(r.id))
})

const agregarServicio = (servicioId?: string) => {
  const id = servicioId || servicioParaAgregar.value || serviciosDisponiblesParaAgregar.value[0]?.id || serviciosOpts.value[0]?.id
  if (!id) return
  if (lineasServicios.value.some(l => l.servicioId === id)) return

  const servicio = serviciosOpts.value.find(s => s.id === id)
  lineasServicios.value.push({
    servicioId: id,
    precio: servicio?.precioBase ?? 0
  })
  servicioParaAgregar.value = ''
}

const quitarServicio = (index: number) => {
  lineasServicios.value.splice(index, 1)
}

const onServicioChange = (index: number, servicioId: string) => {
  const servicio = serviciosOpts.value.find(s => s.id === servicioId)
  if (servicio) {
    lineasServicios.value[index].servicioId = servicioId
    lineasServicios.value[index].precio = servicio.precioBase ?? 0
  }
}

const totalServicios = computed(() =>
  lineasServicios.value.reduce((acc, l) => acc + (Number(l.precio) || 0), 0)
)

const agregarRepuesto = (productoId?: string) => {
  const id = productoId || repuestoParaAgregar.value || repuestosDisponiblesParaAgregar.value[0]?.id
  if (!id) return
  if (lineasRepuestos.value.some(l => l.productoId === id)) return

  const repuesto = repuestosOpts.value.find(r => r.id === id)
  lineasRepuestos.value.push({
    productoId: id,
    cantidad: 1,
    precioUnitario: repuesto?.precio ?? 0
  })
  repuestoParaAgregar.value = ''
}

const quitarRepuesto = (index: number) => {
  lineasRepuestos.value.splice(index, 1)
}

const onRepuestoChange = (index: number, productoId: string) => {
  const repuesto = repuestosOpts.value.find(r => r.id === productoId)
  if (repuesto) {
    lineasRepuestos.value[index].productoId = productoId
    lineasRepuestos.value[index].precioUnitario = repuesto.precio ?? 0
  }
}

const totalRepuestos = computed(() =>
  lineasRepuestos.value.reduce(
    (acc, l) => acc + (Number(l.cantidad) || 0) * (Number(l.precioUnitario) || 0),
    0
  )
)

const agregarRepuestoCliente = () => {
  const nombre = repuestoClienteNuevo.value.trim()
  if (!nombre) return
  const existe = repuestosCliente.value.some(n => n.toLowerCase() === nombre.toLowerCase())
  if (!existe) repuestosCliente.value.push(nombre)
  repuestoClienteNuevo.value = ''
}

const quitarRepuestoCliente = (index: number) => {
  repuestosCliente.value.splice(index, 1)
}

const observacionesConCliente = computed(() => {
  const base = String(state.observaciones || '')
    .replace(/\n?Repuestos que aporta el cliente:.*$/is, '')
    .trim()
  if (!repuestosCliente.value.length) return base
  const bloque = `${MARCADOR_CLIENTE} ${repuestosCliente.value.join('; ')}.`
  return base ? `${base}\n${bloque}` : bloque
})

const handleSubmit = () => {
  isLoading.value = true
  const payload: Record<string, unknown> = soloDiagnostico.value
    ? { diagnosticoTecnico: state.diagnosticoTecnico, observaciones: observacionesConCliente.value }
    : { ...state, observaciones: observacionesConCliente.value }

  if (!soloDiagnostico.value) {
    if (!puedeEditarDiagnostico.value) {
      delete payload.diagnosticoTecnico
    }

    if (puedeGestionarServicios.value) {
      payload.servicios = lineasServicios.value
        .filter(l => l.servicioId)
        .map(l => ({ servicioId: l.servicioId, precio: Number(l.precio) || 0 }))
    }

    if (puedeGestionarRepuestos.value) {
      payload.repuestos = lineasRepuestos.value
        .filter(l => l.productoId)
        .map(l => ({
          productoId: l.productoId,
          cantidad: Math.max(1, Number(l.cantidad) || 1),
          precioUnitario: Number(l.precioUnitario) || 0
        }))
    }

    if (!payload.mecanicoId) payload.mecanicoId = null
  }

  router.put(route('ordenes.update', orden.value.id), payload, {
    onFinish: () => { isLoading.value = false }
  })
}

const cambiarEstado = () => {
  if (nuevoEstado.value === orden.value.estado) return
  cambiandoEstado.value = true
  router.put(route('ordenes.cambiar-estado', orden.value.id), { estado: nuevoEstado.value }, {
    onFinish: () => { cambiandoEstado.value = false }
  })
}

const registrarAvance = () => {
  const mensaje = nuevoAvance.value.trim()
  if (!mensaje) return
  guardandoAvance.value = true
  router.post(route('ordenes.avances.store', orden.value.id), { mensaje }, {
    preserveScroll: true,
    onSuccess: () => { nuevoAvance.value = '' },
    onFinish: () => { guardandoAvance.value = false }
  })
}
</script>

<template>
  <AppDashboardPanel id="orden-edit">
    <template #header>
      <UDashboardNavbar :title="`Editar orden ${orden.numero}`">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <div class="flex gap-2">
            <UButton
              v-if="!sugerenciaIa"
              variant="soft"
              icon="i-lucide-brain"
              label="Generar IA"
              :to="route('diagnosticos-ia.create', { ordenTrabajoId: orden.id })"
            />
            <UButton
              v-else
              variant="ghost"
              icon="i-lucide-brain"
              label="Ver IA"
              :to="route('diagnosticos-ia.show', orden.id)"
            />
            <UButton
              v-if="orden.facturaId"
              variant="soft"
              icon="i-lucide-wallet"
              label="Ver factura"
              :to="route('facturas.show', orden.facturaId)"
            />
          </div>
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <div class="w-full space-y-4">
        <UAlert
          v-if="sugerenciaIa"
          color="info"
          variant="subtle"
          icon="i-lucide-brain"
          :title="`Sugerencia IA (${sugerenciaIa.estadoLabel})${sugerenciaIa.esSimulado ? ' · simulado' : ''}`"
          :description="[
            sugerenciaIa.diagnosticoDetalle,
            sugerenciaIa.especialidadRecomendada ? `Especialista: ${sugerenciaIa.especialidadRecomendada}` : null,
            sugerenciaIa.servicioRecomendado ? `Servicio: ${sugerenciaIa.servicioRecomendado}` : null,
            'Revisa mecánico, servicios y repuestos cargados automáticamente y corrige si hace falta. Luego facturación → pago.'
          ].filter(Boolean).join(' · ')"
        />

        <UAlert
          v-else
          color="warning"
          variant="subtle"
          icon="i-lucide-triangle-alert"
          title="Falta diagnóstico IA"
          description="Genera el diagnóstico para asignar especialista, servicios y repuestos sugeridos."
        />

        <UAlert
          v-if="orden.updatedByNombre || orden.createdByNombre"
          color="neutral"
          variant="subtle"
          icon="i-lucide-user-pen"
          :title="orden.updatedByNombre ? `Última modificación: ${orden.updatedByNombre}` : `Creada por: ${orden.createdByNombre}`"
          :description="orden.updatedAt ? `Actualizada: ${orden.updatedAt}` : (orden.createdAt ? `Creada: ${orden.createdAt}` : undefined)"
        />

        <UCard>
        <form class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 w-full" @submit.prevent="handleSubmit">
          <template v-if="soloDiagnostico">
            <FormField label="Diagnóstico técnico" name="diagnosticoTecnico" :error="errors.diagnosticoTecnico" class="md:col-span-2 xl:col-span-3">
              <UTextarea v-model="state.diagnosticoTecnico" class="w-full" :rows="5" />
            </FormField>
            <FormField label="Observaciones" name="observaciones" :error="errors.observaciones" class="md:col-span-2 xl:col-span-3">
              <UTextarea v-model="state.observaciones" class="w-full" />
            </FormField>
          </template>

          <template v-else>
            <FormField label="Cliente" name="clienteId" required :error="errors.clienteId" class="md:col-span-2 xl:col-span-3">
              <USelect
                v-model="state.clienteId"
                :items="clientes.map(c => ({ label: c.label, value: c.id }))"
                class="w-full"
              />
            </FormField>
            <FormField label="Vehículo" name="vehiculoId" required :error="errors.vehiculoId" class="md:col-span-2 xl:col-span-3">
              <USelect
                v-model="state.vehiculoId"
                :items="vehiculosFiltrados.map(v => ({ label: v.label, value: v.id }))"
                class="w-full"
              />
              <p v-if="vehiculoSeleccionado" class="mt-2 text-sm text-muted">
                {{ vehiculoSeleccionado.marca }} {{ vehiculoSeleccionado.modelo }}
                <span v-if="vehiculoSeleccionado.anio"> ({{ vehiculoSeleccionado.anio }})</span>
                <span v-if="vehiculoSeleccionado.color"> · {{ vehiculoSeleccionado.color }}</span>
                · Km registrado:
                <span class="font-medium text-highlighted">
                  {{ Number(vehiculoSeleccionado.kilometraje ?? 0).toLocaleString() }}
                </span>
              </p>
            </FormField>
            <FormField label="Mecánico" name="mecanicoId" :error="errors.mecanicoId" class="md:col-span-2 xl:col-span-3">
              <div class="flex flex-col gap-2 sm:flex-row sm:items-start">
                <USelect
                  v-model="state.mecanicoId"
                  :items="mecanicos.map(m => ({ label: m.label, value: m.id }))"
                  placeholder="Opcional"
                  class="w-full"
                />
                <UButton
                  type="button"
                  variant="soft"
                  icon="i-lucide-id-card"
                  label="Ver ficha"
                  :disabled="!state.mecanicoId"
                  @click="fichaOpen = true"
                />
              </div>
              <p v-if="mecanicoSeleccionado" class="mt-2 text-sm text-muted">
                Especialidad: <span class="font-medium text-highlighted">{{ mecanicoSeleccionado.especialidad }}</span>
              </p>
            </FormField>
            <FormField label="Tipo de falla" name="tipoFalla" :error="errors.tipoFalla">
              <USelect
                v-model="state.tipoFalla"
                :items="tipoFallaItems"
                placeholder="Seleccionar tipo"
                class="w-full"
              />
            </FormField>
            <FormField label="Prioridad" name="prioridad" :error="errors.prioridad">
              <div translate="no">
                <USelect
                  v-model="state.prioridad"
                  :items="prioridadItems"
                  value-key="value"
                  label-key="label"
                  class="w-full"
                >
                  <template #default="{ modelValue }">
                    <span translate="no">{{ prioridadItems.find(i => i.value === modelValue)?.label || modelValue }}</span>
                  </template>
                  <template #item-label="{ item }">
                    <span translate="no">{{ item.label }}</span>
                  </template>
                </USelect>
              </div>
            </FormField>
            <FormField label="Falla reportada" name="fallaReportada" :error="errors.fallaReportada" class="md:col-span-2 xl:col-span-3">
              <UTextarea v-model="state.fallaReportada" class="w-full" :rows="3" />
            </FormField>
            <FormField label="Kilometraje ingreso" name="kilometrajeIngreso" :error="errors.kilometrajeIngreso">
              <UInput v-model.number="state.kilometrajeIngreso" type="number" min="0" class="w-full" />
            </FormField>
            <FormField
              v-if="puedeEditarDiagnostico"
              label="Diagnóstico técnico"
              name="diagnosticoTecnico"
              :error="errors.diagnosticoTecnico"
            >
              <UTextarea v-model="state.diagnosticoTecnico" class="w-full" />
            </FormField>
            <div v-else class="space-y-1">
              <p class="text-sm font-medium">Diagnóstico técnico</p>
              <p class="text-sm text-muted whitespace-pre-wrap rounded-md border border-default/60 bg-elevated/40 p-3 min-h-16">
                {{ state.diagnosticoTecnico || 'Solo mecánico o administrador puede editarlo.' }}
              </p>
            </div>
            <FormField label="Observaciones" name="observaciones" :error="errors.observaciones" class="md:col-span-2 xl:col-span-3">
              <UTextarea v-model="state.observaciones" class="w-full" />
              <p v-if="repuestosCliente.length" class="mt-1.5 text-xs text-warning">
                Al guardar se añadirá automáticamente: {{ MARCADOR_CLIENTE }} {{ repuestosCliente.join('; ') }}.
              </p>
            </FormField>

            <div class="md:col-span-2 xl:col-span-3 border-t border-default pt-4 space-y-4">
              <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                  <h3 class="font-semibold text-sm">Servicios de la sesión</h3>
                  <p class="text-xs text-muted mt-0.5">
                    En cada sesión suele ir primero el diagnóstico computarizado y luego los demás (limpieza de inyectores, filtros, cuerpo de aceleración, etc.).
                    <span v-if="!puedeGestionarServicios"> Solo el mecánico puede cargarlos o corregirlos.</span>
                  </p>
                </div>
              </div>

              <div v-if="puedeGestionarServicios" class="space-y-3">
                <div
                  v-for="(linea, index) in lineasServicios"
                  :key="`${linea.servicioId}-${index}`"
                  class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end rounded-lg border border-default/60 p-3"
                >
                  <FormField :label="index === 0 ? 'Servicio' : `Servicio ${index + 1}`" class="md:col-span-7">
                    <USelect
                      :model-value="linea.servicioId"
                      :items="serviciosOpts.map(s => ({ label: s.label, value: s.id }))"
                      class="w-full"
                      @update:model-value="(v: string) => onServicioChange(index, v)"
                    />
                  </FormField>
                  <FormField label="Precio" class="md:col-span-3">
                    <UInput v-model.number="linea.precio" type="number" min="0" step="0.01" class="w-full" />
                  </FormField>
                  <div class="md:col-span-2 xl:col-span-3">
                    <UButton
                      type="button"
                      color="error"
                      variant="ghost"
                      icon="i-lucide-trash"
                      block
                      @click="quitarServicio(index)"
                    />
                  </div>
                </div>

                <div class="rounded-lg border border-dashed border-primary/40 bg-primary/5 p-3 space-y-3">
                  <p class="text-sm font-medium">Agregar otro servicio</p>
                  <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                    <FormField label="Servicio del catálogo" class="md:col-span-8">
                      <USelect
                        v-model="servicioParaAgregar"
                        :items="serviciosDisponiblesParaAgregar.map(s => ({ label: s.label, value: s.id }))"
                        placeholder="Ej: Limpieza de inyectores, cambio de filtro…"
                        class="w-full"
                      />
                    </FormField>
                    <div class="md:col-span-4">
                      <UButton
                        type="button"
                        icon="i-lucide-plus"
                        label="Agregar servicio"
                        block
                        :disabled="!servicioParaAgregar && !serviciosDisponiblesParaAgregar.length"
                        @click="agregarServicio()"
                      />
                    </div>
                  </div>
                  <p v-if="!serviciosDisponiblesParaAgregar.length" class="text-xs text-muted">
                    Ya están todos los servicios del catálogo en esta sesión.
                  </p>
                </div>

                <p v-if="!lineasServicios.length" class="text-sm text-muted">
                  Sin servicios. Usa “Agregar servicio” para incluir el diagnóstico computarizado y los del caso.
                </p>
                <p v-else class="text-sm text-muted">
                  Total servicios: <span class="font-medium text-highlighted">{{ totalServicios.toFixed(2) }}</span>
                </p>
              </div>

              <div v-else class="space-y-2">
                <div
                  v-for="(linea, index) in (orden.servicios || [])"
                  :key="index"
                  class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-default/50 px-3 py-2 text-sm"
                >
                  <span>{{ nombreServicio(linea.servicioId) }}</span>
                  <span class="font-medium">{{ Number(linea.precio).toFixed(2) }}</span>
                </div>
                <p v-if="!(orden.servicios || []).length" class="text-sm text-muted">
                  Aún no hay servicios cargados. El mecánico los agregará tras el diagnóstico.
                </p>
              </div>
            </div>

            <div class="md:col-span-2 xl:col-span-3 border-t border-default pt-4 space-y-4">
              <div>
                <h3 class="font-semibold text-sm">Repuestos de la sesión</h3>
                <p class="text-xs text-muted mt-0.5">
                  Si el repuesto está en inventario, agrégalo aquí. Si no existe, anótalo: el cliente lo compra por su cuenta.
                  <span v-if="!puedeGestionarRepuestos"> Solo el mecánico puede cargarlos o corregirlos.</span>
                </p>
              </div>

              <div v-if="puedeGestionarRepuestos" class="space-y-3">
                <div
                  v-for="(linea, index) in lineasRepuestos"
                  :key="`${linea.productoId}-${index}`"
                  class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end rounded-lg border border-default/60 p-3"
                >
                  <FormField :label="index === 0 ? 'Repuesto (inventario)' : `Repuesto ${index + 1}`" class="md:col-span-5">
                    <USelect
                      :model-value="linea.productoId"
                      :items="repuestosOpts.map(r => ({ label: r.label, value: r.id }))"
                      class="w-full"
                      @update:model-value="(v: string) => onRepuestoChange(index, v)"
                    />
                  </FormField>
                  <FormField label="Cant." class="md:col-span-2 xl:col-span-3">
                    <UInput v-model.number="linea.cantidad" type="number" min="1" class="w-full" />
                  </FormField>
                  <FormField label="P. unit." class="md:col-span-3">
                    <UInput v-model.number="linea.precioUnitario" type="number" min="0" step="0.01" class="w-full" />
                  </FormField>
                  <div class="md:col-span-2 xl:col-span-3">
                    <UButton
                      type="button"
                      color="error"
                      variant="ghost"
                      icon="i-lucide-trash"
                      block
                      @click="quitarRepuesto(index)"
                    />
                  </div>
                </div>

                <div class="rounded-lg border border-dashed border-primary/40 bg-primary/5 p-3 space-y-3">
                  <p class="text-sm font-medium">Agregar repuesto del inventario</p>
                  <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                    <FormField label="Repuesto existente" class="md:col-span-8">
                      <USelect
                        v-model="repuestoParaAgregar"
                        :items="repuestosDisponiblesParaAgregar.map(r => ({ label: r.label, value: r.id }))"
                        placeholder="Buscar en inventario…"
                        class="w-full"
                      />
                    </FormField>
                    <div class="md:col-span-4">
                      <UButton
                        type="button"
                        icon="i-lucide-plus"
                        label="Agregar repuesto"
                        block
                        :disabled="!repuestoParaAgregar && !repuestosDisponiblesParaAgregar.length"
                        @click="agregarRepuesto()"
                      />
                    </div>
                  </div>
                </div>

                <div class="rounded-lg border border-dashed border-warning/40 bg-warning/5 p-3 space-y-3">
                  <p class="text-sm font-medium">No está en inventario</p>
                  <p class="text-xs text-muted">
                    Se registrará en observaciones: el cliente compra el repuesto por su parte.
                  </p>
                  <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                    <FormField label="Nombre del repuesto" class="md:col-span-8">
                      <UInput
                        v-model="repuestoClienteNuevo"
                        class="w-full"
                        placeholder="Ej: Sensor MAP original, junta de tapa…"
                        @keydown.enter.prevent="agregarRepuestoCliente"
                      />
                    </FormField>
                    <div class="md:col-span-4">
                      <UButton
                        type="button"
                        color="warning"
                        variant="soft"
                        icon="i-lucide-notebook-pen"
                        label="Anotar (cliente)"
                        block
                        :disabled="!repuestoClienteNuevo.trim()"
                        @click="agregarRepuestoCliente"
                      />
                    </div>
                  </div>
                  <div v-if="repuestosCliente.length" class="space-y-2">
                    <div
                      v-for="(nombre, index) in repuestosCliente"
                      :key="`${nombre}-${index}`"
                      class="flex items-center justify-between gap-2 rounded-md border border-warning/30 bg-default px-3 py-2 text-sm"
                    >
                      <span>{{ nombre }}</span>
                      <UButton
                        type="button"
                        size="xs"
                        color="error"
                        variant="ghost"
                        icon="i-lucide-x"
                        @click="quitarRepuestoCliente(index)"
                      />
                    </div>
                  </div>
                </div>

                <p v-if="!lineasRepuestos.length && !repuestosCliente.length" class="text-sm text-muted">
                  Sin repuestos. Agrega del inventario o anota los que aportará el cliente.
                </p>
                <p v-else class="text-sm text-muted">
                  Total inventario:
                  <span class="font-medium text-highlighted">{{ totalRepuestos.toFixed(2) }}</span>
                  <span v-if="repuestosCliente.length">
                    · Cliente aporta: {{ repuestosCliente.length }}
                  </span>
                </p>
              </div>

              <div v-else class="space-y-2">
                <div
                  v-for="(linea, index) in (orden.repuestos || [])"
                  :key="index"
                  class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-default/50 px-3 py-2 text-sm"
                >
                  <span>{{ nombreRepuesto(linea.productoId) }}</span>
                  <span class="font-medium">
                    {{ linea.cantidad }} × {{ Number(linea.precioUnitario).toFixed(2) }}
                  </span>
                </div>
                <p v-if="parseRepuestosCliente(orden.observaciones).length" class="text-sm text-warning">
                  Cliente aporta: {{ parseRepuestosCliente(orden.observaciones).join('; ') }}
                </p>
                <p v-if="!(orden.repuestos || []).length && !parseRepuestosCliente(orden.observaciones).length" class="text-sm text-muted">
                  Aún no hay repuestos cargados.
                </p>
              </div>
            </div>

            <div class="md:col-span-2 xl:col-span-3 border-t border-default pt-4">
              <div class="flex flex-wrap items-end gap-3">
                <FormField label="Cambiar estado" name="estado" class="flex-1 min-w-[200px]">
                  <USelect v-model="nuevoEstado" :items="estadoItems" class="w-full" />
                </FormField>
                <UButton
                  type="button"
                  variant="outline"
                  label="Aplicar estado"
                  icon="i-lucide-refresh-cw"
                  :loading="cambiandoEstado"
                  :disabled="nuevoEstado === orden.estado"
                  @click="cambiarEstado"
                />
              </div>
            </div>
          </template>

          <div class="md:col-span-2 xl:col-span-3 flex gap-3">
            <UButton type="submit" label="Actualizar" :loading="isLoading" />
            <UButton variant="ghost" color="neutral" label="Cancelar" :to="route('ordenes.index')" />
          </div>
        </form>
      </UCard>

        <UCard>
          <div class="space-y-4">
            <div>
              <h3 class="text-base font-semibold">Bitácora de avances</h3>
              <p class="text-sm text-muted">Registro cronológico del progreso del servicio.</p>
            </div>

            <form
              v-if="puedeRegistrarAvance"
              class="space-y-3"
              @submit.prevent="registrarAvance"
            >
              <FormField label="Nuevo avance" name="mensaje" :error="errors.mensaje">
                <UTextarea
                  v-model="nuevoAvance"
                  class="w-full"
                  :rows="3"
                  placeholder="Ej: Se desmontaron pastillas delanteras; discos con desgaste irregular."
                />
              </FormField>
              <UButton
                type="submit"
                icon="i-lucide-message-square-plus"
                label="Registrar avance"
                :loading="guardandoAvance"
                :disabled="!nuevoAvance.trim()"
              />
            </form>

            <div v-if="avances.length" class="space-y-3">
              <div
                v-for="avance in avances"
                :key="avance.id"
                class="rounded-md border border-default/60 bg-elevated/30 p-3"
              >
                <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-muted">
                  <span class="font-medium text-highlighted">{{ avance.usuarioNombre }}</span>
                  <span>{{ avance.createdAt }}</span>
                </div>
                <p class="mt-2 text-sm whitespace-pre-wrap">{{ avance.mensaje }}</p>
              </div>
            </div>
            <p v-else class="text-sm text-muted">Aún no hay avances registrados en esta orden.</p>
          </div>
        </UCard>

      <MecanicoFichaSlideover v-model:open="fichaOpen" :mecanico="mecanicoSeleccionado" />
      </div>
    </template>
  </AppDashboardPanel>
</template>
