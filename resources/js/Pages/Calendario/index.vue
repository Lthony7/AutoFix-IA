<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'

interface Option {
  id: string
  label: string
  clienteId?: string
}

interface Slot {
  fecha: string
  hora: string
  fechaHora: string
  label: string
}

interface Evento {
  id: string
  fechaHora: string
  fecha: string
  hora: string
  duracionMinutos: number
  tipo: string
  tipoLabel: string
  estado: string
  estadoLabel: string
  notas: string | null
  clienteNombre: string | null
  vehiculoPlaca: string | null
  mecanicoNombre: string | null
  ordenTrabajoId: string | null
  ordenNumero: string | null
  presupuestoId?: string | null
  presupuestoNumero?: string | null
  presupuestoTotal?: number | null
  puedeCancelar: boolean
  puedeReagendar: boolean
  puedeCompletar: boolean
  puedeCrearOt: boolean
}

interface PresupuestoDisponible {
  id: string
  numero: string
  total: number
  vehiculoId: string | null
  vehiculoPlaca: string | null
  servicios: { servicioId: string, nombre: string, precio: number, cantidad: number }[]
  repuestos: { productoId: string, nombre: string, precioUnitario: number, cantidad: number }[]
}

interface CatalogItem {
  id: string
  label: string
  precio: number
  stock?: number
}

interface OtDia {
  id: string
  numero: string
  estado: string
  estadoLabel: string
  clienteNombre: string | null
  vehiculoPlaca: string | null
  mecanicoNombre: string | null
  createdAt: string
}

const page = usePage()
const props = computed(() => page.props as any)

const vista = computed(() => props.value.vista as string)
const fecha = computed(() => props.value.fecha as string)
const desde = computed(() => props.value.desde as string)
const hasta = computed(() => props.value.hasta as string)
const eventos = computed(() => (props.value.eventos || []) as Evento[])
const otsDelDia = computed(() => (props.value.otsDelDia || []) as OtDia[])
const puedeCrear = computed(() => !!props.value.puedeCrear)
const puedeAgendar = computed(() => !!props.value.puedeAgendar)
const esCliente = computed(() => !!props.value.esCliente)
const esMecanico = computed(() => !!props.value.esMecanico)
const antelacionHoras = computed(() => Number(props.value.antelacionHoras ?? 12))
const clientes = computed(() => (props.value.clientes || []) as Option[])
const vehiculos = computed(() => (props.value.vehiculos || []) as Option[])
const mecanicos = computed(() => (props.value.mecanicos || []) as Option[])
const tipos = computed(() => (props.value.tipos || []) as { value: string, label: string }[])
const fechasHabiles = computed(() => (props.value.fechasHabiles || []) as string[])
const presupuestosDisponibles = computed(() => (props.value.presupuestosDisponibles || []) as PresupuestoDisponible[])
const catalogoServicios = computed(() => (props.value.catalogoServicios || []) as CatalogItem[])
const catalogoRepuestos = computed(() => (props.value.catalogoRepuestos || []) as CatalogItem[])
const presupuestoPreseleccionado = computed(() => (props.value.presupuestoPreseleccionado || '') as string)
const filtersProp = computed(() => props.value.filters || {})

const formatMoney = (value: number) =>
  new Intl.NumberFormat('es-EC', { style: 'currency', currency: 'USD' }).format(value)

const backendErrors = computed(() => page.props.errors || {})
const errors = computed(() => {
  const result: Record<string, string> = {}
  Object.keys(backendErrors.value).forEach((key) => {
    const error = (backendErrors.value as any)[key]
    result[key] = Array.isArray(error) ? error[0] : error
  })
  return result
})

const navigate = (overrides: Record<string, string | undefined> = {}) => {
  router.get(route('calendario.index'), {
    vista: overrides.vista ?? vista.value,
    fecha: overrides.fecha ?? fecha.value,
    mecanico_id: overrides.mecanico_id ?? (filtersProp.value.mecanico_id || undefined)
  }, { preserveState: false, replace: true })
}

const shiftFecha = (days: number) => {
  const d = new Date(fecha.value + 'T12:00:00')
  d.setDate(d.getDate() + days)
  navigate({ fecha: d.toISOString().slice(0, 10) })
}

const irHoy = () => navigate({ fecha: new Date().toISOString().slice(0, 10) })

