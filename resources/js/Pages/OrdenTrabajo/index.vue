<script setup lang="ts">
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

interface Orden {
  id: string
  numero: string
  clienteNombre: string | null
  vehiculoPlaca: string | null
  estado: string
  estadoLabel: string
  mecanicoNombre: string | null
  facturaId: string | null
  puedeFacturar: boolean
}

const page = usePage()
const ordenes = computed(() => (page.props as any).ordenes)
const role = computed(() => (page.props as any).auth?.user?.role as string | undefined)
const canDelete = computed(() => role.value !== 'mecanico')
const canFacturar = computed(() => role.value === 'administrador' || role.value === 'recepcionista')

const estadoColor = (estado: string) => {
  const map: Record<string, string> = {
    pendiente: 'warning',
    en_diagnostico: 'info',
    en_reparacion: 'primary',
    finalizada: 'success',
    entregada: 'success',
    cancelada: 'error'
  }
  return map[estado] || 'neutral'
}

const destroy = (id: string) => {
  if (!confirm('¿Eliminar esta orden de trabajo?')) return
  router.delete(route('ordenes.destroy', id))
}
</script>

<template>
  <UDashboardPanel id="ordenes">
    <template #header>
      <UDashboardNavbar title="Órdenes de trabajo">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton
            v-if="role !== 'mecanico'"
            icon="i-lucide-plus"
            label="Nueva orden"
            :to="route('ordenes.create')"
          />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <UCard>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left border-b border-default">
                <th class="py-3 pr-3">Número</th>
                <th class="py-3 pr-3">Cliente</th>
                <th class="py-3 pr-3">Placa</th>
                <th class="py-3 pr-3">Estado</th>
                <th class="py-3 pr-3">Mecánico</th>
                <th class="py-3">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="orden in (ordenes?.data || []) as Orden[]"
                :key="orden.id"
                class="border-b border-default/60"
              >
                <td class="py-3 pr-3 font-medium">{{ orden.numero }}</td>
                <td class="py-3 pr-3">{{ orden.clienteNombre || '—' }}</td>
                <td class="py-3 pr-3">{{ orden.vehiculoPlaca || '—' }}</td>
                <td class="py-3 pr-3">
                  <UBadge :color="estadoColor(orden.estado) as any" variant="subtle">
                    {{ orden.estadoLabel }}
                  </UBadge>
                </td>
                <td class="py-3 pr-3">{{ orden.mecanicoNombre || '—' }}</td>
                <td class="py-3 flex flex-wrap gap-2">
                  <UButton size="xs" variant="ghost" icon="i-lucide-pencil" :to="route('ordenes.edit', orden.id)" />
                  <UButton
                    size="xs"
                    variant="ghost"
                    color="primary"
                    icon="i-lucide-brain"
                    :to="route('diagnosticos-ia.create', { ordenTrabajoId: orden.id })"
                  />
                  <UButton
                    v-if="canFacturar && orden.facturaId"
                    size="xs"
                    variant="ghost"
                    color="success"
                    icon="i-lucide-file-text"
                    :to="route('facturas.show', orden.facturaId)"
                  />
                  <UButton
                    v-else-if="canFacturar && orden.puedeFacturar"
                    size="xs"
                    variant="ghost"
                    color="success"
                    icon="i-lucide-file-plus"
                    :to="route('facturas.create', { ordenTrabajoId: orden.id })"
                  />
                  <UButton
                    v-if="canDelete"
                    size="xs"
                    color="error"
                    variant="ghost"
                    icon="i-lucide-trash"
                    @click="destroy(orden.id)"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </UCard>
    </template>
  </UDashboardPanel>
</template>
