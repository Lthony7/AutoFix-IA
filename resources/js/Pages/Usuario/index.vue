<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'
import MetricStatCards, { type MetricCard } from '../../components/MetricStatCards.vue'
import ModulePanel from '../../components/ModulePanel.vue'

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

const badgeClass = (role?: string) => {
  const map: Record<string, string> = {
    administrador: 'autofix-badge-solid--warn',
    recepcionista: 'autofix-badge-solid--ok',
    mecanico: 'autofix-badge-solid--neutral',
    cliente: 'autofix-badge-solid--ok'
  }
  return map[role || ''] || 'autofix-badge-solid--neutral'
}

const roleUrl = (role: string) => {
  const params = new URLSearchParams()
  if (q.value) params.set('q', q.value)
  if (role) params.set('role', role)
  const qs = params.toString()
  return route('usuarios.index') + (qs ? `?${qs}` : '')
}

const metricCards = computed((): MetricCard[] => [
  {
    key: 'total',
    title: 'Total',
    value: stats.value.total ?? 0,
    icon: 'i-lucide-users',
    tone: 'green',
    to: roleUrl('')
  },
  {
    key: 'administrador',
    title: 'Administradores',
    value: stats.value.administrador ?? 0,
    icon: 'i-lucide-shield',
    tone: 'purple',
    to: roleUrl('administrador')
  },
  {
    key: 'recepcionista',
    title: 'Recepcionistas',
    value: stats.value.recepcionista ?? 0,
    icon: 'i-lucide-headset',
    tone: 'blue',
    to: roleUrl('recepcionista')
  },
  {
    key: 'mecanico',
    title: 'Mecánicos',
    value: stats.value.mecanico ?? 0,
    icon: 'i-lucide-wrench',
    tone: 'lime',
    to: roleUrl('mecanico')
  }
])

const aplicarFiltros = () => {
  router.get(route('usuarios.index'), {
    q: q.value || undefined,
    role: roleFilter.value || undefined
  }, {
    preserveState: true,
    replace: true
  })
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
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="space-y-4">
        <MetricStatCards
          :cards="metricCards"
          :columns="4"
          :model-value="roleFilter || 'total'"
        />

        <ModulePanel title="Usuarios">
          <template #actions>
            <UButton
              icon="i-lucide-plus"
              label="Nuevo usuario"
              color="success"
              :to="route('usuarios.create')"
            />
          </template>

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
                    <span class="autofix-badge-solid" :class="badgeClass(user.role)">
                      {{ user.roleLabel }}
                    </span>
                  </td>
                  <td class="py-3 pr-3">
                    <span
                      class="autofix-badge-solid"
                      :class="user.activo ? 'autofix-badge-solid--ok' : 'autofix-badge-solid--neutral'"
                    >
                      {{ user.activo ? 'Activo' : 'Inactivo' }}
                    </span>
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
        </ModulePanel>
      </div>
    </template>
  </AppDashboardPanel>
</template>
