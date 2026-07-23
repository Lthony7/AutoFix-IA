<script setup lang="ts">
import { reactive, computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'

interface Diagnostico {
  id: string
  ordenTrabajoId: string
  posiblesCausas: string[] | string
  servicioRecomendado: string
  prioridad: string
  observacionMecanico: string | null
  advertencia: string | null
  estadoLabel: string
  esSimulado: boolean
  orden: {
    numero: string
    clienteNombre: string | null
    vehiculoPlaca: string | null
  }
}

const page = usePage()
const diagnostico = (page.props as any).diagnostico as Diagnostico

const backendErrors = computed(() => page.props.errors || {})
const errors = computed(() => {
  const result: Record<string, string> = {}
  Object.keys(backendErrors.value).forEach((key) => {
    const error = (backendErrors.value as any)[key]
    result[key] = Array.isArray(error) ? error[0] : error
  })
  return result
})

const prioridadItems = [
  { label: 'Baja', value: 'baja' },
  { label: 'Media\u200C', value: 'media' },
  { label: 'Alta', value: 'alta' }
]

const accionSeleccionada = ref<'confirmar' | 'modificar' | 'descartar' | null>(null)
const isLoading = ref(false)

const state = reactive({
  observacionesRevision: '',
  servicioRecomendado: diagnostico.servicioRecomendado || '',
  prioridad: diagnostico.prioridad || 'media'
})

const submitRevision = (accion: 'confirmar' | 'modificar' | 'descartar') => {
  accionSeleccionada.value = accion
  isLoading.value = true

  const payload: Record<string, unknown> = {
    accion,
    observacionesRevision: state.observacionesRevision
  }

  if (accion === 'modificar') {
    payload.servicioRecomendado = state.servicioRecomendado
    payload.prioridad = state.prioridad
  }

  router.put(route('diagnosticos-ia.revisar', diagnostico.ordenTrabajoId), payload, {
    onFinish: () => {
      isLoading.value = false
      accionSeleccionada.value = null
    }
  })
}
</script>

<template>
  <AppDashboardPanel id="diagnostico-ia-review">
    <template #header>
      <UDashboardNavbar :title="`Revisar diagnóstico — ${diagnostico.orden.numero}`">
        <template #leading>
          <UDashboardSidebarCollapse />
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

        <UCard>
          <div class="flex flex-wrap gap-2 mb-4">
            <UBadge variant="subtle">{{ diagnostico.estadoLabel }}</UBadge>
            <UBadge v-if="diagnostico.esSimulado" color="warning" variant="subtle">Simulado</UBadge>
          </div>
          <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
            <div>
              <dt class="text-muted">Cliente</dt>
              <dd class="font-medium">{{ diagnostico.orden.clienteNombre || '—' }}</dd>
            </div>
            <div>
              <dt class="text-muted">Vehículo</dt>
              <dd class="font-medium">{{ diagnostico.orden.vehiculoPlaca || '—' }}</dd>
            </div>
            <div class="sm:col-span-2">
              <dt class="text-muted">Servicio recomendado (IA)</dt>
              <dd class="font-medium">{{ diagnostico.servicioRecomendado || '—' }}</dd>
            </div>
            <div>
              <dt class="text-muted">Prioridad (IA)</dt>
              <dd class="font-medium capitalize">{{ diagnostico.prioridad }}</dd>
            </div>
          </dl>
        </UCard>

        <UCard>
          <form class="space-y-4" @submit.prevent>
            <FormField label="Observaciones de revisión" name="observacionesRevision" :error="errors.observacionesRevision">
              <UTextarea v-model="state.observacionesRevision" class="w-full" placeholder="Comentarios del mecánico sobre la sugerencia IA" />
            </FormField>

            <div class="border-t border-default pt-4 space-y-4">
              <p class="text-sm font-medium">Ajustar sugerencia (solo al usar Modificar)</p>
              <FormField label="Servicio recomendado" name="servicioRecomendado" :error="errors.servicioRecomendado">
                <UInput v-model="state.servicioRecomendado" class="w-full" />
              </FormField>
              <FormField label="Prioridad" name="prioridad" :error="errors.prioridad">
                <div translate="no">
                  <USelect v-model="state.prioridad" :items="prioridadItems" class="w-full max-w-xs">
                    <template #default="{ modelValue }">
                      <span translate="no">{{ prioridadItems.find(i => i.value === modelValue)?.label || modelValue }}</span>
                    </template>
                    <template #item-label="{ item }">
                      <span translate="no">{{ item.label }}</span>
                    </template>
                  </USelect>
                </div>
              </FormField>
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
              <UButton
                type="button"
                color="success"
                icon="i-lucide-check"
                label="Confirmar"
                :loading="isLoading && accionSeleccionada === 'confirmar'"
                @click="submitRevision('confirmar')"
              />
              <UButton
                type="button"
                color="primary"
                icon="i-lucide-pencil"
                label="Modificar"
                :loading="isLoading && accionSeleccionada === 'modificar'"
                @click="submitRevision('modificar')"
              />
              <UButton
                type="button"
                color="error"
                variant="outline"
                icon="i-lucide-x"
                label="Descartar"
                :loading="isLoading && accionSeleccionada === 'descartar'"
                @click="submitRevision('descartar')"
              />
              <UButton variant="ghost" color="neutral" label="Ver diagnóstico" :to="route('diagnosticos-ia.show', diagnostico.ordenTrabajoId)" />
            </div>
          </form>
        </UCard>
      </div>
    </template>
  </AppDashboardPanel>
</template>
