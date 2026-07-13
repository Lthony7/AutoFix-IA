<script setup lang="ts">
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

interface Mecanico {
  id: string
  nombreCompleto: string
  documento: string
  especialidad: string
  telefono?: string
  horarioDisponible?: string
  activo: boolean
}

const page = usePage()
const mecanicos = computed(() => (page.props as any).mecanicos)

const destroy = (id: string) => {
  if (!confirm('¿Eliminar este mecánico?')) return
  router.delete(route('mecanicos.destroy', id))
}
</script>

<template>
  <UDashboardPanel id="mecanicos">
    <template #header>
      <UDashboardNavbar title="Mecánicos">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton icon="i-lucide-plus" label="Nuevo mecánico" :to="route('mecanicos.create')" />
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
                <th class="py-3 pr-3">Documento</th>
                <th class="py-3 pr-3">Especialidad</th>
                <th class="py-3 pr-3">Horario</th>
                <th class="py-3 pr-3">Estado</th>
                <th class="py-3">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in (mecanicos?.data || []) as Mecanico[]" :key="item.id" class="border-b border-default/60">
                <td class="py-3 pr-3 font-medium">{{ item.nombreCompleto }}</td>
                <td class="py-3 pr-3">{{ item.documento }}</td>
                <td class="py-3 pr-3">{{ item.especialidad }}</td>
                <td class="py-3 pr-3">{{ item.horarioDisponible || '—' }}</td>
                <td class="py-3 pr-3">
                  <UBadge :color="item.activo ? 'success' : 'neutral'" variant="subtle">
                    {{ item.activo ? 'Activo' : 'Inactivo' }}
                  </UBadge>
                </td>
                <td class="py-3 flex gap-2">
                  <UButton size="xs" variant="ghost" icon="i-lucide-pencil" :to="route('mecanicos.edit', item.id)" />
                  <UButton size="xs" color="error" variant="ghost" icon="i-lucide-trash" @click="destroy(item.id)" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </UCard>
    </template>
  </UDashboardPanel>
</template>
