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
  diagnosticoDetalle: string | null
  posiblesCausas: string[] | string
  accionesRecomendadas: string[] | string
  especialidadRecomendada: string | null
  mecanicosSugeridos: MecanicoSugerido[]
  servicioRecomendado: string
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
  if (Array.isArray(raw)) return raw
  if (typeof raw === 'string') {
    try {
      const parsed = JSON.parse(raw)
      return Array.isArray(parsed) ? parsed : [raw]
    } catch {
      return raw ? [raw] : []
    }
  }
  return []
}

const causas = computed(() => asList(diagnostico.value.posiblesCausas))
const acciones = computed(() => asList(diagnostico.value.accionesRecomendadas))
const mecanicos = computed(() => diagnostico.value.mecanicosSugeridos || [])

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
          <UButton
            v-if="puedeRevisar"
            icon="i-lucide-clipboard-check"
            label="Revisar"
            :to="route('diagnosticos-ia.review', diagnostico.ordenTrabajoId)"
          />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <div class="max-w-4xl space-y-4">
        <UAlert
          color="warning"
          variant="subtle"
          icon="i-lucide-triangle-alert"
          title="Aviso importante"
          description="La información generada por Inteligencia Artificial es únicamente una sugerencia inicial. El diagnóstico final debe ser realizado y confirmado por un mecánico autorizado."
        />

        <div class="flex flex-wrap gap-2">
          <UBadge variant="subtle">{{ diagnostico.estadoLabel }}</UBadge>
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

        <UCard v-if="diagnostico.diagnosticoDetalle">
          <h3 class="font-semibold mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-stethoscope" class="size-4" />
            Diagnóstico detallado
          </h3>
          <p class="text-sm whitespace-pre-wrap leading-relaxed">{{ diagnostico.diagnosticoDetalle }}</p>
        </UCard>

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
            Qué hacer (acciones recomendadas)
          </h3>
          <ol v-if="acciones.length" class="list-decimal pl-5 space-y-1.5 text-sm">
            <li v-for="(accion, index) in acciones" :key="index">{{ accion }}</li>
          </ol>
          <p v-else class="text-sm text-muted">Sin acciones registradas.</p>
        </UCard>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <UCard>
            <h3 class="font-semibold mb-3 flex items-center gap-2">
              <UIcon name="i-lucide-wrench" class="size-4" />
              Servicio recomendado
            </h3>
            <p class="text-sm">{{ diagnostico.servicioRecomendado || '—' }}</p>
          </UCard>

          <UCard>
            <h3 class="font-semibold mb-3 flex items-center gap-2">
              <UIcon name="i-lucide-user-cog" class="size-4" />
              A quién recomendar
            </h3>
            <p class="text-sm font-medium">
              {{ diagnostico.especialidadRecomendada || 'Mantenimiento general' }}
            </p>
            <p class="text-xs text-muted mt-1">
              Especialidad sugerida según el tipo de falla reportada.
            </p>
          </UCard>
        </div>

        <UCard>
          <h3 class="font-semibold mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-users" class="size-4" />
            Mecánicos sugeridos del taller
          </h3>
          <div v-if="mecanicos.length" class="space-y-3">
            <div
              v-for="m in mecanicos"
              :key="m.id"
              class="flex flex-wrap items-start justify-between gap-2 rounded-lg border border-default/60 px-3 py-2.5"
            >
              <div>
                <p class="font-medium text-sm">{{ m.nombre }}</p>
                <p class="text-xs text-muted mt-0.5">{{ m.especialidad }}</p>
              </div>
              <UBadge v-if="m.telefono" variant="subtle" size="sm" icon="i-lucide-phone">
                {{ m.telefono }}
              </UBadge>
            </div>
          </div>
          <p v-else class="text-sm text-muted">
            No hay mecánicos activos con esa especialidad. Asigna manualmente desde la orden de trabajo.
          </p>
        </UCard>

        <UCard v-if="diagnostico.observacionMecanico">
          <h3 class="font-semibold mb-3">Observación del mecánico (IA)</h3>
          <p class="text-sm whitespace-pre-wrap">{{ diagnostico.observacionMecanico }}</p>
        </UCard>

        <UCard v-if="diagnostico.advertencia">
          <h3 class="font-semibold mb-3 flex items-center gap-2 text-warning">
            <UIcon name="i-lucide-alert-circle" class="size-4" />
            Advertencia
          </h3>
          <p class="text-sm whitespace-pre-wrap">{{ diagnostico.advertencia }}</p>
        </UCard>

        <UCard v-if="diagnostico.respuestaCompleta">
          <h3 class="font-semibold mb-3">Respuesta completa</h3>
          <p class="text-sm whitespace-pre-wrap text-muted">{{ diagnostico.respuestaCompleta }}</p>
        </UCard>

        <div class="flex gap-3">
          <UButton variant="ghost" color="neutral" label="Volver a órdenes" :to="route('ordenes.index')" />
        </div>
      </div>
    </template>
  </AppDashboardPanel>
</template>
