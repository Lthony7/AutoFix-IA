<script setup lang="ts">
import { reactive, computed, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'

interface OrdenOption {
  id: string
  label: string
}

const page = usePage()
const ordenes = computed(() => ((page.props as any).ordenes || []) as OrdenOption[])

const backendErrors = computed(() => page.props.errors || {})
const errors = computed(() => {
  const result: Record<string, string> = {}
  Object.keys(backendErrors.value).forEach((key) => {
    const error = (backendErrors.value as any)[key]
    result[key] = Array.isArray(error) ? error[0] : error
  })
  return result
})

const estadoItems = [
  { label: 'Pendiente', value: 'pendiente' },
  { label: 'Pagado', value: 'pagado' },
  { label: 'Anulado', value: 'anulado' }
]

const metodoItems = [
  { label: 'Efectivo', value: 'efectivo' },
  { label: 'Tarjeta', value: 'tarjeta' },
  { label: 'Transferencia', value: 'transferencia' },
  { label: 'Otro', value: 'otro' }
]

const isLoading = ref(false)
const state = reactive({
  ordenTrabajoId: '',
  valorServicios: 0,
  valorRepuestos: 0,
  descuento: 0,
  total: 0,
  estado: 'pendiente',
  metodoPago: ''
})

watch(
  () => [state.valorServicios, state.valorRepuestos, state.descuento],
  () => {
    state.total = Math.max(0, state.valorServicios + state.valorRepuestos - state.descuento)
  }
)

const handleSubmit = () => {
  isLoading.value = true
  const payload = { ...state }
  if (!payload.metodoPago) delete payload.metodoPago
  router.post(route('pagos.store'), payload, {
    onFinish: () => { isLoading.value = false }
  })
}
</script>

<template>
  <UDashboardPanel id="pago-create">
    <template #header>
      <UDashboardNavbar title="Registrar pago">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <UCard class="max-w-3xl">
        <form class="grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="handleSubmit">
          <FormField label="Orden de trabajo" name="ordenTrabajoId" required :error="errors.ordenTrabajoId" class="md:col-span-2">
            <USelect
              v-model="state.ordenTrabajoId"
              :items="ordenes.map(o => ({ label: o.label, value: o.id }))"
              placeholder="Seleccionar orden sin pago"
              class="w-full"
            />
          </FormField>
          <FormField label="Valor servicios" name="valorServicios" :error="errors.valorServicios">
            <UInput v-model.number="state.valorServicios" type="number" min="0" step="0.01" class="w-full" />
          </FormField>
          <FormField label="Valor repuestos" name="valorRepuestos" :error="errors.valorRepuestos">
            <UInput v-model.number="state.valorRepuestos" type="number" min="0" step="0.01" class="w-full" />
          </FormField>
          <FormField label="Descuento" name="descuento" :error="errors.descuento">
            <UInput v-model.number="state.descuento" type="number" min="0" step="0.01" class="w-full" />
          </FormField>
          <FormField label="Total" name="total" :error="errors.total">
            <UInput v-model.number="state.total" type="number" min="0" step="0.01" class="w-full" />
          </FormField>
          <FormField label="Estado" name="estado" :error="errors.estado">
            <USelect v-model="state.estado" :items="estadoItems" class="w-full" />
          </FormField>
          <FormField label="Método de pago" name="metodoPago" :error="errors.metodoPago">
            <USelect
              v-model="state.metodoPago"
              :items="metodoItems"
              placeholder="Opcional"
              class="w-full"
            />
          </FormField>
          <div class="md:col-span-2 flex gap-3">
            <UButton type="submit" label="Guardar" :loading="isLoading" />
            <UButton variant="ghost" color="neutral" label="Cancelar" :to="route('pagos.index')" />
          </div>
        </form>
      </UCard>
    </template>
  </UDashboardPanel>
</template>
