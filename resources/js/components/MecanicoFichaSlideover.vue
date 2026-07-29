<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from './FormField.vue'
import {
  mergeErrors,
  soloDigitos,
  validarDocumento,
  validarEmail,
  validarNombre,
  validarTelefono,
  type FormErrors
} from '../composables/useFormValidation'

export interface MecanicoFicha {
  id: string
  userId?: string | null
  nombres?: string
  apellidos?: string
  nombreCompleto: string
  documento: string
  especialidad: string
  telefono?: string | null
  email?: string | null
  horarioDisponible?: string | null
  activo: boolean
}

const open = defineModel<boolean>('open', { default: false })

const props = defineProps<{
  mecanico?: MecanicoFicha | null
}>()

const emit = defineEmits<{
  deleted: []
}>()

const page = usePage()
const editing = ref(false)
const isSaving = ref(false)
const isDeleting = ref(false)
const localErrors = ref<FormErrors>({})
const backendErrors = computed(() => page.props.errors || {})
const errors = computed(() => mergeErrors(localErrors.value, backendErrors.value as Record<string, unknown>))

const state = reactive({
  nombres: '',
  apellidos: '',
  documento: '',
  telefono: '',
  email: '',
  especialidad: '',
  horarioDisponible: '',
  activo: true
})

const syncFromMecanico = () => {
  const m = props.mecanico
  if (!m) return

  const partes = (m.nombreCompleto || '').trim().split(/\s+/)
  state.nombres = m.nombres || partes.slice(0, Math.max(1, partes.length - 1)).join(' ') || ''
  state.apellidos = m.apellidos || (partes.length > 1 ? partes[partes.length - 1] : '') || ''
  state.documento = m.documento || ''
  state.telefono = m.telefono || ''
  state.email = m.email || ''
  state.especialidad = m.especialidad || ''
  state.horarioDisponible = m.horarioDisponible || ''
  state.activo = !!m.activo
  localErrors.value = {}
  editing.value = false
}

watch(
  () => [open.value, props.mecanico?.id],
  () => {
    if (open.value && props.mecanico) syncFromMecanico()
  },
  { immediate: true }
)

const validate = (): boolean => {
  const next: FormErrors = {}
  const n = validarNombre(state.nombres, 'Los nombres', true)
  if (n) next.nombres = n
  const a = validarNombre(state.apellidos, 'Los apellidos', true)
  if (a) next.apellidos = a
  const d = validarDocumento(state.documento, 'CEDULA', true)
  if (d) next.documento = d
  if (!state.especialidad.trim() || state.especialidad.trim().length < 2) {
    next.especialidad = 'La especialidad es obligatoria'
  }
  if (state.telefono) {
    const t = validarTelefono(state.telefono, false)
    if (t) next.telefono = t
  }
  if (state.email) {
    const e = validarEmail(state.email, false)
    if (e) next.email = e
  }
  localErrors.value = next
  return Object.keys(next).length === 0
}

const guardar = () => {
  if (!props.mecanico || !validate()) return
  isSaving.value = true
  router.put(route('mecanicos.update', props.mecanico.id), {
    nombres: state.nombres,
    apellidos: state.apellidos,
    documento: soloDigitos(state.documento),
    telefono: state.telefono ? soloDigitos(state.telefono) : null,
    email: state.email || null,
    especialidad: state.especialidad,
    horarioDisponible: state.horarioDisponible || null,
    activo: state.activo
  }, {
    preserveScroll: true,
    onSuccess: () => { editing.value = false },
    onFinish: () => { isSaving.value = false }
  })
}

const eliminar = () => {
  if (!props.mecanico) return
  if (!confirm(`¿Eliminar al mecánico ${props.mecanico.nombreCompleto}?`)) return
  isDeleting.value = true
  router.delete(route('mecanicos.destroy', props.mecanico.id), {
    preserveScroll: true,
    onSuccess: () => {
      open.value = false
      emit('deleted')
    },
    onFinish: () => { isDeleting.value = false }
  })
}

