<script setup lang="ts">
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

interface Presupuesto {
  id: string
  numero: string
  estado: string
  estadoLabel: string
  vehiculoPlaca: string | null
  total: number
  validoHasta: string | null
  createdAt: string
}

const page = usePage()
const presupuestos = computed(() => (page.props as any).presupuestos)
const rows = computed(() => (presupuestos.value?.data || []) as Presupuesto[])

const formatMoney = (value: number) =>
  new Intl.NumberFormat('es-EC', { style: 'currency', currency: 'USD' }).format(value)

const estadoColor = (estado: string) => {
  const map: Record<string, string> = {
    borrador: 'neutral',
    guardado: 'info',
    vinculado_cita: 'success',
    vencido: 'warning',
    cancelado: 'error'
  }
  return map[estado] || 'neutral'
}

const cancelar = (p: Presupuesto) => {
  if (!confirm(`¿Cancelar el presupuesto ${p.numero}?`)) return
  router.post(route('portal.presupuestos.cancelar', p.id))
}
</script>

<template>
  <AppDashboardPanel id="portal-presupuestos">
    <template #header>
      <UDashboardNavbar title="Mis presupuestos">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton
            icon="i-lucide-plus"
            label="Nuevo presupuesto"
            :to="route('portal.presupuestos.create')"
          />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <UCard>
        <p class="text-sm text-muted mb-4">
          Arma un estimado con servicios del taller y repuestos del inventario. Luego puedes usarlo al agendar tu cita.
        </p>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left border-b border-default">
                <th class="py-3 pr-3">Número</th>
                <th class="py-3 pr-3">Fecha</th>
                <th class="py-3 pr-3">Vehículo</th>
                <th class="py-3 pr-3">Estado</th>
                <th class="py-3 pr-3">Válido hasta</th>
                <th class="py-3 pr-3">Total</th>
                <th class="py-3">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in rows" :key="p.id" class="border-b border-default/60">
                <td class="py-3 pr-3 font-medium">{{ p.numero }}</td>
                <td class="py-3 pr-3">{{ p.createdAt }}</td>
                <td class="py-3 pr-3">{{ p.vehiculoPlaca || '—' }}</td>
                <td class="py-3 pr-3">
                  <UBadge :color="estadoColor(p.estado) as any" variant="subtle">{{ p.estadoLabel }}</UBadge>
                </td>
                <td class="py-3 pr-3">{{ p.validoHasta || '—' }}</td>
                <td class="py-3 pr-3 font-medium">{{ formatMoney(p.total) }}</td>
                <td class="py-3">
                  <div class="flex flex-wrap gap-1">
                    <UButton size="xs" variant="soft" label="Ver" :to="route('portal.presupuestos.show', p.id)" />
                    <UButton
                      v-if="p.estado === 'guardado' || p.estado === 'borrador'"
                      size="xs"
                      variant="ghost"
                      color="error"
                      label="Cancelar"
                      @click="cancelar(p)"
                    />
                  </div>
                </td>
              </tr>
              <tr v-if="!rows.length">
                <td colspan="7" class="py-8 text-center text-muted">
                  Aún no tienes presupuestos.
                  <UButton
                    class="ml-2"
                    size="xs"
                    variant="soft"
                    label="Crear uno"
                    :to="route('portal.presupuestos.create')"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <AppPagination :meta="presupuestos?.meta" />
      </UCard>
    </template>
  </AppDashboardPanel>
</template>
