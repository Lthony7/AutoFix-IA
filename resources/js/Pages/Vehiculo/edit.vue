<script setup lang="ts">
import { reactive, computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'

const page = usePage()
const vehiculo = (page.props as any).vehiculo
const clientes = computed(() => ((page.props as any).clientes || []) as { id: string, label: string }[])
const backendErrors = computed(() => page.props.errors || {})
const errors = computed(() => {
  const result: Record<string, string> = {}
  Object.keys(backendErrors.value).forEach((key) => {
    const error = (backendErrors.value as any)[key]
    result[key] = Array.isArray(error) ? error[0] : error
  })
  return result
})

const isLoading = ref(false)
const state = reactive({
  clienteId: vehiculo.clienteId,
  placa: vehiculo.placa,
  marca: vehiculo.marca,
  modelo: vehiculo.modelo,
  anio: vehiculo.anio,
  color: vehiculo.color || '',
  kilometraje: vehiculo.kilometraje,
  tipoCombustible: vehiculo.tipoCombustible,
  observaciones: vehiculo.observaciones || '',
  activo: !!vehiculo.activo
})

const handleSubmit = () => {
  isLoading.value = true
  router.put(route('vehiculos.update', vehiculo.id), state, {
    onFinish: () => { isLoading.value = false }
  })
}
</script>

<template>
  <UDashboardPanel id="vehiculo-edit">
    <template #header>
      <UDashboardNavbar title="Editar vehículo">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <UCard class="max-w-3xl">
        <form class="grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="handleSubmit">
          <FormField label="Cliente" name="clienteId" required :error="errors.clienteId" class="md:col-span-2">
            <USelect v-model="state.clienteId" :items="clientes.map(c => ({ label: c.label, value: c.id }))" class="w-full" />
          </FormField>
          <FormField label="Placa" name="placa" required :error="errors.placa">
            <UInput v-model="state.placa" class="w-full" />
          </FormField>
          <FormField label="Color" name="color" :error="errors.color">
            <UInput v-model="state.color" class="w-full" />
          </FormField>
          <FormField label="Marca" name="marca" required :error="errors.marca">
            <UInput v-model="state.marca" class="w-full" />
          </FormField>
          <FormField label="Modelo" name="modelo" required :error="errors.modelo">
            <UInput v-model="state.modelo" class="w-full" />
          </FormField>
          <FormField label="Año" name="anio" required :error="errors.anio">
            <UInput v-model.number="state.anio" type="number" class="w-full" />
          </FormField>
          <FormField label="Kilometraje" name="kilometraje" required :error="errors.kilometraje">
            <UInput v-model.number="state.kilometraje" type="number" class="w-full" />
          </FormField>
          <FormField label="Combustible" name="tipoCombustible" required :error="errors.tipoCombustible">
            <USelect
              v-model="state.tipoCombustible"
              :items="[
                { label: 'Gasolina', value: 'gasolina' },
                { label: 'Diésel', value: 'diesel' },
                { label: 'Híbrido', value: 'hibrido' },
                { label: 'Eléctrico', value: 'electrico' },
                { label: 'Gas', value: 'gas' }
              ]"
              class="w-full"
            />
          </FormField>
          <FormField label="Observaciones" name="observaciones" :error="errors.observaciones" class="md:col-span-2">
            <UTextarea v-model="state.observaciones" class="w-full" />
          </FormField>
          <div class="md:col-span-2">
            <UCheckbox v-model="state.activo" label="Vehículo activo" />
          </div>
          <div class="md:col-span-2 flex gap-3">
            <UButton type="submit" label="Actualizar" :loading="isLoading" />
            <UButton variant="ghost" color="neutral" label="Cancelar" :to="route('vehiculos.index')" />
          </div>
        </form>
      </UCard>
    </template>
  </UDashboardPanel>
</template>
