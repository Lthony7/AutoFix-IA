<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'
import {
  mergeErrors,
  soloDigitos,
  validarEmail,
  validarNombre,
  validarTelefono,
  type FormErrors
} from '../../composables/useFormValidation'

interface Perfil {
  id: string
  nombres: string
  apellidos: string
  telefono: string
  email: string
  direccion: string
  numeroDocumento: string
  tipoDocumento: string
}

const page = usePage()
const cliente = (page.props as any).cliente as Perfil | null
const localErrors = ref<FormErrors>({})
const backendErrors = computed(() => page.props.errors || {})
const errors = computed(() => mergeErrors(localErrors.value, backendErrors.value as Record<string, unknown>))

const isLoading = ref(false)
const state = reactive({
  nombres: cliente?.nombres || '',
  apellidos: cliente?.apellidos || '',
  telefono: cliente?.telefono || '',
  email: cliente?.email || '',
  direccion: cliente?.direccion || ''
})

const validate = (): boolean => {
  const next: FormErrors = {}
  const nombresErr = validarNombre(state.nombres, 'Los nombres', true)
  if (nombresErr) next.nombres = nombresErr
  const apellidosErr = validarNombre(state.apellidos, 'Los apellidos', true)
  if (apellidosErr) next.apellidos = apellidosErr
  const telErr = validarTelefono(state.telefono, true)
  if (telErr) next.telefono = telErr
  const emailErr = validarEmail(state.email, true)
  if (emailErr) next.email = emailErr
  if (!state.direccion?.trim() || state.direccion.trim().length < 5) {
    next.direccion = 'La dirección debe tener al menos 5 caracteres'
  }
  localErrors.value = next
  return Object.keys(next).length === 0
}

const handleSubmit = () => {
  if (!cliente || !validate()) return
  isLoading.value = true
  state.telefono = soloDigitos(state.telefono)
  router.put(route('portal.mis-datos.update'), state, {
    onFinish: () => { isLoading.value = false }
  })
}
</script>

<template>
  <AppDashboardPanel id="portal-perfil">
    <template #header>
      <UDashboardNavbar title="Mis datos">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <UCard v-if="cliente" class="max-w-2xl">
        <form class="grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="handleSubmit">
          <div class="md:col-span-2 rounded-md border border-default/60 bg-elevated/30 p-3 text-sm">
            <p class="text-muted">Documento</p>
            <p class="font-medium">{{ cliente.tipoDocumento }} {{ cliente.numeroDocumento }}</p>
          </div>

          <FormField label="Nombres" name="nombres" required :error="errors.nombres">
            <UInput v-model="state.nombres" class="w-full" />
          </FormField>
          <FormField label="Apellidos" name="apellidos" required :error="errors.apellidos">
            <UInput v-model="state.apellidos" class="w-full" />
          </FormField>
          <FormField label="Teléfono" name="telefono" required :error="errors.telefono">
            <UInput v-model="state.telefono" class="w-full" maxlength="10" />
          </FormField>
          <FormField label="Correo" name="email" required :error="errors.email">
            <UInput v-model="state.email" type="email" class="w-full" />
          </FormField>
          <FormField label="Dirección" name="direccion" required :error="errors.direccion" class="md:col-span-2">
            <UInput v-model="state.direccion" class="w-full" />
          </FormField>

          <div class="md:col-span-2">
            <UButton type="submit" label="Guardar cambios" :loading="isLoading" />
          </div>
        </form>
      </UCard>

      <UAlert
        v-else
        color="warning"
        variant="subtle"
        title="Sin ficha de cliente"
        description="Tu usuario no está vinculado a un cliente del taller. Contacta a recepción."
      />
    </template>
  </AppDashboardPanel>
</template>
