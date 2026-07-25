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

export interface ClienteVehiculoResumen {
  id: string
  placa: string
  marca: string
  modelo: string
  anio: number
  color?: string | null
}

export interface ClienteFicha {
  id: string
  tipoDocumento: string
  numeroDocumento: string
  nombres?: string | null
  apellidos?: string | null
  nombreCompleto: string
  razonSocial: string
  direccion: string
  telefono: string
  email: string
  estado: boolean
  vehiculos?: ClienteVehiculoResumen[]
}

const open = defineModel<boolean>('open', { default: false })

const props = defineProps<{
  cliente?: ClienteFicha | null
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
  tipoDocumento: 'CEDULA',
  numeroDocumento: '',
  nombres: '',
  apellidos: '',
  razonSocial: '',
  direccion: '',
  telefono: '',
  email: '',
  estado: true
})

const syncFromCliente = () => {
  const c = props.cliente
  if (!c) return
  state.tipoDocumento = c.tipoDocumento || 'CEDULA'
  state.numeroDocumento = c.numeroDocumento || ''
  state.nombres = c.nombres || ''
  state.apellidos = c.apellidos || ''
  state.razonSocial = c.razonSocial || c.nombreCompleto || ''
  state.direccion = c.direccion || ''
  state.telefono = c.telefono || ''
  state.email = c.email || ''
  state.estado = !!c.estado
  localErrors.value = {}
  editing.value = false
}

watch(
  () => [open.value, props.cliente?.id],
  () => {
    if (open.value && props.cliente) syncFromCliente()
  },
  { immediate: true }
)

const validate = (): boolean => {
  const next: FormErrors = {}
  const docErr = validarDocumento(state.numeroDocumento, state.tipoDocumento, true)
  if (docErr) next.numeroDocumento = docErr
  if (state.nombres) {
    const e = validarNombre(state.nombres, 'Los nombres', false)
    if (e) next.nombres = e
  }
  if (state.apellidos) {
    const e = validarNombre(state.apellidos, 'Los apellidos', false)
    if (e) next.apellidos = e
  }
  if (!state.razonSocial?.trim() || state.razonSocial.trim().length < 2) {
    next.razonSocial = 'El nombre o razón social es obligatorio'
  }
  if (!state.direccion?.trim() || state.direccion.trim().length < 5) {
    next.direccion = 'La dirección debe tener al menos 5 caracteres'
  }
  const telErr = validarTelefono(state.telefono, true)
  if (telErr) next.telefono = telErr
  const emailErr = validarEmail(state.email, true)
  if (emailErr) next.email = emailErr
  localErrors.value = next
  return Object.keys(next).length === 0
}

const guardar = () => {
  if (!props.cliente || !validate()) return
  isSaving.value = true
  const payload = {
    ...state,
    telefono: soloDigitos(state.telefono),
    numeroDocumento: ['CEDULA', 'DNI', 'RUC'].includes(state.tipoDocumento)
      ? soloDigitos(state.numeroDocumento)
      : state.numeroDocumento
  }
  router.put(route('clientes.update', props.cliente.id), payload, {
    preserveScroll: true,
    onSuccess: () => { editing.value = false },
    onFinish: () => { isSaving.value = false }
  })
}

const eliminar = () => {
  if (!props.cliente) return
  if (!confirm('¿Eliminar este cliente? Esta acción no se puede deshacer.')) return
  isDeleting.value = true
  router.delete(route('clientes.destroy', props.cliente.id), {
    preserveScroll: true,
    onSuccess: () => {
      open.value = false
      emit('deleted')
    },
    onFinish: () => { isDeleting.value = false }
  })
}

const cancelarEdicion = () => {
  syncFromCliente()
}
</script>

