<script setup lang="ts">
import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

interface Vehiculo {
  id: string
  placa: string
  marca: string
  modelo: string
  anio: number
  clienteNombre: string | null
}

interface OrdenHistorial {
  id: string
  numero: string
  estado: string
  estadoLabel: string
  tipoFalla: string | null
  fallaReportada: string | null
  diagnosticoTecnico: string | null
  prioridad: string | null
  observaciones: string | null
  kilometrajeIngreso: number | null
  mecanicoNombre: string | null
  createdAt: string
  servicios: { nombre: string, precio: number }[]
  repuestos: { nombre: string, cantidad: number, precioUnitario: number, subtotal: number }[]
  avances: { mensaje: string, usuarioNombre: string, createdAt: string }[]
  sugerenciaIa: {
    estadoLabel: string
    esSimulado: boolean
    diagnosticoDetalle: string | null
    posiblesCausas: string[]
    accionesRecomendadas: string[]
    especialidadRecomendada: string | null
    servicioRecomendado: string | null
    serviciosSugeridos: string[]
    repuestosSugeridos: string[]
    prioridad: string | null
    observacionMecanico: string | null
    advertencia: string | null
    coincideAnalisis: boolean | null
    observacionesRevision: string | null
    createdAt: string | null
  } | null
  factura: {
    id: string
    numero: string
    serie: string
    fechaEmision: string | null
    subtotal: number
    iva: number
    descuento: number
    total: number
    estado: string
    estadoLabel: string
  } | null
  pago: {
    id: string
    total: number
    estado: string
    estadoLabel: string
    metodoPago: string | null
  } | null
}

const page = usePage()
const vehiculo = computed(() => (page.props as any).vehiculo as Vehiculo)
const ordenes = computed(() => (page.props as any).ordenes)
const rows = computed(() => (ordenes.value?.data || []) as OrdenHistorial[])

const expandedId = ref<string | null>(null)

const toggle = (id: string) => {
  expandedId.value = expandedId.value === id ? null : id
}

const formatMoney = (value: number) =>
  new Intl.NumberFormat('es-EC', { style: 'currency', currency: 'USD' }).format(value || 0)

const estadoColor = (estado: string) => {
  const map: Record<string, string> = {
    pendiente: 'warning',
    en_diagnostico: 'info',
    en_reparacion: 'primary',
    finalizada: 'success',
    entregada: 'success',
    cancelada: 'error'
  }
  return map[estado] || 'neutral'
}

const facturaBadgeClass = (estado: string) => {
  const map: Record<string, string> = {
    borrador: 'autofix-badge-solid--neutral',
    emitida: 'autofix-badge-solid--warn',
    pagada: 'autofix-badge-solid--ok',
    anulada: 'autofix-badge-solid--danger'
  }
  return map[estado] || 'autofix-badge-solid--neutral'
}
</script>

