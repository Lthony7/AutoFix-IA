<script setup lang="ts">
import { reactive, computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'

const page = usePage()
const cliente = (page.props as any).cliente

const backendErrors = computed(() => page.props.errors || {})
const errors = computed(() => {
  const result: Record<string, string> = {}
  Object.keys(backendErrors.value).forEach((key) => {
    const error = (backendErrors.value as any)[key]
    result[key] = Array.isArray(error) ? error[0] : error
  })
  return result
})

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

const handleSubmit = () => {
  isLoading.value = true
  router.put(route('clientes.update', cliente.id), state, {
    onFinish: () => { isLoading.value = false }
  })
}
</script>

<template>
  <UDashboardPanel id="cliente-edit">
    <template #header>
      <UDashboardNavbar title="Editar cliente">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <UCard class="max-w-3xl">
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
          <FormField label="Número documento" name="numeroDocumento" required :error="errors.numeroDocumento">
            <UInput v-model="state.numeroDocumento" class="w-full" />
          </FormField>
          <FormField label="Nombres" name="nombres" :error="errors.nombres">
            <UInput v-model="state.nombres" class="w-full" />
          </FormField>
          <FormField label="Apellidos" name="apellidos" :error="errors.apellidos">
            <UInput v-model="state.apellidos" class="w-full" />
          </FormField>
          <FormField label="Razón social / Nombre" name="razonSocial" required :error="errors.razonSocial" class="md:col-span-2">
            <UInput v-model="state.razonSocial" class="w-full" />
          </FormField>
          <FormField label="Dirección" name="direccion" required :error="errors.direccion" class="md:col-span-2">
            <UInput v-model="state.direccion" class="w-full" />
          </FormField>
          <FormField label="Teléfono" name="telefono" required :error="errors.telefono">
            <UInput v-model="state.telefono" class="w-full" />
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
  </UDashboardPanel>
</template>