const cancelarEdicion = () => syncFromMecanico()
</script>

<template>
  <USlideover
    v-model:open="open"
    :title="editing ? 'Editar mecánico' : 'Ficha del mecánico'"
    :description="mecanico?.nombreCompleto || 'Detalle y gestión del especialista'"
    side="right"
  >
    <template #body>
      <div v-if="mecanico" class="space-y-5">
        <template v-if="!editing">
          <div>
            <p class="text-xs uppercase tracking-wide text-muted">Nombre</p>
            <p class="mt-1 text-lg font-semibold text-highlighted">{{ mecanico.nombreCompleto }}</p>
          </div>

          <div>
            <p class="text-xs uppercase tracking-wide text-muted">Especialidad</p>
            <UBadge class="mt-2" color="primary" variant="subtle" size="lg">
              {{ mecanico.especialidad }}
            </UBadge>
          </div>

          <div class="grid grid-cols-1 gap-4">
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Documento</p>
              <p class="mt-1 text-sm">{{ mecanico.documento }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Horario disponible</p>
              <p class="mt-1 text-sm">{{ mecanico.horarioDisponible || '—' }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Teléfono</p>
              <p class="mt-1 text-sm">{{ mecanico.telefono || '—' }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Correo</p>
              <p class="mt-1 text-sm break-all">{{ mecanico.email || '—' }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Estado</p>
              <UBadge
                class="mt-2"
                :color="mecanico.activo ? 'success' : 'neutral'"
                variant="subtle"
              >
                {{ mecanico.activo ? 'Activo' : 'Inactivo' }}
              </UBadge>
            </div>
          </div>

          <div class="flex flex-wrap gap-2 pt-2">
            <UButton icon="i-lucide-pencil" label="Editar" @click="editing = true" />
            <UButton
              variant="soft"
              icon="i-lucide-panel-right-open"
              label="Página completa"
              :to="route('mecanicos.edit', mecanico.id)"
              @click="open = false"
            />
            <UButton
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
          <UAlert
            v-if="Object.keys(localErrors).length"
            color="error"
            variant="subtle"
            icon="i-lucide-circle-alert"
            title="Revisa los datos"
            description="Corrige los campos marcados antes de guardar."
          />

          <FormField label="Nombres" name="nombres" required :error="errors.nombres">
            <UInput v-model="state.nombres" class="w-full" />
          </FormField>
          <FormField label="Apellidos" name="apellidos" required :error="errors.apellidos">
            <UInput v-model="state.apellidos" class="w-full" />
          </FormField>
          <FormField label="Documento" name="documento" required :error="errors.documento">
            <UInput v-model="state.documento" inputmode="numeric" maxlength="10" class="w-full" />
          </FormField>
          <FormField label="Especialidad" name="especialidad" required :error="errors.especialidad">
            <UInput v-model="state.especialidad" class="w-full" />
          </FormField>
          <FormField label="Teléfono" name="telefono" :error="errors.telefono">
            <UInput v-model="state.telefono" inputmode="numeric" maxlength="10" class="w-full" />
          </FormField>
          <FormField label="Email" name="email" :error="errors.email">
            <UInput v-model="state.email" type="email" class="w-full" />
          </FormField>
          <FormField label="Horario disponible" name="horarioDisponible" :error="errors.horarioDisponible">
            <UInput v-model="state.horarioDisponible" class="w-full" />
          </FormField>
          <UCheckbox v-model="state.activo" label="Mecánico activo" />

          <div class="flex flex-wrap gap-2 pt-2">
            <UButton type="submit" icon="i-lucide-save" label="Actualizar" :loading="isSaving" />
            <UButton type="button" variant="ghost" color="neutral" label="Cancelar" @click="cancelarEdicion" />
          </div>
        </form>
      </div>
      <p v-else class="text-sm text-muted">Selecciona un mecánico para ver su ficha.</p>
    </template>
  </USlideover>
</template>