<template>
  <AppDashboardPanel id="historial">
    <template #header>
      <UDashboardNavbar :title="`Historial — ${vehiculo.placa}`">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton variant="ghost" icon="i-lucide-arrow-left" label="Historial" :to="route('historial.index')" />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
          <UCard>
            <p class="text-sm text-muted">Placa</p>
            <p class="text-xl font-semibold">{{ vehiculo.placa }}</p>
          </UCard>
          <UCard>
            <p class="text-sm text-muted">Vehículo</p>
            <p class="text-xl font-semibold">{{ vehiculo.marca }} {{ vehiculo.modelo }}</p>
          </UCard>
          <UCard>
            <p class="text-sm text-muted">Año</p>
            <p class="text-xl font-semibold">{{ vehiculo.anio }}</p>
          </UCard>
          <UCard>
            <p class="text-sm text-muted">Cliente</p>
            <p class="text-xl font-semibold">{{ vehiculo.clienteNombre || '—' }}</p>
          </UCard>
        </div>

        <UCard>
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold flex items-center gap-2">
              <UIcon name="i-lucide-history" class="size-4" />
              Órdenes de trabajo
            </h3>
            <p class="text-sm text-muted">Haz clic en una orden para ver su detalle completo.</p>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left border-b border-default">
                  <th class="py-3 pr-3">Número</th>
                  <th class="py-3 pr-3">Fecha</th>
                  <th class="py-3 pr-3">Estado</th>
                  <th class="py-3 pr-3">Falla</th>
                  <th class="py-3 pr-3">Mecánico</th>
                  <th class="py-3 pr-3">Km ingreso</th>
                  <th class="py-3">Total</th>
                </tr>
              </thead>
              <tbody>
                <template v-for="orden in rows" :key="orden.id">
                  <tr
                    class="border-b border-default/60 cursor-pointer transition-colors hover:bg-elevated/40"
                    @click="toggle(orden.id)"
                  >
                    <td class="py-3 pr-3">
                      <div class="flex items-center gap-2">
                        <UIcon
                          :name="expandedId === orden.id ? 'i-lucide-chevron-down' : 'i-lucide-chevron-right'"
                          class="size-4 text-muted"
                        />
                        <span class="font-medium">{{ orden.numero }}</span>
                      </div>
                    </td>
                    <td class="py-3 pr-3">{{ orden.createdAt }}</td>
                    <td class="py-3 pr-3">
                      <UBadge :color="estadoColor(orden.estado) as any" variant="subtle">
                        {{ orden.estadoLabel }}
                      </UBadge>
                    </td>
                    <td class="py-3 pr-3 max-w-xs truncate" :title="orden.fallaReportada || ''">
                      {{ orden.tipoFalla || orden.fallaReportada || '—' }}
                    </td>
                    <td class="py-3 pr-3">{{ orden.mecanicoNombre || '—' }}</td>
                    <td class="py-3 pr-3">{{ orden.kilometrajeIngreso?.toLocaleString() ?? '—' }}</td>
                    <td class="py-3 font-medium">
                      {{ orden.pago?.total != null ? formatMoney(orden.pago.total) : (orden.factura?.total != null ? formatMoney(orden.factura.total) : '—') }}
                    </td>
                  </tr>

                  <tr v-if="expandedId === orden.id" class="bg-elevated/40 border-b border-default">
                    <td colspan="7" class="px-4 py-4">
                      <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                          <UCard>
                            <h4 class="font-semibold mb-3 flex items-center gap-2">
                              <UIcon name="i-lucide-clipboard-list" class="size-4" />
                              Trabajo realizado
                            </h4>
                            <dl class="space-y-2 text-sm">
                              <div class="grid grid-cols-2 gap-2">
                                <dt class="text-muted">Tipo de falla</dt>
                                <dd>{{ orden.tipoFalla || '—' }}</dd>
                              </div>
                              <div class="grid grid-cols-2 gap-2">
                                <dt class="text-muted">Falla reportada</dt>
                                <dd>{{ orden.fallaReportada || '—' }}</dd>
                              </div>
                              <div class="grid grid-cols-2 gap-2">
                                <dt class="text-muted">Diagnóstico técnico</dt>
                                <dd>{{ orden.diagnosticoTecnico || '—' }}</dd>
                              </div>
                              <div class="grid grid-cols-2 gap-2">
                                <dt class="text-muted">Prioridad</dt>
                                <dd>{{ orden.prioridad || '—' }}</dd>
                              </div>
                              <div class="grid grid-cols-2 gap-2">
                                <dt class="text-muted">Mecánico</dt>
                                <dd>{{ orden.mecanicoNombre || '—' }}</dd>
                              </div>
                              <div v-if="orden.observaciones" class="grid grid-cols-2 gap-2">
                                <dt class="text-muted">Observaciones</dt>
                                <dd class="whitespace-pre-wrap">{{ orden.observaciones }}</dd>
                              </div>
                            </dl>
                          </UCard>

                          <UCard>
                            <div class="flex items-center justify-between mb-3">
                              <h4 class="font-semibold flex items-center gap-2">
                                <UIcon name="i-lucide-file-text" class="size-4" />
                                Factura
                              </h4>
                              <span v-if="orden.factura" class="autofix-badge-solid" :class="facturaBadgeClass(orden.factura.estado)">
                                {{ orden.factura.estadoLabel }}
                              </span>
                            </div>
                            <template v-if="orden.factura">
                              <dl class="space-y-2 text-sm">
                                <div class="grid grid-cols-2 gap-2">
                                  <dt class="text-muted">Número</dt>
                                  <dd class="font-medium">{{ orden.factura.numero }} (serie {{ orden.factura.serie }})</dd>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                  <dt class="text-muted">Fecha emisión</dt>
                                  <dd>{{ orden.factura.fechaEmision || '—' }}</dd>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                  <dt class="text-muted">Subtotal</dt>
                                  <dd>{{ formatMoney(orden.factura.subtotal) }}</dd>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                  <dt class="text-muted">Descuento</dt>
                                  <dd>{{ formatMoney(orden.factura.descuento) }}</dd>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                  <dt class="text-muted">IVA</dt>
                                  <dd>{{ formatMoney(orden.factura.iva) }}</dd>
                                </div>
                                <div class="grid grid-cols-2 gap-2 border-t border-default pt-2">
                                  <dt class="font-semibold">Total</dt>
                                  <dd class="font-semibold">{{ formatMoney(orden.factura.total) }}</dd>
                                </div>
                              </dl>
                              <div class="mt-4">
                                <UButton
                                  size="xs"
                                  variant="soft"
                                  icon="i-lucide-eye"
                                  label="Ver factura"
                                  :to="route('facturas.show', orden.factura.id)"
                                />
                              </div>
                            </template>
                            <p v-else class="text-sm text-muted">Sin factura asociada.</p>

                            <div class="mt-6 border-t border-default pt-4">
                              <h4 class="font-semibold mb-3 flex items-center gap-2">
                                <UIcon name="i-lucide-wallet" class="size-4" />
                                Pago
                              </h4>
                              <template v-if="orden.pago">
                                <dl class="space-y-2 text-sm">
                                  <div class="grid grid-cols-2 gap-2">
                                    <dt class="text-muted">Estado</dt>
                                    <dd>
                                      <span class="autofix-badge-solid" :class="orden.pago.estado === 'pagado' ? 'autofix-badge-solid--ok' : 'autofix-badge-solid--neutral'">
                                        {{ orden.pago.estadoLabel }}
                                      </span>
                                    </dd>
                                  </div>
                                  <div class="grid grid-cols-2 gap-2">
                                    <dt class="text-muted">Método</dt>
                                    <dd>{{ orden.pago.metodoPago || '—' }}</dd>
                                  </div>
                                  <div class="grid grid-cols-2 gap-2">
                                    <dt class="text-muted">Total pagado</dt>
                                    <dd class="font-semibold">{{ formatMoney(orden.pago.total) }}</dd>
                                  </div>
                                </dl>
                              </template>
                              <p v-else class="text-sm text-muted">Sin pago registrado.</p>
                            </div>
                          </UCard>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                          <UCard>
                            <h4 class="font-semibold mb-3 flex items-center gap-2">
                              <UIcon name="i-lucide-wrench" class="size-4" />
                              Servicios y repuestos
                            </h4>
                            <table class="w-full text-sm">
                              <thead>
                                <tr class="text-left border-b border-default">
                                  <th class="py-2 pr-3">Ítem</th>
                                  <th class="py-2 pr-3">Tipo</th>
                                  <th class="py-2 pr-3">Cant.</th>
                                  <th class="py-2">Subtotal</th>
                                </tr>
                              </thead>
                              <tbody>
                                <tr v-for="(s, i) in orden.servicios" :key="'s' + i" class="border-b border-default/50">
                                  <td class="py-2 pr-3">{{ s.nombre }}</td>
                                  <td class="py-2 pr-3">Servicio</td>
                                  <td class="py-2 pr-3">1</td>
                                  <td class="py-2">{{ formatMoney(s.precio) }}</td>
                                </tr>
                                <tr v-for="(r, i) in orden.repuestos" :key="'r' + i" class="border-b border-default/50">
                                  <td class="py-2 pr-3">{{ r.nombre }}</td>
                                  <td class="py-2 pr-3">Repuesto</td>
                                  <td class="py-2 pr-3">{{ r.cantidad }}</td>
                                  <td class="py-2">{{ formatMoney(r.subtotal) }}</td>
                                </tr>
                                <tr v-if="!orden.servicios.length && !orden.repuestos.length">
                                  <td colspan="4" class="py-4 text-center text-muted">Sin ítems registrados.</td>
                                </tr>
                              </tbody>
                            </table>
                          </UCard>

                          <UCard v-if="orden.avances.length">
                            <h4 class="font-semibold mb-3 flex items-center gap-2">
                              <UIcon name="i-lucide-message-square" class="size-4" />
                              Bitácora de avances
                            </h4>
                            <ol class="space-y-3 text-sm">
                              <li
                                v-for="(av, i) in orden.avances"
                                :key="i"
                                class="border-l-2 border-default pl-3"
                              >
                                <p>{{ av.mensaje }}</p>
                                <p class="text-xs text-muted mt-0.5">{{ av.usuarioNombre }} · {{ av.createdAt }}</p>
                              </li>
                            </ol>
                          </UCard>
                        </div>

                        <UCard v-if="orden.sugerenciaIa">
                          <div class="flex items-center justify-between mb-3">
                            <h4 class="font-semibold flex items-center gap-2">
                              <UIcon name="i-lucide-brain" class="size-4" />
                              Análisis de la IA
                            </h4>
                            <span class="autofix-badge-solid" :class="orden.sugerenciaIa.esSimulado ? 'autofix-badge-solid--neutral' : 'autofix-badge-solid--ok'">
                              {{ orden.sugerenciaIa.esSimulado ? 'Simulado' : 'IA real' }}
                            </span>
                          </div>
                          <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 text-sm">
                            <div class="space-y-3">
                              <div>
                                <p class="text-muted mb-0.5">Diagnóstico</p>
                                <p class="whitespace-pre-wrap">{{ orden.sugerenciaIa.diagnosticoDetalle || '—' }}</p>
                              </div>
                              <div>
                                <p class="text-muted mb-1">Posibles causas</p>
                                <ul class="list-disc list-inside space-y-1">
                                  <li v-for="(c, i) in orden.sugerenciaIa.posiblesCausas" :key="i">{{ c }}</li>
                                  <li v-if="!orden.sugerenciaIa.posiblesCausas.length">—</li>
                                </ul>
                              </div>
                              <div>
                                <p class="text-muted mb-1">Acciones recomendadas</p>
                                <ul class="list-disc list-inside space-y-1">
                                  <li v-for="(a, i) in orden.sugerenciaIa.accionesRecomendadas" :key="i">{{ a }}</li>
                                  <li v-if="!orden.sugerenciaIa.accionesRecomendadas.length">—</li>
                                </ul>
                              </div>
                            </div>
                            <div class="space-y-3">
                              <div>
                                <p class="text-muted mb-0.5">Especialidad / prioridad</p>
                                <p>{{ orden.sugerenciaIa.especialidadRecomendada || '—' }} · {{ orden.sugerenciaIa.prioridad || '—' }}</p>
                              </div>
                              <div>
                                <p class="text-muted mb-0.5">Servicio recomendado</p>
                                <p>{{ orden.sugerenciaIa.servicioRecomendado || '—' }}</p>
                              </div>
                              <div>
                                <p class="text-muted mb-1">Servicios sugeridos</p>
                                <ul class="list-disc list-inside space-y-1">
                                  <li v-for="(s, i) in orden.sugerenciaIa.serviciosSugeridos" :key="i">{{ s }}</li>
                                  <li v-if="!orden.sugerenciaIa.serviciosSugeridos.length">—</li>
                                </ul>
                              </div>
                              <div>
                                <p class="text-muted mb-1">Repuestos sugeridos</p>
                                <ul class="list-disc list-inside space-y-1">
                                  <li v-for="(r, i) in orden.sugerenciaIa.repuestosSugeridos" :key="i">{{ r }}</li>
                                  <li v-if="!orden.sugerenciaIa.repuestosSugeridos.length">—</li>
                                </ul>
                              </div>
                              <div v-if="orden.sugerenciaIa.coincideAnalisis != null">
                                <p class="text-muted mb-0.5">Coincide con el análisis del mecánico</p>
                                <span class="autofix-badge-solid" :class="orden.sugerenciaIa.coincideAnalisis ? 'autofix-badge-solid--ok' : 'autofix-badge-solid--warn'">
                                  {{ orden.sugerenciaIa.coincideAnalisis ? 'Sí' : 'No' }}
                                </span>
                                <p v-if="orden.sugerenciaIa.observacionesRevision" class="text-xs text-muted mt-1">
                                  {{ orden.sugerenciaIa.observacionesRevision }}
                                </p>
                              </div>
                              <div v-if="orden.sugerenciaIa.observacionMecanico">
                                <p class="text-muted mb-0.5">Observación del mecánico</p>
                                <p>{{ orden.sugerenciaIa.observacionMecanico }}</p>
                              </div>
                              <div v-if="orden.sugerenciaIa.advertencia">
                                <p class="text-muted mb-0.5">Advertencia</p>
                                <p class="text-warning">{{ orden.sugerenciaIa.advertencia }}</p>
                              </div>
                            </div>
                          </div>
                        </UCard>
                      </div>
                    </td>
                  </tr>
                </template>
                <tr v-if="!rows.length">
                  <td colspan="7" class="py-6 text-center text-muted">Sin órdenes registradas para este vehículo.</td>
                </tr>
              </tbody>
            </table>
          </div>
          <AppPagination :meta="ordenes?.meta" />
        </UCard>
      </div>
    </template>
  </AppDashboardPanel>
</template>
