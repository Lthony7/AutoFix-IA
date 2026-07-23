<script setup lang="ts">
import { reactive, computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'

const page = usePage()
const servicio = (page.props as any).servicio

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
  nombre: servicio.nombre,
  descripcion: servicio.descripcion || '',
  precioBase: servicio.precioBase,
  activo: !!servicio.activo
})

const handleSubmit = () => {
  isLoading.value = true
  router.put(route('servicios.update', servicio.id), state, {
    onFinish: () => { isLoading.value = false }
  })
}
</script>

<template>
  <AppDashboardPanel id="servicio-edit">
    <template #header>
      <UDashboardNavbar title="Editar servicio">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <UCard class="max-w-3xl">
        <form class="grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="handleSubmit">
          <FormField label="Nombre" name="nombre" required :error="errors.nombre" class="md:col-span-2">
            <UInput v-model="state.nombre" class="w-full" />
          </FormField>
          <FormField label="Descripción" name="descripcion" :error="errors.descripcion" class="md:col-span-2">
            <UTextarea v-model="state.descripcion" class="w-full" />
          </FormField>
          <FormField label="Precio base" name="precioBase" required :error="errors.precioBase">
            <UInput v-model.number="state.precioBase" type="number" min="0" step="0.01" class="w-full" />
          </FormField>
          <div class="flex items-center">
            <UCheckbox v-model="state.activo" label="Servicio activo" />
          </div>
          <div class="md:col-span-2 flex gap-3">
            <UButton type="submit" label="Actualizar" :loading="isLoading" />
            <UButton variant="ghost" color="neutral" label="Cancelar" :to="route('servicios.index')" />
          </div>
        </form>
      </UCard>
    </template>
  </AppDashboardPanel>
</template>
