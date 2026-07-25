<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from './FormField.vue'
import {
  mergeErrors,
  validarNumero,
  validarPlaca,
  type FormErrors
} from '../composables/useFormValidation'

export interface ClienteOption {
  id: string
  label: string
}

export interface VehiculoFicha {
  id: string
  clienteId: string
  clienteNombre?: string | null
  placa: string
  marca: string
  modelo: string
  anio: number
  color?: string | null
  kilometraje: number
  tipoCombustible: string
  observaciones?: string | null
  activo: boolean
}

const open = defineModel<boolean>('open', { default: false })

const props = defineProps<{
  vehiculo?: VehiculoFicha | null
  clientes?: ClienteOption[]
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

const combustibleItems = [
  { label: 'Gasolina', value: 'gasolina' },
  { label: 'Diésel', value: 'diesel' },
  { label: 'Híbrido', value: 'hibrido' },
  { label: 'Eléctrico', value: 'electrico' },
  { label: 'Gas', value: 'gas' }
]

const state = reactive({
  clienteId: '',
  placa: '',
  marca: '',
  modelo: '',
  anio: new Date().getFullYear(),
  color: '',
  kilometraje: 0,
  tipoCombustible: 'gasolina',
  observaciones: '',
  activo: true
})

const syncFromVehiculo = () => {
  const v = props.vehiculo
  if (!v) return
  state.clienteId = v.clienteId || ''
  state.placa = v.placa || ''
  state.marca = v.marca || ''
  state.modelo = v.modelo || ''
  state.anio = v.anio
  state.color = v.color || ''
  state.kilometraje = v.kilometraje ?? 0
  state.tipoCombustible = v.tipoCombustible || 'gasolina'
  state.observaciones = v.observaciones || ''
  state.activo = !!v.activo
  localErrors.value = {}
  editing.value = false
}

watch(
  () => [open.value, props.vehiculo?.id],
  () => {
    if (open.value && props.vehiculo) syncFromVehiculo()
  },
  { immediate: true }
)

const combustibleLabel = computed(() =>
  combustibleItems.find(i => i.value === (props.vehiculo?.tipoCombustible || ''))?.label
  || props.vehiculo?.tipoCombustible
  || '—'
)

const validate = (): boolean => {
  const next: FormErrors = {}
  if (!state.clienteId) next.clienteId = 'El cliente es obligatorio'
  const placaErr = validarPlaca(state.placa, true)
  if (placaErr) next.placa = placaErr
  if (!state.marca?.trim()) next.marca = 'La marca es obligatoria'
  if (!state.modelo?.trim()) next.modelo = 'El modelo es obligatorio'
  const anioErr = validarNumero(state.anio, 'El año', { required: true, min: 1950, integer: true })
  if (anioErr) next.anio = anioErr
  const kmErr = validarNumero(state.kilometraje, 'El kilometraje', { required: true, min: 0, integer: true })
  if (kmErr) next.kilometraje = kmErr
  if (!state.tipoCombustible) next.tipoCombustible = 'El combustible es obligatorio'
  localErrors.value = next
  return Object.keys(next).length === 0
}

const guardar = () => {
  if (!props.vehiculo || !validate()) return
  isSaving.value = true
  router.put(route('vehiculos.update', props.vehiculo.id), {
    ...state,
    placa: state.placa.trim().toUpperCase()
  }, {
    preserveScroll: true,
    onSuccess: () => { editing.value = false },
    onFinish: () => { isSaving.value = false }
  })
}

const eliminar = () => {
  if (!props.vehiculo) return
  if (!confirm('¿Eliminar este vehículo? Esta acción no se puede deshacer.')) return
  isDeleting.value = true
  router.delete(route('vehiculos.destroy', props.vehiculo.id), {
    preserveScroll: true,
    onSuccess: () => {
      open.value = false
      emit('deleted')
    },
    onFinish: () => { isDeleting.value = false }
  })
}

const cancelarEdicion = () => {
  syncFromVehiculo()
}
</script>

<template>
  <USlideover
    v-model:open="open"
    :title="editing ? 'Editar vehículo' : 'Ficha del vehículo'"
    :description="vehiculo ? `${vehiculo.placa} — ${vehiculo.marca} ${vehiculo.modelo}` : 'Detalle y gestión del vehículo'"
    side="right"
  >
    <template #body>
      <div v-if="vehiculo" class="space-y-5">
        <template v-if="!editing">
          <div>
            <p class="text-xs uppercase tracking-wide text-muted">Placa</p>
            <p class="mt-1 text-lg font-semibold text-highlighted">{{ vehiculo.placa }}</p>
          </div>

          <div class="grid grid-cols-1 gap-4">
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Vehículo</p>
              <p class="mt-1 text-sm">{{ vehiculo.marca }} {{ vehiculo.modelo }} ({{ vehiculo.anio }})</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Cliente</p>
              <p class="mt-1 text-sm">{{ vehiculo.clienteNombre || '—' }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Color</p>
              <p class="mt-1 text-sm">{{ vehiculo.color || '—' }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Kilometraje</p>
              <p class="mt-1 text-sm">{{ Number(vehiculo.kilometraje || 0).toLocaleString() }} km</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Combustible</p>
              <p class="mt-1 text-sm">{{ combustibleLabel }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Observaciones</p>
              <p class="mt-1 text-sm whitespace-pre-wrap">{{ vehiculo.observaciones || '—' }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Estado</p>
              <UBadge
                class="mt-2"
                :color="vehiculo.activo ? 'success' : 'neutral'"
                variant="subtle"
              >
                {{ vehiculo.activo ? 'Activo' : 'Inactivo' }}
              </UBadge>
            </div>
          </div>

          <div class="flex flex-wrap gap-2 pt-2">
            <UButton icon="i-lucide-pencil" label="Editar" @click="editing = true" />
            <UButton
              variant="soft"
              icon="i-lucide-history"
              label="Historial"
              :to="route('historial.vehiculo', vehiculo.id)"
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

          <FormField label="Cliente" name="clienteId" required :error="errors.clienteId">
            <USelect
              v-model="state.clienteId"
              :items="(clientes || []).map(c => ({ label: c.label, value: c.id }))"
              class="w-full"
            />
          </FormField>
          <FormField label="Placa" name="placa" required :error="errors.placa">
            <UInput v-model="state.placa" class="w-full" />
          </FormField>
          <FormField label="Color" name="color" :error="errors.color">
            <UInput v-model="state.color" class="w-full" />
          </FormField>
          <FormField label="Marca" name="marca" required :error="errors.marca">
            <UInput v-model="state.marca" class="w-full" />
          </FormField>
          <FormField label="Modelo" name="modelo" required :error="errors.modelo">
            <UInput v-model="state.modelo" class="w-full" />
          </FormField>
          <FormField label="Año" name="anio" required :error="errors.anio">
            <UInput v-model.number="state.anio" type="number" class="w-full" />
          </FormField>
          <FormField label="Kilometraje" name="kilometraje" required :error="errors.kilometraje">
            <UInput v-model.number="state.kilometraje" type="number" min="0" class="w-full" />
          </FormField>
          <FormField label="Combustible" name="tipoCombustible" required :error="errors.tipoCombustible">
            <USelect v-model="state.tipoCombustible" :items="combustibleItems" class="w-full" />
          </FormField>
          <FormField label="Observaciones" name="observaciones" :error="errors.observaciones">
            <UTextarea v-model="state.observaciones" class="w-full" />
          </FormField>
          <UCheckbox v-model="state.activo" label="Vehículo activo" />

          <div class="flex flex-wrap gap-2 pt-2">
            <UButton type="submit" icon="i-lucide-save" label="Actualizar" :loading="isSaving" />
            <UButton type="button" variant="ghost" color="neutral" label="Cancelar" @click="cancelarEdicion" />
          </div>
        </form>
      </div>
      <p v-else class="text-sm text-muted">Selecciona un vehículo para ver su ficha.</p>
    </template>
  </USlideover>
</template>