const diasSemana = computed(() => {
  const start = new Date(desde.value + 'T12:00:00')
  return Array.from({ length: 7 }, (_, i) => {
    const d = new Date(start)
    d.setDate(start.getDate() + i)
    const key = d.toISOString().slice(0, 10)
    return {
      key,
      label: d.toLocaleDateString('es-EC', { weekday: 'short', day: 'numeric', month: 'short' }),
      eventos: eventos.value.filter(e => e.fecha === key)
    }
  })
})

const eventosAgrupadosDia = computed(() =>
  [...eventos.value].sort((a, b) => a.hora.localeCompare(b.hora))
)

const estadoColor = (estado: string) => {
  const map: Record<string, string> = {
    programada: 'info',
    reagendada: 'warning',
    cancelada: 'error',
    completada: 'success'
  }
  return map[estado] || 'neutral'
}

const tituloRango = computed(() => {
  if (vista.value === 'dia') {
    return new Date(fecha.value + 'T12:00:00').toLocaleDateString('es-EC', {
      weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
    })
  }
  return `${desde.value} — ${hasta.value}`
})

const fechaItems = computed(() =>
  fechasHabiles.value.map(f => ({
    label: new Date(f + 'T12:00:00').toLocaleDateString('es-EC', {
      weekday: 'short', day: 'numeric', month: 'short'
    }),
    value: f
  }))
)

const slotsLoading = ref(false)
const slotsDisponibles = ref<Slot[]>([])

const cargarSlots = async (fechaDia: string, excluirCitaId?: string | null) => {
  if (!fechaDia) {
    slotsDisponibles.value = []
    return
  }
  slotsLoading.value = true
  try {
    const params = new URLSearchParams({ fecha: fechaDia })
    if (excluirCitaId) params.set('excluir_cita_id', excluirCitaId)
    const res = await fetch(`${route('calendario.disponibilidad')}?${params.toString()}`, {
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin'
    })
    const data = await res.json()
    slotsDisponibles.value = (data.slots || []) as Slot[]
  } catch {
    slotsDisponibles.value = []
  } finally {
    slotsLoading.value = false
  }
}

const slotItems = computed(() =>
  slotsDisponibles.value.map(s => ({ label: s.label, value: s.fechaHora }))
)

// Crear cita (staff)
const createOpen = ref(false)
const createLoading = ref(false)
const createState = reactive({
  clienteId: '',
  vehiculoId: '',
  mecanicoId: '',
  fecha: '',
  fechaHora: '',
  duracionMinutos: 60,
  tipo: 'reparacion',
  notas: ''
})

const vehiculosFiltrados = computed(() => {
  if (!createState.clienteId) return vehiculos.value
  return vehiculos.value.filter(v => v.clienteId === createState.clienteId)
})

watch(() => createState.clienteId, () => {
  if (createState.vehiculoId && !vehiculosFiltrados.value.some(v => v.id === createState.vehiculoId)) {
    createState.vehiculoId = ''
  }
})

watch(() => createState.fecha, (f) => {
  if (!createOpen.value) return
  createState.fechaHora = ''
  cargarSlots(f)
})

const resetCreateForm = () => {
  createState.clienteId = ''
  createState.vehiculoId = ''
  createState.mecanicoId = ''
  createState.fecha = fechasHabiles.value[0] || fecha.value
  createState.fechaHora = ''
  createState.duracionMinutos = 60
  createState.tipo = 'reparacion'
  createState.notas = ''
  cargarSlots(createState.fecha)
}

watch(createOpen, (open) => {
  if (open) resetCreateForm()
})

const submitCreate = () => {
  if (!createState.clienteId) {
    alert('Selecciona un cliente.')
    return
  }
  if (!createState.vehiculoId) {
    alert('Selecciona un vehículo.')
    return
  }
  if (!createState.fechaHora) {
    alert('Elige un horario disponible.')
    return
  }

  createLoading.value = true
  router.post(route('calendario.store'), {
    clienteId: createState.clienteId,
    vehiculoId: createState.vehiculoId,
    mecanicoId: createState.mecanicoId || null,
    fechaHora: createState.fechaHora,
    duracionMinutos: createState.duracionMinutos || 60,
    tipo: createState.tipo,
    notas: createState.notas || null
  }, {
    preserveScroll: true,
    onFinish: () => { createLoading.value = false },
    onSuccess: () => { createOpen.value = false },
    onError: () => { createOpen.value = true }
  })
}

