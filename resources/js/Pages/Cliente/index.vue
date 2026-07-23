<script setup lang="ts">
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

interface Cliente {
  id: string
  nombreCompleto: string
  numeroDocumento: string
  telefono: string
  email: string
  estado: boolean
}

const page = usePage()
const customers = computed(() => (page.props as any).customers)
const stats = computed(() => (page.props as any).stats)

const destroy = (id: string) => {
  if (!confirm('¿Eliminar este cliente?')) return
  router.delete(route('clientes.destroy', id))
}
</script>

<template>
  <AppDashboardPanel id="clientes">
    <template #header>
      <UDashboardNavbar title="Clientes">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton icon="i-lucide-plus" label="Nuevo cliente" :to="route('clientes.create')" />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <UCard><p class="text-sm text-muted">Total</p><p class="text-2xl font-semibold">{{ stats?.total ?? 0 }}</p></UCard>
        <UCard><p class="text-sm text-muted">Activos</p><p class="text-2xl font-semibold">{{ stats?.active ?? 0 }}</p></UCard>
        <UCard><p class="text-sm text-muted">Inactivos</p><p class="text-2xl font-semibold">{{ stats?.inactive ?? 0 }}</p></UCard>
      </div>

      <UCard>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left border-b border-default">
                <th class="py-3 pr-3">Cliente</th>
                <th class="py-3 pr-3">Documento</th>
                <th class="py-3 pr-3">Teléfono</th>
                <th class="py-3 pr-3">Email</th>
                <th class="py-3 pr-3">Estado</th>
                <th class="py-3">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="cliente in (customers?.data || []) as Cliente[]" :key="cliente.id" class="border-b border-default/60">
                <td class="py-3 pr-3 font-medium">{{ cliente.nombreCompleto }}</td>
                <td class="py-3 pr-3">{{ cliente.numeroDocumento }}</td>
                <td class="py-3 pr-3">{{ cliente.telefono }}</td>
                <td class="py-3 pr-3">{{ cliente.email }}</td>
                <td class="py-3 pr-3">
                  <UBadge :color="cliente.estado ? 'success' : 'neutral'" variant="subtle">
                    {{ cliente.estado ? 'Activo' : 'Inactivo' }}
                  </UBadge>
                </td>
                <td class="py-3 flex gap-2">
                  <UButton size="xs" variant="ghost" icon="i-lucide-pencil" :to="route('clientes.edit', cliente.id)" />
                  <UButton size="xs" color="error" variant="ghost" icon="i-lucide-trash" @click="destroy(cliente.id)" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <AppPagination :meta="customers?.meta" />
      </UCard>
    </template>
  </AppDashboardPanel>
</template>
