<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

interface MecanicoSugerido {
  id: string
  nombre: string
  especialidad: string
  telefono?: string | null
}

interface Diagnostico {
  id: string
  ordenTrabajoId: string
  tipoFalla: string | null
  reporteCliente: string | null
  urgenciaSolicitada: string | null
  diagnosticoDetalle: string | null
  posiblesCausas: string[] | string
  accionesRecomendadas: string[] | string
  especialidadRecomendada: string | null
  mecanicosSugeridos: MecanicoSugerido[]
  servicioRecomendado: string
  serviciosSugeridos: string[]
  repuestosSugeridos: string[]
  prioridad: string
  observacionMecanico: string | null
  advertencia: string | null
  estado: string
  estadoLabel: string
  esSimulado: boolean
  respuestaCompleta: string | null
  orden: {
    numero: string
    clienteNombre: string | null
    vehiculoPlaca: string | null
    vehiculoMarca?: string | null
    vehiculoModelo?: string | null
    vehiculoAnio?: number | null
    kilometrajeIngreso?: number | null
  }
  createdAt: string
}

const page = usePage()
const diagnostico = computed(() => (page.props as any).diagnostico as Diagnostico)

const asList = (raw: string[] | string | null | undefined): string[] => {
  if (Array.isArray(raw)) return raw.filter(Boolean)
  if (typeof raw === 'string') {
    try {
      const parsed = JSON.parse(raw)
      return Array.isArray(parsed) ? parsed.filter(Boolean) : [raw]
    } catch {
      return raw ? [raw] : []
    }
  }
  return []
}

const causas = computed(() => asList(diagnostico.value.posiblesCausas))
const acciones = computed(() => asList(diagnostico.value.accionesRecomendadas))
const mecanicos = computed(() => diagnostico.value.mecanicosSugeridos || [])
const servicios = computed(() => asList(diagnostico.value.serviciosSugeridos))
const repuestos = computed(() => asList(diagnostico.value.repuestosSugeridos))

const prioridadColor = (prioridad: string) => {
  const map: Record<string, string> = { baja: 'success', media: 'warning', alta: 'error' }
  return map[prioridad] || 'neutral'
}

const puedeRevisar = computed(() =>
  ['generada', 'en_revision'].includes(diagnostico.value.estado)
)
</script>

