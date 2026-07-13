<script setup lang="ts">
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

interface Usuario {
  id: string
  name: string
  email: string
  roleLabel?: string
  activo: boolean
}

const page = usePage()
const users = computed(() => (page.props as any).users)

const destroy = (id: string) => {
  if (!confirm('¿Eliminar este usuario?')) return
  router.delete(route('usuarios.destroy', id))
}
</script>

<template>
  <UDashboardPanel id="usuarios">
    <template #header>
      <UDashboardNavbar title="Usuarios">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton icon="i-lucide-plus" label="Nuevo usuario" :to="route('usuarios.create')" />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <UCard>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left border-b border-default">
                <th class="py-3 pr-3">Nombre</th>
                <th class="py-3 pr-3">Email</th>
                <th class="py-3 pr-3">Rol</th>
                <th class="py-3 pr-3">Estado</th>
                <th class="py-3">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in (users?.data || []) as Usuario[]" :key="user.id" class="border-b border-default/60">
                <td class="py-3 pr-3 font-medium">{{ user.name }}</td>
                <td class="py-3 pr-3">{{ user.email }}</td>
                <td class="py-3 pr-3">{{ user.roleLabel }}</td>
                <td class="py-3 pr-3">
                  <UBadge :color="user.activo ? 'success' : 'neutral'" variant="subtle">
                    {{ user.activo ? 'Activo' : 'Inactivo' }}
                  </UBadge>
                </td>
                <td class="py-3 flex gap-2">
                  <UButton size="xs" variant="ghost" icon="i-lucide-pencil" :to="route('usuarios.edit', user.id)" />
                  <UButton size="xs" color="error" variant="ghost" icon="i-lucide-trash" @click="destroy(user.id)" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </UCard>
    </template>
  </UDashboardPanel>
</template>
