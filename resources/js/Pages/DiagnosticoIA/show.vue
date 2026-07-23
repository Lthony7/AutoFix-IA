<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

interface Diagnostico {
  id: string
  ordenTrabajoId: string
  posiblesCausas: string[] | string
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
  }
  createdAt: string
}

const page = usePage()
const diagnostico = computed(() => (page.props as any).diagnostico as Diagnostico)

const causas = computed(() => {
  const raw = diagnostico.value.posiblesCausas
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
})

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
          <UBadge v-if="diagnostico.esSimulado" color="warning" variant="subtle" icon="i-lucide-flask-conical">
            Simulado
          </UBadge>
          <UBadge v-else color="success" variant="subtle" icon="i-lucide-sparkles">
            IA real
          </UBadge>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
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
          </UCard>
        </div>

        <UCard>
          <h3 class="font-semibold mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-search" class="size-4" />
            Posibles causas
          </h3>
          <ul v-if="causas.length" class="list-disc pl-5 space-y-1 text-sm">
            <li v-for="(causa, index) in causas" :key="index">{{ causa }}</li>
          </ul>
          <p v-else class="text-sm text-muted">Sin causas registradas.</p>
        </UCard>

        <UCard>
          <h3 class="font-semibold mb-3 flex items-center gap-2">
            <UIcon name="i-lucide-wrench" class="size-4" />
            Servicio recomendado
          </h3>
          <p class="text-sm">{{ diagnostico.servicioRecomendado || '—' }}</p>
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
