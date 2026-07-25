<script setup lang="ts">
import { reactive, computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'
import AppLogo from '../../components/AppLogo.vue'

// Definir que esta página no usa layout
defineOptions({
  layout: null
})

// Estado del formulario
const state = reactive({
  email: '',
  password: '',
  remember: false
})

// Obtener errores de validación del backend
const page = usePage()
const backendErrors = computed(() => page.props.errors || {})

// Convertir errores de array a string (Laravel retorna arrays)
const errors = computed(() => {
  const result: Record<string, string> = {}
  Object.keys(backendErrors.value).forEach(key => {
    const error = backendErrors.value[key]
    result[key] = Array.isArray(error) ? error[0] : error
  })
  return result
})

// Loading state
const isLoading = ref(false)

// Submit handler
const handleSubmit = () => {
  isLoading.value = true

  // Ruta relativa: evita POST a otro host si Ziggy/APP_URL están mal configurados
  router.post('/login', {
    email: state.email.trim(),
    password: state.password,
    remember: state.remember
  }, {
    onFinish: () => {
      isLoading.value = false
    },
    onError: (errors) => {
      console.error('Errores de validación:', errors)
    }
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
              <h2 class="text-2xl font-bold">Iniciar Sesión</h2>
              <p class="text-sm text-muted">Ingresa tus credenciales para acceder al sistema</p>
            </div>
          </div>
        </template>

        <form @submit.prevent="handleSubmit" class="space-y-4">
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

          <div class="flex items-center">
            <UCheckbox v-model="state.remember" label="Recordarme" />
          </div>

          <UButton
            type="submit"
            color="primary"
            label="Iniciar Sesión"
            :loading="isLoading"
            block
            size="xl"
          />
        </form>

        <template #footer>
          <div class="space-y-2 text-center text-sm">
            <div>
              <span class="text-muted">¿No tienes una cuenta?</span>
              <UButton
                :to="route('register')"
                variant="link"
                color="primary"
                label="Regístrate"
                :padded="false"
                class="ml-1"
              />
            </div>
            <UButton
              :to="route('welcome')"
              variant="link"
              color="neutral"
              label="Volver al inicio"
              :padded="false"
            />
          </div>
        </template>
      </UCard>
    </div>
  </div>
</template>
