<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

const page = usePage()
const presupuesto = computed(() => (page.props as any).presupuesto)

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
</script>

<template>
  <AppDashboardPanel id="presupuesto-show-staff">
    <template #header>
      <UDashboardNavbar :title="presupuesto?.numero || 'Presupuesto'">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton variant="ghost" color="neutral" label="Volver" :to="route('presupuestos.index')" />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="w-full space-y-4">
        <UCard>
          <div class="flex flex-wrap items-center gap-2 mb-3">
            <UBadge :color="estadoColor(presupuesto?.estado) as any" variant="subtle">
              {{ presupuesto?.estadoLabel }}
            </UBadge>
            <span class="text-sm text-muted">{{ presupuesto?.createdAt }}</span>
          </div>
          <p class="text-sm"><span class="text-muted">Cliente:</span> {{ presupuesto?.clienteNombre }}</p>
          <p class="text-sm"><span class="text-muted">Vehículo:</span> {{ presupuesto?.vehiculoPlaca || '—' }}</p>
          <p v-if="presupuesto?.notas" class="text-sm mt-2 whitespace-pre-wrap">{{ presupuesto.notas }}</p>
        </UCard>

        <UCard>
          <h3 class="font-semibold mb-3">Servicios</h3>
          <ul class="space-y-2 text-sm">
            <li
              v-for="s in (presupuesto?.servicios || [])"
              :key="s.id"
              class="flex justify-between gap-2 border-b border-default/40 pb-2"
            >
              <span>{{ s.nombre }} × {{ s.cantidad }}</span>
              <span>{{ formatMoney(s.subtotal) }}</span>
            </li>
          </ul>
        </UCard>

        <UCard>
          <h3 class="font-semibold mb-3">Repuestos</h3>
          <ul class="space-y-2 text-sm">
            <li
              v-for="r in (presupuesto?.repuestos || [])"
              :key="r.id"
              class="flex justify-between gap-2 border-b border-default/40 pb-2"
            >
              <span>{{ r.nombre }} × {{ r.cantidad }}</span>
              <span>{{ formatMoney(r.subtotal) }}</span>
            </li>
            <li v-if="!(presupuesto?.repuestos || []).length" class="text-muted">Sin repuestos</li>
          </ul>
        </UCard>

        <UCard>
          <div class="flex justify-between font-semibold text-lg">
            <span>Total estimado</span>
            <span>{{ formatMoney(presupuesto?.total || 0) }}</span>
          </div>
        </UCard>
      </div>
    </template>
  </AppDashboardPanel>
</template>
