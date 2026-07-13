<script setup lang="ts">
import { reactive, computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'

const page = usePage()
const mecanico = (page.props as any).mecanico
const usuarios = computed(() => ((page.props as any).usuarios || []) as { id: string, label: string }[])
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
  userId: mecanico.userId || '',
  nombres: mecanico.nombres,
  apellidos: mecanico.apellidos,
  documento: mecanico.documento,
  telefono: mecanico.telefono || '',
  email: mecanico.email || '',
  especialidad: mecanico.especialidad,
  horarioDisponible: mecanico.horarioDisponible || '',
  activo: !!mecanico.activo
})

const handleSubmit = () => {
  isLoading.value = true
  router.put(route('mecanicos.update', mecanico.id), state, {
    onFinish: () => { isLoading.value = false }
  })
}
</script>

<template>
  <UDashboardPanel id="mecanico-edit">
    <template #header>
      <UDashboardNavbar title="Editar mecánico">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <UCard class="max-w-3xl">
        <form class="grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="handleSubmit">
          <FormField label="Usuario vinculado (opcional)" name="userId" :error="errors.userId" class="md:col-span-2">
            <USelect
              v-model="state.userId"
              :items="[{ label: 'Sin usuario', value: '' }, ...usuarios.map(u => ({ label: u.label, value: u.id }))]"
              class="w-full"
            />
          </FormField>
          <FormField label="Nombres" name="nombres" required :error="errors.nombres">
            <UInput v-model="state.nombres" class="w-full" />
          </FormField>
          <FormField label="Apellidos" name="apellidos" required :error="errors.apellidos">
            <UInput v-model="state.apellidos" class="w-full" />
          </FormField>
          <FormField label="Documento" name="documento" required :error="errors.documento">
            <UInput v-model="state.documento" class="w-full" />
          </FormField>
          <FormField label="Especialidad" name="especialidad" required :error="errors.especialidad">
            <UInput v-model="state.especialidad" class="w-full" />
          </FormField>
          <FormField label="Teléfono" name="telefono" :error="errors.telefono">
            <UInput v-model="state.telefono" class="w-full" />
          </FormField>
          <FormField label="Email" name="email" :error="errors.email">
            <UInput v-model="state.email" type="email" class="w-full" />
          </FormField>
          <FormField label="Horario disponible" name="horarioDisponible" :error="errors.horarioDisponible" class="md:col-span-2">
            <UInput v-model="state.horarioDisponible" class="w-full" />
          </FormField>
          <div class="md:col-span-2">
            <UCheckbox v-model="state.activo" label="Mecánico activo" />
          </div>
          <div class="md:col-span-2 flex gap-3">
            <UButton type="submit" label="Actualizar" :loading="isLoading" />
            <UButton variant="ghost" color="neutral" label="Cancelar" :to="route('mecanicos.index')" />
          </div>
        </form>
      </UCard>
    </template>
  </UDashboardPanel>
</template>
