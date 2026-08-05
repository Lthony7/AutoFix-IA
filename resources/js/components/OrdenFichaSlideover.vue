<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from './FormField.vue'

export interface OrdenFicha {
  id: string
  numero: string
  clienteId?: string
  vehiculoId?: string
  mecanicoId?: string | null
  clienteNombre: string | null
  vehiculoPlaca: string | null
  mecanicoNombre: string | null
  estado: string
  estadoLabel: string
  tipoFalla?: string | null
  fallaReportada?: string | null
  kilometrajeIngreso?: number | null
  observaciones?: string | null
  diagnosticoTecnico?: string | null
  prioridad?: string | null
  facturaId?: string | null
  puedeFacturar?: boolean
  createdAt?: string | null
  updatedAt?: string | null
}

interface MecanicoOption {
  id: string
  label: string
}

const open = defineModel<boolean>('open', { default: false })

const props = defineProps<{
  orden?: OrdenFicha | null
  mecanicos?: MecanicoOption[]
  canDelete?: boolean
  canFacturar?: boolean
  canCambiarEstado?: boolean
  canEditarDiagnostico?: boolean
}>()

const emit = defineEmits<{
  deleted: []
}>()

const page = usePage()
const editing = ref(false)
const isSaving = ref(false)
const isDeleting = ref(false)
const cambiandoEstado = ref(false)

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
  { label: 'Media\u200C', value: 'media' },
  { label: 'Alta', value: 'alta' }
]

const state = reactive({
  estado: 'pendiente',
  prioridad: 'media',
  mecanicoId: '',
  tipoFalla: '',
  fallaReportada: '',
  observaciones: '',
  diagnosticoTecnico: '',
  kilometrajeIngreso: 0
})

const syncFromOrden = () => {
  const o = props.orden
  if (!o) return
  state.estado = o.estado || 'pendiente'
  state.prioridad = o.prioridad || 'media'
  state.mecanicoId = o.mecanicoId || ''
  state.tipoFalla = o.tipoFalla || ''
  state.fallaReportada = o.fallaReportada || ''
  state.observaciones = o.observaciones || ''
  state.diagnosticoTecnico = o.diagnosticoTecnico || ''
  state.kilometrajeIngreso = o.kilometrajeIngreso ?? 0
  editing.value = false
}

watch(
  () => [open.value, props.orden?.id],
  () => {
    if (open.value && props.orden) syncFromOrden()
  },
  { immediate: true }
)

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

const guardar = () => {
  if (!props.orden) return
  isSaving.value = true

  const payload: Record<string, unknown> = {
    prioridad: state.prioridad,
    tipoFalla: state.tipoFalla || null,
    fallaReportada: state.fallaReportada,
    observaciones: state.observaciones || null,
    kilometrajeIngreso: state.kilometrajeIngreso,
    mecanicoId: state.mecanicoId || null
  }

  if (props.canEditarDiagnostico) {
    payload.diagnosticoTecnico = state.diagnosticoTecnico || null
  }

  router.put(route('ordenes.update', props.orden.id), payload, {
    preserveScroll: true,
    onSuccess: () => { editing.value = false },
    onFinish: () => { isSaving.value = false }
  })
}

const aplicarEstado = () => {
  if (!props.orden || !props.canCambiarEstado) return
  if (state.estado === props.orden.estado) return
  cambiandoEstado.value = true
  router.put(route('ordenes.cambiar-estado', props.orden.id), { estado: state.estado }, {
    preserveScroll: true,
    onFinish: () => { cambiandoEstado.value = false }
  })
}

const eliminar = () => {
  if (!props.orden || !props.canDelete) return
  if (!confirm(`¿Eliminar la orden ${props.orden.numero}?`)) return
  isDeleting.value = true
  router.delete(route('ordenes.destroy', props.orden.id), {
    preserveScroll: true,
    onSuccess: () => {
      open.value = false
      emit('deleted')
    },
    onFinish: () => { isDeleting.value = false }
  })
}

const cancelarEdicion = () => syncFromOrden()
</script>