// Agendar cita (cliente)
const agendarOpen = ref(false)
const agendarLoading = ref(false)
const agendarState = reactive({
  vehiculoId: '',
  fecha: '',
  fechaHora: '',
  tipo: 'mantenimiento',
  notas: '',
  presupuestoId: '',
  servicios: [] as { servicioId: string, cantidad: number }[],
  repuestos: [] as { productoId: string, cantidad: number }[]
})
const servicioExtra = ref('')
const repuestoExtra = ref('')

watch(() => agendarState.fecha, (f) => {
  if (!agendarOpen.value) return
  agendarState.fechaHora = ''
  cargarSlots(f)
})

const cargarLineasPresupuesto = (presupuestoId: string) => {
  const p = presupuestosDisponibles.value.find(x => x.id === presupuestoId)
  if (!p) {
    agendarState.servicios = []
    agendarState.repuestos = []
    return
  }
  agendarState.servicios = (p.servicios || []).map(s => ({
    servicioId: s.servicioId,
    cantidad: s.cantidad || 1
  }))
  agendarState.repuestos = (p.repuestos || []).map(r => ({
    productoId: r.productoId,
    cantidad: r.cantidad || 1
  }))
  if (p.vehiculoId) agendarState.vehiculoId = p.vehiculoId
}

watch(() => agendarState.presupuestoId, (id) => {
  if (!agendarOpen.value) return
  if (!id) {
    agendarState.servicios = []
    agendarState.repuestos = []
    return
  }
  cargarLineasPresupuesto(id)
})

const resetAgendarForm = () => {
  agendarState.vehiculoId = vehiculos.value[0]?.id || ''
  agendarState.fecha = fechasHabiles.value[0] || fecha.value
  agendarState.fechaHora = ''
  agendarState.tipo = 'mantenimiento'
  agendarState.notas = ''
  agendarState.presupuestoId = presupuestoPreseleccionado.value || ''
  agendarState.servicios = []
  agendarState.repuestos = []
  servicioExtra.value = ''
  repuestoExtra.value = ''
  if (agendarState.presupuestoId) cargarLineasPresupuesto(agendarState.presupuestoId)
  cargarSlots(agendarState.fecha)
}

watch(agendarOpen, (open) => {
  if (open) resetAgendarForm()
})

watch(presupuestoPreseleccionado, (id) => {
  if (id && puedeAgendar.value) {
    agendarOpen.value = true
  }
}, { immediate: true })

const nombreServicio = (id: string) =>
  catalogoServicios.value.find(s => s.id === id)?.label
  || presupuestosDisponibles.value.flatMap(p => p.servicios).find(s => s.servicioId === id)?.nombre
  || 'Servicio'

const nombreRepuesto = (id: string) =>
  catalogoRepuestos.value.find(r => r.id === id)?.label
  || presupuestosDisponibles.value.flatMap(p => p.repuestos).find(r => r.productoId === id)?.nombre
  || 'Repuesto'

const precioServicio = (id: string) =>
  catalogoServicios.value.find(s => s.id === id)?.precio
  || presupuestosDisponibles.value.flatMap(p => p.servicios).find(s => s.servicioId === id)?.precio
  || 0

const precioRepuesto = (id: string) =>
  catalogoRepuestos.value.find(r => r.id === id)?.precio
  || presupuestosDisponibles.value.flatMap(p => p.repuestos).find(r => r.productoId === id)?.precioUnitario
  || 0

const totalAgendarPresupuesto = computed(() => {
  const s = agendarState.servicios.reduce((acc, l) => acc + precioServicio(l.servicioId) * l.cantidad, 0)
  const r = agendarState.repuestos.reduce((acc, l) => acc + precioRepuesto(l.productoId) * l.cantidad, 0)
  return s + r
})

const agregarServicioAgendar = () => {
  if (!servicioExtra.value) return
  const exists = agendarState.servicios.find(s => s.servicioId === servicioExtra.value)
  if (exists) exists.cantidad += 1
  else agendarState.servicios.push({ servicioId: servicioExtra.value, cantidad: 1 })
  servicioExtra.value = ''
}

const agregarRepuestoAgendar = () => {
  if (!repuestoExtra.value) return
  const exists = agendarState.repuestos.find(r => r.productoId === repuestoExtra.value)
  if (exists) exists.cantidad += 1
  else agendarState.repuestos.push({ productoId: repuestoExtra.value, cantidad: 1 })
  repuestoExtra.value = ''
}

