<script setup lang="ts">
import { reactive, computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'
import {
  mergeErrors,
  soloDigitos,
  validarDocumento,
  validarEmail,
  validarNombre,
  validarTelefono,
  type FormErrors
} from '../../composables/useFormValidation'

const page = usePage()
const usuarios = computed(() => ((page.props as any).usuarios || []) as { id: string, label: string }[])
const localErrors = ref<FormErrors>({})
const backendErrors = computed(() => page.props.errors || {})
const errors = computed(() => mergeErrors(localErrors.value, backendErrors.value as Record<string, unknown>))

const isLoading = ref(false)
const state = reactive({
  userId: '',
  nombres: '',
  apellidos: '',
  documento: '',
  telefono: '',
  email: '',
  especialidad: '',
  horarioDisponible: '',
  activo: true
})

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

const handleSubmit = () => {
  if (!validate()) return
  isLoading.value = true
  state.documento = soloDigitos(state.documento)
  if (state.telefono) state.telefono = soloDigitos(state.telefono)
  router.post(route('mecanicos.store'), state, {
    onFinish: () => { isLoading.value = false }
  })
}
</script>

<template>
  <AppDashboardPanel id="mecanico-create">
    <template #header>
      <UDashboardNavbar title="Nuevo mecánico">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <UCard class="max-w-3xl">
        <UAlert
          v-if="Object.keys(localErrors).length"
          class="mb-4"
          color="error"
          variant="subtle"
          icon="i-lucide-circle-alert"
          title="Revisa los datos del formulario"
          description="Corrige los campos marcados antes de continuar."
        />
        <form class="grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="handleSubmit">
          <FormField label="Usuario vinculado (opcional)" name="userId" :error="errors.userId" class="md:col-span-2">
            <USelect
              v-model="state.userId"
              :items="[{ label: 'Sin usuario', value: '' }, ...usuarios.map(u => ({ label: u.label, value: u.id }))]"
              class="w-full"
            />
          </FormField>
          <FormField label="Nombres" name="nombres" required :error="errors.nombres" hint="Solo letras">
            <UInput v-model="state.nombres" class="w-full" />
          </FormField>
          <FormField label="Apellidos" name="apellidos" required :error="errors.apellidos" hint="Solo letras">
            <UInput v-model="state.apellidos" class="w-full" />
          </FormField>
          <FormField label="Documento" name="documento" required :error="errors.documento" hint="6–10 dígitos">
            <UInput v-model="state.documento" inputmode="numeric" maxlength="10" class="w-full" />
          </FormField>
          <FormField label="Especialidad" name="especialidad" required :error="errors.especialidad">
            <UInput v-model="state.especialidad" class="w-full" />
          </FormField>
          <FormField label="Teléfono" name="telefono" :error="errors.telefono" hint="10 dígitos si se ingresa">
            <UInput v-model="state.telefono" inputmode="numeric" maxlength="10" class="w-full" />
          </FormField>
          <FormField label="Email" name="email" :error="errors.email">
            <UInput v-model="state.email" type="email" class="w-full" />
          </FormField>
          <FormField label="Horario disponible" name="horarioDisponible" :error="errors.horarioDisponible" class="md:col-span-2">
            <UInput v-model="state.horarioDisponible" placeholder="Lun-Vie 08:00-17:00" class="w-full" />
          </FormField>
          <div class="md:col-span-2">
            <UCheckbox v-model="state.activo" label="Mecánico activo" />
          </div>
          <div class="md:col-span-2 flex gap-3">
            <UButton type="submit" label="Guardar" :loading="isLoading" />
            <UButton variant="ghost" color="neutral" label="Cancelar" :to="route('mecanicos.index')" />
          </div>
        </form>
      </UCard>
    </template>
  </AppDashboardPanel>
</template>