<template>
  <USlideover
    v-model:open="open"
    :title="editing ? `Corregir ${orden?.numero || ''}` : `Orden ${orden?.numero || ''}`"
    :description="orden ? `${orden.clienteNombre || 'Sin cliente'} · ${orden.vehiculoPlaca || 'Sin placa'}` : 'Detalle de la orden'"
    side="right"
  >
    <template #body>
      <div v-if="orden" class="space-y-5">
        <template v-if="!editing">
          <div class="flex flex-wrap items-center gap-2">
            <UBadge :color="estadoColor(orden.estado) as any" variant="subtle">
              {{ orden.estadoLabel }}
            </UBadge>
            <UBadge v-if="orden.prioridad" variant="outline" class="capitalize">
              Prioridad: {{ orden.prioridad }}
            </UBadge>
          </div>

          <div class="grid grid-cols-1 gap-4">
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Cliente</p>
              <p class="mt-1 text-sm font-medium">{{ orden.clienteNombre || '—' }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Vehículo</p>
              <p class="mt-1 text-sm font-medium">{{ orden.vehiculoPlaca || '—' }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Mecánico</p>
              <p class="mt-1 text-sm">{{ orden.mecanicoNombre || 'Sin asignar' }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Tipo de falla</p>
              <p class="mt-1 text-sm">{{ orden.tipoFalla || '—' }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Falla reportada</p>
              <p class="mt-1 text-sm whitespace-pre-wrap">{{ orden.fallaReportada || '—' }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Kilometraje ingreso</p>
              <p class="mt-1 text-sm">
                {{ orden.kilometrajeIngreso != null ? `${Number(orden.kilometrajeIngreso).toLocaleString()} km` : '—' }}
              </p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Diagnóstico técnico</p>
              <p class="mt-1 text-sm whitespace-pre-wrap">{{ orden.diagnosticoTecnico || '—' }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Observaciones</p>
              <p class="mt-1 text-sm whitespace-pre-wrap">{{ orden.observaciones || '—' }}</p>
            </div>
          </div>

          <div v-if="canCambiarEstado" class="space-y-3 rounded-lg border border-default/60 p-3">
            <p class="text-sm font-medium">Cambiar estado</p>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
              <FormField label="Estado" name="estado" class="flex-1">
                <USelect v-model="state.estado" :items="estadoItems" class="w-full" />
              </FormField>
              <UButton
                type="button"
                variant="outline"
                label="Aplicar"
                icon="i-lucide-refresh-cw"
                :loading="cambiandoEstado"
                :disabled="state.estado === orden.estado"
                @click="aplicarEstado"
              />
            </div>
          </div>

          <div class="flex flex-wrap gap-2 pt-1">
            <UButton icon="i-lucide-pencil" label="Corregir" @click="editing = true" />
            <UButton
              variant="soft"
              icon="i-lucide-panel-right-open"
              label="Edición completa"
              :to="route('ordenes.edit', orden.id)"
              @click="open = false"
            />
            <UButton
              variant="soft"
              color="primary"
              icon="i-lucide-brain"
              label="Diagnóstico IA"
              :to="route('diagnosticos-ia.create', { ordenTrabajoId: orden.id })"
              @click="open = false"
            />
            <UButton
              v-if="canFacturar && orden.facturaId"
              color="success"
              variant="soft"
              icon="i-lucide-file-text"
              label="Ver factura"
              :to="route('facturas.show', orden.facturaId)"
              @click="open = false"
            />
            <UButton
              v-if="canDelete"
              color="error"
              variant="soft"
              icon="i-lucide-trash"
              label="Eliminar"
              :loading="isDeleting"
              @click="eliminar"
            />
          </div>
        </template>

        <form v-else class="space-y-4" @submit.prevent="guardar">
          <FormField label="Mecánico" name="mecanicoId" :error="errors.mecanicoId">
            <USelect
              v-model="state.mecanicoId"
              :items="[
                { label: 'Sin asignar', value: '' },
                ...(mecanicos || []).map(m => ({ label: m.label, value: m.id }))
              ]"
              placeholder="Sin asignar"
              class="w-full"
            />
          </FormField>
          <FormField label="Prioridad" name="prioridad" :error="errors.prioridad">
            <div translate="no">
              <USelect v-model="state.prioridad" :items="prioridadItems" class="w-full" />
            </div>
          </FormField>
          <FormField label="Tipo de falla" name="tipoFalla" :error="errors.tipoFalla">
            <UInput v-model="state.tipoFalla" class="w-full" />
          </FormField>
          <FormField label="Falla reportada" name="fallaReportada" required :error="errors.fallaReportada">
            <UTextarea v-model="state.fallaReportada" class="w-full" :rows="3" />
          </FormField>
          <FormField label="Kilometraje ingreso" name="kilometrajeIngreso" :error="errors.kilometrajeIngreso">
            <UInput v-model.number="state.kilometrajeIngreso" type="number" min="0" class="w-full" />
          </FormField>
          <FormField
            v-if="canEditarDiagnostico"
            label="Diagnóstico técnico"
            name="diagnosticoTecnico"
            :error="errors.diagnosticoTecnico"
          >
            <UTextarea v-model="state.diagnosticoTecnico" class="w-full" :rows="3" />
          </FormField>
          <FormField label="Observaciones" name="observaciones" :error="errors.observaciones">
            <UTextarea v-model="state.observaciones" class="w-full" :rows="2" />
          </FormField>

          <div class="flex flex-wrap gap-2 pt-2">
            <UButton type="submit" icon="i-lucide-save" label="Guardar cambios" :loading="isSaving" />
            <UButton type="button" variant="ghost" color="neutral" label="Cancelar" @click="cancelarEdicion" />
          </div>
        </form>
      </div>
      <p v-else class="text-sm text-muted">Selecciona una orden para gestionarla.</p>
    </template>
  </USlideover>
</template>