<template>
  <USlideover
    v-model:open="open"
    :title="editing ? 'Editar cliente' : 'Ficha del cliente'"
    :description="cliente?.nombreCompleto || 'Detalle y gestión del cliente'"
    side="right"
  >
    <template #body>
      <div v-if="cliente" class="space-y-5">
        <template v-if="!editing">
          <div>
            <p class="text-xs uppercase tracking-wide text-muted">Nombre</p>
            <p class="mt-1 text-lg font-semibold text-highlighted">{{ cliente.nombreCompleto }}</p>
          </div>

          <div class="grid grid-cols-1 gap-4">
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Documento</p>
              <p class="mt-1 text-sm">{{ cliente.tipoDocumento }} {{ cliente.numeroDocumento }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Razón social</p>
              <p class="mt-1 text-sm">{{ cliente.razonSocial || '—' }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Dirección</p>
              <p class="mt-1 text-sm">{{ cliente.direccion || '—' }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Teléfono</p>
              <p class="mt-1 text-sm">{{ cliente.telefono || '—' }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Correo</p>
              <p class="mt-1 text-sm break-all">{{ cliente.email || '—' }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Estado</p>
              <UBadge
                class="mt-2"
                :color="cliente.estado ? 'success' : 'neutral'"
                variant="subtle"
              >
                {{ cliente.estado ? 'Activo' : 'Inactivo' }}
              </UBadge>
            </div>
          </div>

          <div v-if="cliente.vehiculos?.length" class="space-y-2">
            <p class="text-xs uppercase tracking-wide text-muted">Vehículos ({{ cliente.vehiculos.length }})</p>
            <ul class="space-y-2">
              <li
                v-for="v in cliente.vehiculos"
                :key="v.id"
                class="rounded-md border border-default/60 bg-elevated/30 px-3 py-2 text-sm"
              >
                <span class="font-medium">{{ v.placa }}</span>
                <span class="text-muted"> — {{ v.marca }} {{ v.modelo }} ({{ v.anio }})</span>
              </li>
            </ul>
          </div>
          <p v-else class="text-sm text-muted">Sin vehículos asociados.</p>

          <div class="flex flex-wrap gap-2 pt-2">
            <UButton icon="i-lucide-pencil" label="Editar" @click="editing = true" />
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

          <FormField label="Tipo documento" name="tipoDocumento" required :error="errors.tipoDocumento">
            <USelect
              v-model="state.tipoDocumento"
              :items="[
                { label: 'Cédula', value: 'CEDULA' },
                { label: 'DNI', value: 'DNI' },
                { label: 'RUC', value: 'RUC' },
                { label: 'CE', value: 'CE' },
                { label: 'Pasaporte', value: 'PASAPORTE' }
              ]"
              class="w-full"
            />
          </FormField>
          <FormField label="Número documento" name="numeroDocumento" required :error="errors.numeroDocumento">
            <UInput v-model="state.numeroDocumento" class="w-full" maxlength="20" />
          </FormField>
          <FormField label="Nombres" name="nombres" :error="errors.nombres">
            <UInput v-model="state.nombres" class="w-full" />
          </FormField>
          <FormField label="Apellidos" name="apellidos" :error="errors.apellidos">
            <UInput v-model="state.apellidos" class="w-full" />
          </FormField>
          <FormField label="Razón social / Nombre" name="razonSocial" required :error="errors.razonSocial">
            <UInput v-model="state.razonSocial" class="w-full" />
          </FormField>
          <FormField label="Dirección" name="direccion" required :error="errors.direccion">
            <UInput v-model="state.direccion" class="w-full" />
          </FormField>
          <FormField label="Teléfono" name="telefono" required :error="errors.telefono">
            <UInput v-model="state.telefono" inputmode="numeric" maxlength="10" class="w-full" />
          </FormField>
          <FormField label="Email" name="email" required :error="errors.email">
            <UInput v-model="state.email" type="email" class="w-full" />
          </FormField>
          <UCheckbox v-model="state.estado" label="Cliente activo" />

          <div class="flex flex-wrap gap-2 pt-2">
            <UButton type="submit" icon="i-lucide-save" label="Actualizar" :loading="isSaving" />
            <UButton type="button" variant="ghost" color="neutral" label="Cancelar" @click="cancelarEdicion" />
          </div>
        </form>
      </div>
      <p v-else class="text-sm text-muted">Selecciona un cliente para ver su ficha.</p>
    </template>
  </USlideover>
</template>