<template>
  <AppDashboardPanel id="diagnostico-ia-show">
    <template #header>
      <UDashboardNavbar :title="`Diagnóstico IA — ${diagnostico.orden.numero}`">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <div class="flex gap-2">
            <UButton
              variant="soft"
              icon="i-lucide-clipboard-pen"
              label="Ver orden"
              :to="route('ordenes.edit', diagnostico.ordenTrabajoId)"
            />
            <UButton
              v-if="puedeRevisar"
              icon="i-lucide-clipboard-check"
              label="Revisar / confirmar"
              :to="route('diagnosticos-ia.review', diagnostico.ordenTrabajoId)"
            />
          </div>
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <div class="w-full space-y-4">
        <UAlert
          color="primary"
          variant="subtle"
          icon="i-lucide-wrench"
          title="Vista del mecánico"
          description="Contrasta este diagnóstico con tu análisis, confirma o modifícalo y deja observaciones que el cliente podrá ver en su portal."
        />

        <div class="flex flex-wrap gap-2">
          <UBadge variant="subtle">{{ diagnostico.estadoLabel }}</UBadge>
          <UBadge v-if="diagnostico.tipoFalla" color="neutral" variant="subtle">
            Falla: {{ diagnostico.tipoFalla }}
          </UBadge>
          <UBadge :color="prioridadColor(diagnostico.prioridad) as any" variant="subtle">
            Prioridad: {{ diagnostico.prioridad }}
          </UBadge>
          <UBadge
            v-if="diagnostico.especialidadRecomendada"
            color="primary"
            variant="subtle"
            icon="i-lucide-user-cog"
          >
            {{ diagnostico.especialidadRecomendada }}
          </UBadge>
          <UBadge v-if="diagnostico.esSimulado" color="warning" variant="subtle" icon="i-lucide-flask-conical">
            Simulado
          </UBadge>
          <UBadge v-else color="success" variant="subtle" icon="i-lucide-sparkles">
            IA real
          </UBadge>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <UCard>
            <p class="text-sm text-muted">Orden</p>
            <p class="font-semibold">{{ diagnostico.orden.numero }}</p>
          </UCard>
          <UCard>
            <p class="text-sm text-muted">Cliente</p>
            <p class="font-semibold">{{ diagnostico.orden.clienteNombre || '—' }}</p>
          </UCard>
          <UCard>
            <p class="text-sm text-muted">Vehículo</p>
            <p class="font-semibold">{{ diagnostico.orden.vehiculoPlaca || '—' }}</p>
            <p v-if="diagnostico.orden.vehiculoMarca" class="text-xs text-muted mt-1">
              {{ diagnostico.orden.vehiculoMarca }} {{ diagnostico.orden.vehiculoModelo }}
              <span v-if="diagnostico.orden.vehiculoAnio">({{ diagnostico.orden.vehiculoAnio }})</span>
            </p>
          </UCard>
          <UCard>
            <p class="text-sm text-muted">Kilometraje ingreso</p>
            <p class="font-semibold">
              {{ diagnostico.orden.kilometrajeIngreso != null
                ? `${Number(diagnostico.orden.kilometrajeIngreso).toLocaleString()} km`
                : '—' }}
            </p>
          </UCard>
        </div>

        <UCard v-if="diagnostico.reporteCliente">
          <h3 class="font-semibold mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-file-text" class="size-4" />
            Reporte ingresado
          </h3>
          <p class="text-sm whitespace-pre-wrap leading-relaxed">{{ diagnostico.reporteCliente }}</p>
        </UCard>

        <UCard v-if="diagnostico.diagnosticoDetalle">
          <h3 class="font-semibold mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-stethoscope" class="size-4" />
            Diagnóstico detallado (IA)
          </h3>
          <p class="text-sm whitespace-pre-wrap leading-relaxed">{{ diagnostico.diagnosticoDetalle }}</p>
        </UCard>

        <UCard v-if="diagnostico.observacionMecanico">
          <h3 class="font-semibold mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-message-square-text" class="size-4" />
            Observaciones para el mecánico
          </h3>
          <p class="text-sm whitespace-pre-wrap">{{ diagnostico.observacionMecanico }}</p>
        </UCard>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <UCard>
            <h3 class="font-semibold mb-3 flex items-center gap-2">
              <UIcon name="i-lucide-search" class="size-4" />
              Posibles causas
            </h3>
            <ol v-if="causas.length" class="list-decimal pl-5 space-y-1.5 text-sm">
              <li v-for="(causa, index) in causas" :key="index">{{ causa }}</li>
            </ol>
            <p v-else class="text-sm text-muted">Sin causas registradas.</p>
          </UCard>

          <UCard>
            <h3 class="font-semibold mb-3 flex items-center gap-2">
              <UIcon name="i-lucide-list-checks" class="size-4" />
              Acciones recomendadas
            </h3>
            <ol v-if="acciones.length" class="list-decimal pl-5 space-y-1.5 text-sm">
              <li v-for="(accion, index) in acciones" :key="index">{{ accion }}</li>
            </ol>
            <p v-else class="text-sm text-muted">Sin acciones registradas.</p>
          </UCard>
        </div>

        <UCard>
          <h3 class="font-semibold mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-users" class="size-4" />
            Mecánico sugerido
          </h3>
          <p class="text-sm font-medium mb-3">
            Especialidad: {{ diagnostico.especialidadRecomendada || 'Mantenimiento general' }}
          </p>
          <div v-if="mecanicos.length" class="space-y-3">
            <div
              v-for="(m, idx) in mecanicos"
              :key="m.id"
              class="flex flex-wrap items-start justify-between gap-2 rounded-lg border border-default/60 px-3 py-2.5"
              :class="idx === 0 ? 'border-primary/40 bg-primary/5' : ''"
            >
              <div>
                <div class="flex items-center gap-2">
                  <p class="font-medium text-sm">{{ m.nombre }}</p>
                  <UBadge v-if="idx === 0" color="primary" variant="subtle" size="xs">Asignado preferente</UBadge>
                </div>
                <p class="text-xs text-muted mt-0.5">{{ m.especialidad }}</p>
              </div>
              <UBadge v-if="m.telefono" variant="subtle" size="sm" icon="i-lucide-phone">
                {{ m.telefono }}
              </UBadge>
            </div>
          </div>
          <p v-else class="text-sm text-muted">
            No hay mecánicos activos con esa especialidad. Asigna manualmente desde la orden.
          </p>
        </UCard>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <UCard>
            <h3 class="font-semibold mb-3 flex items-center gap-2">
              <UIcon name="i-lucide-wrench" class="size-4" />
              Servicios sugeridos
            </h3>
            <p class="text-sm mb-2">
              Principal: <span class="font-medium">{{ diagnostico.servicioRecomendado || '—' }}</span>
            </p>
            <ul v-if="servicios.length" class="list-disc pl-5 space-y-1 text-sm">
              <li v-for="(s, index) in servicios" :key="index">{{ s }}</li>
            </ul>
            <p v-else class="text-sm text-muted">Sin lista adicional de servicios.</p>
          </UCard>

          <UCard>
            <h3 class="font-semibold mb-3 flex items-center gap-2">
              <UIcon name="i-lucide-package" class="size-4" />
              Repuestos a consumir
            </h3>
            <ul v-if="repuestos.length" class="list-disc pl-5 space-y-1 text-sm">
              <li v-for="(r, index) in repuestos" :key="index">{{ r }}</li>
            </ul>
            <p v-else class="text-sm text-muted">Sin repuestos sugeridos aún.</p>
          </UCard>
        </div>

        <UCard v-if="diagnostico.advertencia">
          <h3 class="font-semibold mb-3 flex items-center gap-2 text-warning">
            <UIcon name="i-lucide-alert-circle" class="size-4" />
            Advertencia
          </h3>
          <p class="text-sm whitespace-pre-wrap">{{ diagnostico.advertencia }}</p>
        </UCard>

        <UCard v-if="diagnostico.respuestaCompleta">
          <h3 class="font-semibold mb-3">Resumen completo</h3>
          <p class="text-sm whitespace-pre-wrap text-muted">{{ diagnostico.respuestaCompleta }}</p>
        </UCard>

        <div class="flex flex-wrap gap-3">
          <UButton
            v-if="puedeRevisar"
            icon="i-lucide-clipboard-check"
            label="Revisar / confirmar"
            :to="route('diagnosticos-ia.review', diagnostico.ordenTrabajoId)"
          />
          <UButton
            variant="soft"
            icon="i-lucide-clipboard-pen"
            label="Ir a la orden"
            :to="route('ordenes.edit', diagnostico.ordenTrabajoId)"
          />
          <UButton variant="ghost" color="neutral" label="Volver" :to="route('diagnosticos-ia.index')" />
        </div>
      </div>
    </template>
  </AppDashboardPanel>
</template>
