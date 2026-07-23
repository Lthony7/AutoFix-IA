<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

interface Diagnostico {
  id: string
  ordenTrabajoId: string
  ordenNumero: string | null
  clienteNombre: string | null
  vehiculoPlaca: string | null
  prioridad: string | null
  servicioRecomendado: string | null
  estado: string
  estadoLabel: string
  esSimulado: boolean
  createdAt: string
}

const page = usePage()
const diagnosticos = computed(() => (page.props as any).diagnosticos)

const estadoColor = (estado: string) => {
  const map: Record<string, string> = {
    generada: 'info',
    confirmada: 'success',
    modificada: 'warning',
    descartada: 'error'
  }
  return map[estado] || 'neutral'
}
</script>

<template>
  <AppDashboardPanel id="diagnosticos-ia">
    <template #header>
      <UDashboardNavbar title="Diagnósticos IA">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton icon="i-lucide-plus" label="Nuevo diagnóstico" :to="route('diagnosticos-ia.create')" />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="space-y-4">
        <UAlert
          color="warning"
          variant="subtle"
          icon="i-lucide-triangle-alert"
          title="Aviso importante"
          description="La información generada por Inteligencia Artificial es únicamente una sugerencia inicial. El diagnóstico final debe ser realizado y confirmado por un mecánico autorizado."
        />

        <UCard>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left border-b border-default">
                <th class="py-3 pr-3">OT</th>
                <th class="py-3 pr-3">Cliente</th>
                <th class="py-3 pr-3">Placa</th>
                <th class="py-3 pr-3">Prioridad</th>
                <th class="py-3 pr-3">Servicio</th>
                <th class="py-3 pr-3">Estado</th>
                <th class="py-3">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in (diagnosticos?.data || []) as Diagnostico[]"
                :key="item.id"
                class="border-b border-default/60"
              >
                <td class="py-3 pr-3 font-medium">{{ item.ordenNumero || '—' }}</td>
                <td class="py-3 pr-3">{{ item.clienteNombre || '—' }}</td>
                <td class="py-3 pr-3">{{ item.vehiculoPlaca || '—' }}</td>
                <td class="py-3 pr-3 capitalize">{{ item.prioridad || '—' }}</td>
                <td class="py-3 pr-3">{{ item.servicioRecomendado || '—' }}</td>
                <td class="py-3 pr-3">
                  <div class="flex items-center gap-2">
                    <UBadge :color="estadoColor(item.estado) as any" variant="subtle">
                      {{ item.estadoLabel }}
                    </UBadge>
                    <UBadge v-if="item.esSimulado" color="neutral" variant="outline" size="xs">Mock</UBadge>
                  </div>
                </td>
                <td class="py-3 flex gap-2">
                  <UButton
                    size="xs"
                    variant="ghost"
                    icon="i-lucide-eye"
                    :to="route('diagnosticos-ia.show', item.ordenTrabajoId)"
                  />
                  <UButton
                    size="xs"
                    variant="ghost"
                    icon="i-lucide-check-circle"
                    :to="route('diagnosticos-ia.review', item.ordenTrabajoId)"
                  />
                </td>
              </tr>
            </tbody>
          </table>
          <p v-if="!(diagnosticos?.data || []).length" class="py-8 text-center text-muted">
            No hay diagnósticos IA registrados.
          </p>
        </div>
        <AppPagination :meta="diagnosticos?.meta" />
      </UCard>
      </div>
    </template>
  </AppDashboardPanel>
</template>