const submitAgendar = () => {
  if (!agendarState.vehiculoId) {
    alert('Selecciona un vehículo.')
    return
  }
  if (!agendarState.fechaHora) {
    alert('Elige un horario disponible.')
    return
  }

  agendarLoading.value = true
  const payload: Record<string, unknown> = {
    vehiculoId: agendarState.vehiculoId,
    fechaHora: agendarState.fechaHora,
    tipo: agendarState.tipo,
    notas: agendarState.notas || null,
    presupuestoId: agendarState.presupuestoId || null
  }

  if (agendarState.presupuestoId) {
    payload.ajustarPresupuesto = true
    payload.servicios = agendarState.servicios
    payload.repuestos = agendarState.repuestos
  }

  router.post(route('calendario.agendar'), payload, {
    preserveScroll: true,
    onFinish: () => { agendarLoading.value = false },
    onSuccess: () => { agendarOpen.value = false },
    onError: () => { agendarOpen.value = true }
  })
}

// Reagendar
const reagendarOpen = ref(false)
const reagendarLoading = ref(false)
const citaActiva = ref<Evento | null>(null)
const reagendarState = reactive({ fecha: '', fechaHora: '', notas: '' })

watch(() => reagendarState.fecha, (f) => {
  if (!reagendarOpen.value) return
  reagendarState.fechaHora = ''
  cargarSlots(f, citaActiva.value?.id)
})

const openReagendar = (evento: Evento) => {
  citaActiva.value = evento
  reagendarState.fecha = evento.fecha || fechasHabiles.value[0] || fecha.value
  reagendarState.fechaHora = ''
  reagendarState.notas = evento.notas || ''
  reagendarOpen.value = true
  cargarSlots(reagendarState.fecha, evento.id)
}

const submitReagendar = () => {
  if (!citaActiva.value) return
  if (!reagendarState.fechaHora) {
    alert('Elige un horario disponible.')
    return
  }
  reagendarLoading.value = true
  router.put(route('calendario.reagendar', citaActiva.value.id), {
    fechaHora: reagendarState.fechaHora,
    notas: reagendarState.notas || null
  }, {
    onFinish: () => { reagendarLoading.value = false },
    onSuccess: () => { reagendarOpen.value = false }
  })
}

const cancelarCita = (evento: Evento) => {
  if (!confirm('¿Cancelar esta cita? El taller será notificado.')) return
  router.post(route('calendario.cancelar', evento.id))
}

const completarCita = (evento: Evento) => {
  router.post(route('calendario.completar', evento.id))
}

const crearOtLoading = ref<string | null>(null)
const crearOtDesdeCita = (evento: Evento) => {
  if (!confirm('¿Crear orden de trabajo desde esta cita y continuar con el diagnóstico IA?')) return
  crearOtLoading.value = evento.id
  router.post(route('calendario.crear-ot', evento.id), {}, {
    onFinish: () => { crearOtLoading.value = null }
  })
}

const enlaceOrden = (evento: Evento) => {
  if (!evento.ordenTrabajoId) return null
  return esCliente.value
    ? route('portal.mis-ordenes.show', evento.ordenTrabajoId)
    : route('ordenes.edit', evento.ordenTrabajoId)
}
</script>

