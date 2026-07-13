<script setup lang="ts">
import { reactive, computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'

const page = usePage()
const usuario = (page.props as any).usuario
const roles = computed(() => ((page.props as any).roles || []) as { value: string, label: string }[])
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
  name: usuario.name,
  email: usuario.email,
  password: '',
  password_confirmation: '',
  role: usuario.role,
  activo: !!usuario.activo
})

const handleSubmit = () => {
  isLoading.value = true
  router.put(route('usuarios.update', usuario.id), state, {
    onFinish: () => { isLoading.value = false }
  })
}
</script>

<template>
  <UDashboardPanel id="usuario-edit">
    <template #header>
      <UDashboardNavbar title="Editar usuario">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <UCard class="max-w-2xl">
        <form class="space-y-4" @submit.prevent="handleSubmit">
          <FormField label="Nombre" name="name" required :error="errors.name">
            <UInput v-model="state.name" class="w-full" />
          </FormField>
          <FormField label="Email" name="email" required :error="errors.email">
            <UInput v-model="state.email" type="email" class="w-full" />
          </FormField>
          <FormField label="Rol" name="role" required :error="errors.role">
            <USelect v-model="state.role" :items="roles.map(r => ({ label: r.label, value: r.value }))" class="w-full" />
          </FormField>
          <FormField label="Nueva contraseña (opcional)" name="password" :error="errors.password">
            <UInput v-model="state.password" type="password" class="w-full" />
          </FormField>
          <FormField label="Confirmar contraseña" name="password_confirmation">
            <UInput v-model="state.password_confirmation" type="password" class="w-full" />
          </FormField>
          <UCheckbox v-model="state.activo" label="Usuario activo" />
          <div class="flex gap-3">
            <UButton type="submit" label="Actualizar" :loading="isLoading" />
            <UButton variant="ghost" color="neutral" label="Cancelar" :to="route('usuarios.index')" />
          </div>
        </form>
      </UCard>
    </template>
  </UDashboardPanel>
</template>
