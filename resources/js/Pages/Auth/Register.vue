<script setup lang="ts">
import { reactive, computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'
import AppLogo from '../../components/AppLogo.vue'
import {
  mergeErrors,
  validarEmail,
  validarNombre,
  type FormErrors
} from '../../composables/useFormValidation'

defineOptions({
  layout: null
})

const state = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
})

const page = usePage()
const localErrors = ref<FormErrors>({})
const backendErrors = computed(() => page.props.errors || {})
const errors = computed(() => mergeErrors(localErrors.value, backendErrors.value as Record<string, unknown>))

const isLoading = ref(false)

const validate = (): boolean => {
  const next: FormErrors = {}
  const n = validarNombre(state.name, 'El nombre', true)
  if (n) next.name = n
  const e = validarEmail(state.email, true)
  if (e) next.email = e
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
  router.post(route('register'), state, {
    onFinish: () => { isLoading.value = false }
  })
}
</script>

<template>
  <div class="flex min-h-dvh items-center justify-center overflow-y-auto bg-background p-4">
    <div class="w-full max-w-md">
      <UCard>
        <template #header>
          <div class="space-y-4 mb-2">
            <AppLogo size="lg" />
            <div>
              <h2 class="text-2xl font-bold">Crear Cuenta</h2>
              <p class="text-sm text-muted">Completa el formulario para registrarte en el sistema</p>
            </div>
          </div>
        </template>

        <form @submit.prevent="handleSubmit" class="space-y-4">
          <UAlert
            v-if="Object.keys(localErrors).length"
            color="error"
            variant="subtle"
            icon="i-lucide-circle-alert"
            title="Revisa los datos del formulario"
            description="Corrige los campos marcados antes de continuar."
          />
          <FormField label="Nombre completo" name="name" required :error="errors.name" hint="Solo letras">
            <UInput
              v-model="state.name"
              type="text"
              placeholder="Juan Pérez"
              icon="i-lucide-user"
              size="xl"
              class="w-full"
            />
          </FormField>

          <FormField label="Correo electrónico" name="email" required :error="errors.email">
            <UInput
              v-model="state.email"
              type="email"
              placeholder="tu@email.com"
              icon="i-lucide-mail"
              size="xl"
              class="w-full"
            />
          </FormField>

          <FormField label="Contraseña" name="password" required :error="errors.password">
            <UInput
              v-model="state.password"
              type="password"
              placeholder="••••••••"
              icon="i-lucide-lock"
              size="xl"
              class="w-full"
            />
          </FormField>

          <FormField label="Confirmar contraseña" name="password_confirmation" required :error="errors.password_confirmation">
            <UInput
              v-model="state.password_confirmation"
              type="password"
              placeholder="••••••••"
              icon="i-lucide-lock"
              size="xl"
              class="w-full"
            />
          </FormField>

          <UButton
            type="submit"
            color="primary"
            label="Registrarse"
            :loading="isLoading"
            block
            size="xl"
          />
        </form>

        <template #footer>
          <div class="text-center text-sm">
            <span class="text-muted">¿Ya tienes una cuenta?</span>
            <UButton
              :to="route('login')"
              variant="link"
              color="primary"
              label="Inicia sesión"
              :padded="false"
              class="ml-1"
            />
          </div>
        </template>
      </UCard>
    </div>
  </div>
</template>
