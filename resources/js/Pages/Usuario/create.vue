<script setup lang="ts">
import { reactive, computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'
import {
  mergeErrors,
  validarEmail,
  validarNombre,
  type FormErrors
} from '../../composables/useFormValidation'

const page = usePage()
const roles = computed(() => ((page.props as any).roles || []) as { value: string, label: string }[])
const localErrors = ref<FormErrors>({})
const backendErrors = computed(() => page.props.errors || {})
const errors = computed(() => mergeErrors(localErrors.value, backendErrors.value as Record<string, unknown>))

const isLoading = ref(false)
const state = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'recepcionista',
  activo: true
})

const validate = (): boolean => {
  const next: FormErrors = {}
  const n = validarNombre(state.name, 'El nombre', true)
  if (n) next.name = n
  const e = validarEmail(state.email, true)
  if (e) next.email = e
  if (!state.role) next.role = 'El rol es obligatorio'
  if (!state.password || state.password.length < 8) {
    next.password = 'La contraseña debe tener al menos 8 caracteres'
  } else if (state.password !== state.password_confirmation) {
    next.password = 'Las contraseñas no coinciden'
  }
  localErrors.value = next
  return Object.keys(next).length === 0
}

const handleSubmit = () => {
  if (!validate()) return
  isLoading.value = true
  router.post(route('usuarios.store'), state, {
    onFinish: () => { isLoading.value = false }
  })
}
</script>

<template>
  <AppDashboardPanel id="usuario-create">
    <template #header>
      <UDashboardNavbar title="Nuevo usuario">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <UCard class="max-w-2xl">
        <UAlert
          v-if="Object.keys(localErrors).length"
          class="mb-4"
          color="error"
          variant="subtle"
          icon="i-lucide-circle-alert"
          title="Revisa los datos del formulario"
          description="Corrige los campos marcados antes de continuar."
        />
        <form class="space-y-4" @submit.prevent="handleSubmit">
          <FormField label="Nombre" name="name" required :error="errors.name" hint="Solo letras">
            <UInput v-model="state.name" class="w-full" />
          </FormField>
          <FormField label="Email" name="email" required :error="errors.email">
            <UInput v-model="state.email" type="email" class="w-full" />
          </FormField>
          <FormField label="Rol" name="role" required :error="errors.role">
            <USelect v-model="state.role" :items="roles.map(r => ({ label: r.label, value: r.value }))" class="w-full" />
          </FormField>
          <FormField label="Contraseña" name="password" required :error="errors.password" hint="Mínimo 8 caracteres">
            <UInput v-model="state.password" type="password" class="w-full" />
          </FormField>
          <FormField label="Confirmar contraseña" name="password_confirmation" required>
            <UInput v-model="state.password_confirmation" type="password" class="w-full" />
          </FormField>
          <UCheckbox v-model="state.activo" label="Usuario activo" />
          <div class="flex gap-3">
            <UButton type="submit" label="Guardar" :loading="isLoading" />
            <UButton variant="ghost" color="neutral" label="Cancelar" :to="route('usuarios.index')" />
          </div>
        </form>
      </UCard>
    </template>
  </AppDashboardPanel>
</template>
