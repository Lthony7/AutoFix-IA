<script setup lang="ts">
import { reactive, computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'
import ModulePanel from '../../components/ModulePanel.vue'
import MecanicoEspecialidadHorarioFields from '../../components/MecanicoEspecialidadHorarioFields.vue'
import {
  mergeErrors,
  soloDigitos,
  validarDocumento,
  validarEmail,
  validarNombre,
  validarTelefono,
  type FormErrors
} from '../../composables/useFormValidation'
import {
  decodeEspecialidades,
  decodeHorarioSemanal,
  validarEspecialidadesHorario
} from '../../composables/useMecanicoPerfilTaller'

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

  const perfil = validarEspecialidadesHorario(
    decodeEspecialidades(state.especialidad),
    decodeHorarioSemanal(state.horarioDisponible)
  )
  Object.assign(next, perfil)

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
  router.post(route('mecanicos.store'), {
    ...state,
    horarioDisponible: state.horarioDisponible || null
  }, {
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
      <div class="flex w-full min-w-0 flex-col gap-4">
        <UAlert
          v-if="Object.keys(localErrors).length"
          color="error"
          variant="subtle"
          icon="i-lucide-circle-alert"
          title="Revisa los datos del formulario"
          description="Corrige los campos marcados antes de continuar."
        />

        <ModulePanel title="Datos del mecánico" class="w-full">
          <form id="form-nuevo-mecanico" class="grid w-full grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3" @submit.prevent="handleSubmit">
            <FormField label="Usuario vinculado (opcional)" name="userId" :error="errors.userId" class="md:col-span-2 xl:col-span-3">
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
            <FormField label="Teléfono" name="telefono" :error="errors.telefono" hint="10 dígitos si se ingresa">
              <UInput v-model="state.telefono" inputmode="numeric" maxlength="10" class="w-full" />
            </FormField>
            <FormField label="Email" name="email" :error="errors.email" class="md:col-span-2 xl:col-span-1">
              <UInput v-model="state.email" type="email" class="w-full" />
            </FormField>
            <div class="md:col-span-2 xl:col-span-3">
              <UCheckbox v-model="state.activo" label="Mecánico activo" />
            </div>
          </form>
        </ModulePanel>

        <MecanicoEspecialidadHorarioFields
          v-model:especialidad="state.especialidad"
          v-model:horario-disponible="state.horarioDisponible"
          :error-especialidad="errors.especialidad"
          :error-horario="errors.horarioDisponible"
        />

        <div class="flex flex-wrap gap-3">
          <UButton
            type="submit"
            form="form-nuevo-mecanico"
            color="success"
            label="Guardar"
            :loading="isLoading"
          />
          <UButton variant="ghost" color="neutral" label="Cancelar" :to="route('mecanicos.index')" />
        </div>
      </div>
    </template>
  </AppDashboardPanel>
</template>