<template>
  <AppDashboardPanel id="calendario">
    <template #header>
      <UDashboardNavbar title="Calendario">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <div class="flex max-w-[58vw] flex-wrap items-center justify-end gap-1.5 sm:max-w-none sm:gap-2">
            <UButton
              :variant="vista === 'dia' ? 'solid' : 'soft'"
              label="Día"
              size="xs"
              class="sm:hidden"
              @click="navigate({ vista: 'dia' })"
            />
            <UButton
              :variant="vista === 'semana' ? 'solid' : 'soft'"
              label="Sem."
              size="xs"
              class="sm:hidden"
              @click="navigate({ vista: 'semana' })"
            />
            <UButton
              :variant="vista === 'dia' ? 'solid' : 'soft'"
              label="Día"
              size="sm"
              class="hidden sm:inline-flex"
              @click="navigate({ vista: 'dia' })"
            />
            <UButton
              :variant="vista === 'semana' ? 'solid' : 'soft'"
              label="Semana"
              size="sm"
              class="hidden sm:inline-flex"
              @click="navigate({ vista: 'semana' })"
            />

            <UModal
              v-if="puedeCrear"
              v-model:open="createOpen"
              title="Nueva cita"
              description="Programa según cupos disponibles del taller."
              :ui="{ content: 'sm:max-w-lg' }"
            >
              <UButton icon="i-lucide-plus" label="Nueva cita" />

              <template #body>
                <form id="form-nueva-cita" class="space-y-3" @submit.prevent="submitCreate">
                  <UAlert
                    v-if="Object.keys(errors).length"
                    color="error"
                    variant="subtle"
                    title="Revisa los datos"
                    :description="String(Object.values(errors)[0] || '')"
                  />
                  <FormField label="Cliente" name="clienteId" required :error="errors.cliente_id || errors.clienteId">
                    <USelect
                      v-model="createState.clienteId"
                      :items="clientes.map(c => ({ label: c.label, value: c.id }))"
                      placeholder="Seleccionar cliente"
                      class="w-full"
                    />
                  </FormField>
                  <FormField label="Vehículo" name="vehiculoId" required :error="errors.vehiculo_id || errors.vehiculoId">
                    <USelect
                      v-model="createState.vehiculoId"
                      :items="vehiculosFiltrados.map(v => ({ label: v.label, value: v.id }))"
                      placeholder="Seleccionar vehículo"
                      class="w-full"
                      :disabled="!createState.clienteId"
                    />
                  </FormField>
                  <FormField label="Mecánico" name="mecanicoId" :error="errors.mecanico_id || errors.mecanicoId">
                    <USelect
                      v-model="createState.mecanicoId"
                      :items="[
                        { label: 'Sin asignar', value: '' },
                        ...mecanicos.map(m => ({ label: m.label, value: m.id }))
                      ]"
                      placeholder="Opcional"
                      class="w-full"
                    />
                  </FormField>
                  <FormField label="Fecha" name="fecha" required>
                    <USelect
                      v-model="createState.fecha"
                      :items="fechaItems"
                      placeholder="Día hábil"
                      class="w-full"
                    />
                  </FormField>
                  <FormField label="Horario disponible" name="fechaHora" required :error="errors.fecha_hora || errors.fechaHora">
                    <USelect
                      v-model="createState.fechaHora"
                      :items="slotItems"
                      :loading="slotsLoading"
                      :placeholder="slotsLoading ? 'Cargando cupos…' : (slotItems.length ? 'Elegir cupo' : 'Sin cupos ese día')"
                      class="w-full"
                      :disabled="!createState.fecha || slotsLoading"
                    />
                  </FormField>
                  <FormField label="Tipo" name="tipo" required :error="errors.tipo">
                    <USelect
                      v-model="createState.tipo"
                      :items="tipos.map(t => ({ label: t.label, value: t.value }))"
                      class="w-full"
                    />
                  </FormField>
                  <FormField label="Notas" name="notas" :error="errors.notas">
                    <UTextarea v-model="createState.notas" class="w-full" :rows="3" />
                  </FormField>
                </form>
              </template>

              <template #footer="{ close }">
                <div class="flex justify-end gap-2 w-full">
                  <UButton variant="ghost" color="neutral" label="Cancelar" @click="close()" />
                  <UButton
                    label="Guardar cita"
                    icon="i-lucide-check"
                    :loading="createLoading"
                    @click="submitCreate"
                  />
                </div>
              </template>
            </UModal>

            <UModal
              v-if="puedeAgendar"
              v-model:open="agendarOpen"
              title="Agendar cita"
              description="Puedes vincular un presupuesto y ajustarlo antes de confirmar el turno."
              :ui="{ content: 'sm:max-w-xl' }"
            >
              <UButton icon="i-lucide-calendar-plus" label="Agendar cita" color="primary" />

              <template #body>
                <form id="form-agendar-cita" class="space-y-3" @submit.prevent="submitAgendar">
                  <UAlert
                    v-if="Object.keys(errors).length"
                    color="error"
                    variant="subtle"
                    title="Revisa los datos"
                    :description="String(Object.values(errors)[0] || '')"
                  />
                  <FormField label="Vehículo" name="vehiculoId" required :error="errors.vehiculo_id || errors.vehiculoId">
                    <USelect
                      v-model="agendarState.vehiculoId"
                      :items="vehiculos.map(v => ({ label: v.label, value: v.id }))"
                      placeholder="Tu vehículo"
                      class="w-full"
                    />
                  </FormField>
                  <FormField label="Usar presupuesto" name="presupuestoId" :error="errors.presupuesto_id">
                    <USelect
                      v-model="agendarState.presupuestoId"
                      :items="[
                        { label: 'Sin presupuesto', value: '' },
                        ...presupuestosDisponibles.map(p => ({
                          label: `${p.numero} · ${formatMoney(p.total)}${p.vehiculoPlaca ? ' · ' + p.vehiculoPlaca : ''}`,
                          value: p.id
                        }))
                      ]"
                      class="w-full"
                    />
                  </FormField>

                  <div v-if="agendarState.presupuestoId" class="rounded-md border border-default/60 p-3 space-y-3">
                    <p class="text-sm font-medium">Ítems del presupuesto (puedes cambiarlos)</p>
                    <ul class="space-y-2 text-sm">
                      <li
                        v-for="(linea, idx) in agendarState.servicios"
                        :key="'s-' + linea.servicioId"
                        class="flex flex-wrap items-center justify-between gap-2"
                      >
                        <span>{{ nombreServicio(linea.servicioId) }}</span>
                        <div class="flex items-center gap-2">
                          <UInput v-model.number="linea.cantidad" type="number" min="1" max="20" class="w-16" />
                          <span class="w-20 text-right">{{ formatMoney(precioServicio(linea.servicioId) * linea.cantidad) }}</span>
                          <UButton
                            icon="i-lucide-trash-2"
                            size="xs"
                            color="error"
                            variant="ghost"
                            @click="agendarState.servicios.splice(idx, 1)"
                          />
                        </div>
                      </li>
                      <li
                        v-for="(linea, idx) in agendarState.repuestos"
                        :key="'r-' + linea.productoId"
                        class="flex flex-wrap items-center justify-between gap-2"
                      >
                        <span>{{ nombreRepuesto(linea.productoId) }}</span>
                        <div class="flex items-center gap-2">
                          <UInput v-model.number="linea.cantidad" type="number" min="1" class="w-16" />
                          <span class="w-20 text-right">{{ formatMoney(precioRepuesto(linea.productoId) * linea.cantidad) }}</span>
                          <UButton
                            icon="i-lucide-trash-2"
                            size="xs"
                            color="error"
                            variant="ghost"
                            @click="agendarState.repuestos.splice(idx, 1)"
                          />
                        </div>
                      </li>
                    </ul>
                    <div class="grid grid-cols-[1fr_auto] gap-2">
                      <USelect
                        v-model="servicioExtra"
                        :items="catalogoServicios.map(s => ({ label: `${s.label} · ${formatMoney(s.precio)}`, value: s.id }))"
                        placeholder="Agregar servicio"
                        class="w-full"
                      />
                      <UButton size="sm" label="+" @click="agregarServicioAgendar" />
                    </div>
                    <div class="grid grid-cols-[1fr_auto] gap-2">
                      <USelect
                        v-model="repuestoExtra"
                        :items="catalogoRepuestos.map(r => ({ label: `${r.label} · ${formatMoney(r.precio)}`, value: r.id }))"
                        placeholder="Agregar repuesto"
                        class="w-full"
                      />
                      <UButton size="sm" variant="soft" label="+" @click="agregarRepuestoAgendar" />
                    </div>
                    <p class="text-sm font-semibold text-right">Total estimado: {{ formatMoney(totalAgendarPresupuesto) }}</p>
                  </div>

                  <FormField label="Tipo" name="tipo" required :error="errors.tipo">
                    <USelect
                      v-model="agendarState.tipo"
                      :items="tipos.map(t => ({ label: t.label, value: t.value }))"
                      class="w-full"
                    />
                  </FormField>
                  <FormField label="Fecha" name="fecha" required>
                    <USelect
                      v-model="agendarState.fecha"
                      :items="fechaItems"
                      placeholder="Día hábil"
                      class="w-full"
                    />
                  </FormField>
                  <FormField label="Horario disponible" name="fechaHora" required :error="errors.fecha_hora || errors.fechaHora">
                    <USelect
                      v-model="agendarState.fechaHora"
                      :items="slotItems"
                      :loading="slotsLoading"
                      :placeholder="slotsLoading ? 'Cargando cupos…' : (slotItems.length ? 'Elegir cupo' : 'Sin cupos ese día')"
                      class="w-full"
                      :disabled="!agendarState.fecha || slotsLoading"
                    />
                  </FormField>
                  <FormField label="Detalle / motivo" name="notas" :error="errors.notas">
                    <UTextarea
                      v-model="agendarState.notas"
                      class="w-full"
                      :rows="3"
                      placeholder="Describe el problema o el servicio que necesitas"
                    />
                  </FormField>
                </form>
              </template>

              <template #footer="{ close }">
                <div class="flex justify-end gap-2 w-full">
                  <UButton variant="ghost" color="neutral" label="Cancelar" @click="close()" />
                  <UButton
                    label="Confirmar cita"
                    icon="i-lucide-check"
                    :loading="agendarLoading"
                    @click="submitAgendar"
                  />
                </div>
              </template>
            </UModal>
          </div>
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="space-y-4">
        <UCard>
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
              <UButton
                icon="i-lucide-chevron-left"
                variant="ghost"
                color="neutral"
                @click="shiftFecha(vista === 'dia' ? -1 : -7)"
              />
              <UButton variant="soft" label="Hoy" size="sm" @click="irHoy" />
              <UButton
                icon="i-lucide-chevron-right"
                variant="ghost"
                color="neutral"
                @click="shiftFecha(vista === 'dia' ? 1 : 7)"
              />
            </div>
            <p class="font-medium capitalize">{{ tituloRango }}</p>
            <div v-if="puedeCrear && mecanicos.length" class="min-w-48">
              <USelect
                :model-value="filtersProp.mecanico_id || ''"
                :items="[
                  { label: 'Todos los mecánicos', value: '' },
                  ...mecanicos.map(m => ({ label: m.label, value: m.id }))
                ]"
                class="w-full"
                @update:model-value="(v: string) => navigate({ mecanico_id: v || undefined })"
              />
            </div>
          </div>
          <p v-if="esCliente" class="text-sm text-muted mt-3">
            Agenda según cupos libres. Puedes cancelar o reagendar con al menos {{ antelacionHoras }} h de anticipación,
            siempre que el taller no haya iniciado la reparación.
          </p>
          <p v-else-if="puedeCrear" class="text-sm text-muted mt-3">
            En la planificación del día, usa <strong>Crear OT</strong> en cada cita confirmada para iniciar el diagnóstico IA.
          </p>
        </UCard>

        <!-- Vista día -->
        <div v-if="vista === 'dia'" class="space-y-3">
          <UCard v-for="evento in eventosAgrupadosDia" :key="evento.id">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <div class="flex flex-wrap items-center gap-2 mb-1">
                  <span class="font-semibold text-lg">{{ evento.hora }}</span>
                  <UBadge :color="estadoColor(evento.estado) as any" variant="subtle">{{ evento.estadoLabel }}</UBadge>
                  <UBadge variant="subtle">{{ evento.tipoLabel }}</UBadge>
                  <span class="text-xs text-muted">{{ evento.duracionMinutos }} min</span>
                </div>
                <p class="font-medium">{{ evento.vehiculoPlaca || 'Sin placa' }} · {{ evento.clienteNombre || 'Cliente' }}</p>
                <p v-if="evento.mecanicoNombre" class="text-sm text-muted">Mecánico: {{ evento.mecanicoNombre }}</p>
                <p v-if="evento.ordenNumero" class="text-sm text-muted">OT: {{ evento.ordenNumero }}</p>
                <p v-if="evento.presupuestoNumero" class="text-sm text-muted">
                  Presupuesto: {{ evento.presupuestoNumero }}
                  <span v-if="evento.presupuestoTotal != null"> · {{ formatMoney(evento.presupuestoTotal) }}</span>
                </p>
                <p v-if="evento.notas" class="text-sm mt-2 whitespace-pre-wrap">{{ evento.notas }}</p>
              </div>
              <div class="flex flex-wrap gap-1">
                <UButton
                  v-if="evento.puedeCrearOt"
                  size="xs"
                  color="primary"
                  icon="i-lucide-clipboard-plus"
                  label="Crear OT"
                  :loading="crearOtLoading === evento.id"
                  @click="crearOtDesdeCita(evento)"
                />
                <UButton
                  v-if="enlaceOrden(evento)"
                  size="xs"
                  variant="soft"
                  icon="i-lucide-clipboard-list"
                  label="Orden"
                  :to="enlaceOrden(evento)!"
                />
                <UButton
                  v-if="evento.puedeCompletar"
                  size="xs"
                  color="success"
                  variant="soft"
                  label="Completar"
                  @click="completarCita(evento)"
                />
                <UButton
                  v-if="evento.puedeReagendar"
                  size="xs"
                  variant="soft"
                  icon="i-lucide-calendar-clock"
                  label="Reagendar"
                  @click="openReagendar(evento)"
                />
                <UButton
                  v-if="evento.puedeCancelar"
                  size="xs"
                  color="error"
                  variant="ghost"
                  label="Cancelar"
                  @click="cancelarCita(evento)"
                />
              </div>
            </div>
          </UCard>
          <UCard v-if="!eventosAgrupadosDia.length">
            <p class="text-sm text-muted text-center py-6">
              {{ esMecanico ? 'No tienes reparaciones programadas este día.' : 'No hay citas para este día.' }}
            </p>
          </UCard>

          <UCard v-if="otsDelDia.length">
            <h3 class="font-semibold mb-3 flex items-center gap-2">
              <UIcon name="i-lucide-clipboard-plus" class="size-4" />
              Órdenes creadas hoy
            </h3>
            <ul class="space-y-2 text-sm">
              <li
                v-for="ot in otsDelDia"
                :key="ot.id"
                class="flex flex-wrap items-center justify-between gap-2 border-b border-default/50 pb-2"
              >
                <div>
                  <span class="font-medium">{{ ot.numero }}</span>
                  <span class="text-muted"> · {{ ot.vehiculoPlaca }} · {{ ot.clienteNombre }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <UBadge variant="subtle">{{ ot.estadoLabel }}</UBadge>
                  <UButton size="xs" variant="ghost" label="Abrir" :to="route('ordenes.edit', ot.id)" />
                </div>
              </li>
            </ul>
          </UCard>
        </div>

        <!-- Vista semana -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-7 gap-3">
          <UCard
            v-for="dia in diasSemana"
            :key="dia.key"
            class="min-h-40"
            :class="dia.key === fecha ? 'ring-1 ring-primary/40' : ''"
          >
            <button
              type="button"
              class="w-full text-left font-medium text-sm mb-2 hover:underline"
              @click="navigate({ vista: 'dia', fecha: dia.key })"
            >
              {{ dia.label }}
            </button>
            <div class="space-y-2">
              <button
                v-for="evento in dia.eventos"
                :key="evento.id"
                type="button"
                class="w-full text-left rounded-md border border-default/60 px-2 py-1.5 text-xs hover:bg-elevated"
                @click="navigate({ vista: 'dia', fecha: dia.key })"
              >
                <div class="flex items-center justify-between gap-1">
                  <span class="font-semibold">{{ evento.hora }}</span>
                  <UBadge :color="estadoColor(evento.estado) as any" variant="subtle" size="xs">
                    {{ evento.estadoLabel }}
                  </UBadge>
                </div>
                <p class="mt-0.5 truncate">{{ evento.vehiculoPlaca }} · {{ evento.tipoLabel }}</p>
              </button>
              <p v-if="!dia.eventos.length" class="text-xs text-muted">Sin citas</p>
            </div>
          </UCard>
        </div>
      </div>

      <!-- Modal reagendar -->
      <UModal v-model:open="reagendarOpen" title="Reagendar cita">
        <template #body>
          <form class="space-y-3" @submit.prevent="submitReagendar">
            <p v-if="citaActiva" class="text-sm text-muted">
              {{ citaActiva.vehiculoPlaca }} · {{ citaActiva.clienteNombre }}
            </p>
            <FormField label="Fecha" name="fecha" required>
              <USelect
                v-model="reagendarState.fecha"
                :items="fechaItems"
                placeholder="Día hábil"
                class="w-full"
              />
            </FormField>
            <FormField label="Horario disponible" name="fechaHora" required :error="errors.fecha_hora || errors.fechaHora">
              <USelect
                v-model="reagendarState.fechaHora"
                :items="slotItems"
                :loading="slotsLoading"
                :placeholder="slotsLoading ? 'Cargando cupos…' : (slotItems.length ? 'Elegir cupo' : 'Sin cupos ese día')"
                class="w-full"
                :disabled="!reagendarState.fecha || slotsLoading"
              />
            </FormField>
            <FormField label="Notas" name="notas" :error="errors.notas">
              <UTextarea v-model="reagendarState.notas" class="w-full" />
            </FormField>
          </form>
        </template>
        <template #footer>
          <div class="flex justify-end gap-2">
            <UButton variant="ghost" color="neutral" label="Cerrar" @click="reagendarOpen = false" />
            <UButton label="Reagendar" :loading="reagendarLoading" @click="submitReagendar" />
          </div>
        </template>
      </UModal>
    </template>
  </AppDashboardPanel>
</template>
