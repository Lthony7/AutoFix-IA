<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'

interface Usuario {
  id: string
  name: string
  email: string
  role?: string
  roleLabel?: string
  activo: boolean
}

interface RoleOption {
  value: string
  label: string
}

const page = usePage()
const users = computed(() => (page.props as any).users)
const stats = computed(() => (page.props as any).stats || {})
const roles = computed(() => ((page.props as any).roles || []) as RoleOption[])
const filters = computed(() => (page.props as any).filters || { role: null, q: '' })

const q = ref(String(filters.value.q || ''))
const roleFilter = ref(String(filters.value.role || ''))

watch(
  () => filters.value,
  (f) => {
    q.value = String(f.q || '')
    roleFilter.value = String(f.role || '')
  }
)

const roleColor = (role?: string) => {
  const map: Record<string, string> = {
    administrador: 'primary',
    recepcionista: 'info',
    mecanico: 'warning',
    cliente: 'success'
  }
  return map[role || ''] || 'neutral'
}

const aplicarFiltros = () => {
  router.get(route('usuarios.index'), {
    q: q.value || undefined,
    role: roleFilter.value || undefined
  }, {
    preserveState: true,
    replace: true
  })
}

const filtrarPorRol = (role: string) => {
  roleFilter.value = roleFilter.value === role ? '' : role
  aplicarFiltros()
}

const destroy = (id: string) => {
  if (!confirm('¿Eliminar este usuario?')) return
  router.delete(route('usuarios.destroy', id))
}
</script>

<template>
  <AppDashboardPanel id="usuarios">
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
      <div class="space-y-4">
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
          <button
            type="button"
            class="rounded-lg border border-default p-3 text-left transition-colors hover:bg-elevated/50"
            :class="!roleFilter ? 'ring-1 ring-primary/40 bg-primary/5' : ''"
            @click="roleFilter = ''; aplicarFiltros()"
          >
            <p class="text-xs uppercase tracking-wide text-muted">Total</p>
            <p class="mt-1 text-2xl font-semibold">{{ stats.total ?? 0 }}</p>
          </button>
          <button
            v-for="rol in roles"
            :key="rol.value"
            type="button"
            class="rounded-lg border border-default p-3 text-left transition-colors hover:bg-elevated/50"
            :class="roleFilter === rol.value ? 'ring-1 ring-primary/40 bg-primary/5' : ''"
            @click="filtrarPorRol(rol.value)"
          >
            <p class="text-xs uppercase tracking-wide text-muted">{{ rol.label }}</p>
            <p class="mt-1 text-2xl font-semibold">{{ stats[rol.value] ?? 0 }}</p>
          </button>
        </div>

        <UCard>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
            <FormField label="Buscar" name="q" class="md:col-span-2">
              <UInput
                v-model="q"
                class="w-full"
                placeholder="Nombre o email…"
                icon="i-lucide-search"
                @keydown.enter.prevent="aplicarFiltros"
              />
            </FormField>
            <FormField label="Rol" name="role">
              <USelect
                v-model="roleFilter"
                :items="[
                  { label: 'Todos los roles', value: '' },
                  ...roles.map(r => ({ label: r.label, value: r.value }))
                ]"
                class="w-full"
                @update:model-value="aplicarFiltros"
              />
            </FormField>
          </div>

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
                <tr
                  v-for="user in (users?.data || []) as Usuario[]"
                  :key="user.id"
                  class="border-b border-default/60"
                >
                  <td class="py-3 pr-3 font-medium">{{ user.name }}</td>
                  <td class="py-3 pr-3">{{ user.email }}</td>
                  <td class="py-3 pr-3">
                    <UBadge :color="roleColor(user.role) as any" variant="subtle">
                      {{ user.roleLabel }}
                    </UBadge>
                  </td>
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
            <p v-if="!(users?.data || []).length" class="py-8 text-center text-muted">
              No hay usuarios con ese filtro.
            </p>
          </div>
          <AppPagination :meta="users?.meta" />
        </UCard>
      </div>
    </template>
  </AppDashboardPanel>
</template>
