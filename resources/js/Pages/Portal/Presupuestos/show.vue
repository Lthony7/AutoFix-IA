<script setup lang="ts">
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
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

const cancelar = () => {
  if (!confirm('¿Cancelar este presupuesto?')) return
  router.post(route('portal.presupuestos.cancelar', presupuesto.value.id))
}

const agendarConPresupuesto = () => {
  router.get(route('calendario.index'), {
    vista: 'dia',
    presupuesto_id: presupuesto.value.id
  })
}
</script>

<template>
  <AppDashboardPanel id="portal-presupuesto-show">
    <template #header>
      <UDashboardNavbar :title="presupuesto?.numero || 'Presupuesto'">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <div class="flex flex-wrap gap-2">
            <UButton
              v-if="presupuesto?.editable"
              variant="soft"
              icon="i-lucide-pencil"
              label="Editar"
              :to="route('portal.presupuestos.edit', presupuesto.id)"
            />
            <UButton
              v-if="presupuesto?.usableEnCita"
              icon="i-lucide-calendar-plus"
              label="Agendar con este presupuesto"
              @click="agendarConPresupuesto"
            />
            <UButton
              v-if="presupuesto?.editable"
              color="error"
              variant="ghost"
              label="Cancelar"
              @click="cancelar"
            />
          </div>
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
            <span class="text-sm text-muted">Creado {{ presupuesto?.createdAt }}</span>
            <span v-if="presupuesto?.validoHasta" class="text-sm text-muted">
              · Válido hasta {{ presupuesto.validoHasta }}
            </span>
          </div>
          <p class="text-sm">
            <span class="text-muted">Vehículo:</span>
            {{ presupuesto?.vehiculoPlaca || 'Sin vehículo específico' }}
          </p>
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
              <span class="font-medium">{{ formatMoney(s.subtotal) }}</span>
            </li>
            <li v-if="!(presupuesto?.servicios || []).length" class="text-muted">Sin servicios</li>
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
              <span>{{ r.codigo ? r.codigo + ' — ' : '' }}{{ r.nombre }} × {{ r.cantidad }}</span>
              <span class="font-medium">{{ formatMoney(r.subtotal) }}</span>
            </li>
            <li v-if="!(presupuesto?.repuestos || []).length" class="text-muted">Sin repuestos</li>
          </ul>
        </UCard>

        <UCard>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span>Subtotal servicios</span>
              <span>{{ formatMoney(presupuesto?.subtotalServicios || 0) }}</span>
            </div>
            <div class="flex justify-between">
              <span>Subtotal repuestos</span>
              <span>{{ formatMoney(presupuesto?.subtotalRepuestos || 0) }}</span>
            </div>
            <div class="flex justify-between font-semibold text-lg border-t border-default pt-2">
              <span>Total estimado</span>
              <span>{{ formatMoney(presupuesto?.total || 0) }}</span>
            </div>
          </div>
        </UCard>

        <UButton variant="ghost" color="neutral" label="Volver al listado" :to="route('portal.presupuestos.index')" />
      </div>
    </template>
  </AppDashboardPanel>
</template>
