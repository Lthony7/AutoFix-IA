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
const cliente = (page.props as any).cliente
const localErrors = ref<FormErrors>({})
const backendErrors = computed(() => page.props.errors || {})
const errors = computed(() => mergeErrors(localErrors.value, backendErrors.value as Record<string, unknown>))

const isLoading = ref(false)
const state = reactive({
  tipoDocumento: cliente.tipoDocumento,
  numeroDocumento: cliente.numeroDocumento,
  nombres: cliente.nombres || '',
  apellidos: cliente.apellidos || '',
  razonSocial: cliente.razonSocial,
  direccion: cliente.direccion,
  telefono: cliente.telefono,
  email: cliente.email,
  estado: !!cliente.estado
})

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

const handleSubmit = () => {
  if (!validate()) return
  isLoading.value = true
  state.telefono = soloDigitos(state.telefono)
  if (['CEDULA', 'DNI', 'RUC'].includes(state.tipoDocumento)) {
    state.numeroDocumento = soloDigitos(state.numeroDocumento)
  }
  router.put(route('clientes.update', cliente.id), state, {
    onFinish: () => { isLoading.value = false }
  })
}
</script>

<template>
  <AppDashboardPanel id="cliente-edit">
    <template #header>
      <UDashboardNavbar title="Editar cliente">
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
          <FormField
            label="Número documento"
            name="numeroDocumento"
            required
            :error="errors.numeroDocumento || errors.numero_documento"
            hint="Cédula/DNI: 6–10 dígitos"
          >
            <UInput v-model="state.numeroDocumento" class="w-full" maxlength="20" />
          </FormField>
          <FormField label="Nombres" name="nombres" :error="errors.nombres" hint="Solo letras">
            <UInput v-model="state.nombres" class="w-full" />
          </FormField>
          <FormField label="Apellidos" name="apellidos" :error="errors.apellidos" hint="Solo letras">
            <UInput v-model="state.apellidos" class="w-full" />
          </FormField>
          <FormField label="Razón social / Nombre" name="razonSocial" required :error="errors.razonSocial || errors.razon_social" class="md:col-span-2">
            <UInput v-model="state.razonSocial" class="w-full" />
          </FormField>
          <FormField label="Dirección" name="direccion" required :error="errors.direccion" class="md:col-span-2">
            <UInput v-model="state.direccion" class="w-full" />
          </FormField>
          <FormField label="Teléfono" name="telefono" required :error="errors.telefono" hint="Exactamente 10 dígitos">
            <UInput v-model="state.telefono" inputmode="numeric" maxlength="10" class="w-full" />
          </FormField>
          <FormField label="Email" name="email" required :error="errors.email">
            <UInput v-model="state.email" type="email" class="w-full" />
          </FormField>
          <div class="md:col-span-2 flex items-center gap-2">
            <UCheckbox v-model="state.estado" label="Cliente activo" />
          </div>
          <div class="md:col-span-2 flex gap-3">
            <UButton type="submit" label="Actualizar" :loading="isLoading" />
            <UButton variant="ghost" color="neutral" label="Cancelar" :to="route('clientes.index')" />
          </div>
        </form>
      </UCard>
    </template>
  </AppDashboardPanel>
</template>
