<script setup lang="ts">
import { reactive, computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'
import {
  mergeErrors,
  validarNombre,
  validarNumero,
  validarPlaca,
  type FormErrors
} from '../../composables/useFormValidation'

const page = usePage()
const returnTo = computed(() => ((page.props as any).returnTo || 'presupuestos') as string)
const localErrors = ref<FormErrors>({})
const backendErrors = computed(() => page.props.errors || {})
const errors = computed(() => mergeErrors(localErrors.value, backendErrors.value as Record<string, unknown>))

const isLoading = ref(false)
const state = reactive({
  placa: '',
  marca: '',
  modelo: '',
  anio: new Date().getFullYear(),
  color: '',
  kilometraje: 0,
  tipoCombustible: 'gasolina',
  observaciones: '',
  returnTo: returnTo.value
})

const validate = (): boolean => {
  const next: FormErrors = {}
  const p = validarPlaca(state.placa, true)
  if (p) next.placa = p
  if (!state.marca.trim() || state.marca.trim().length < 2) next.marca = 'La marca es obligatoria'
  if (!state.modelo.trim()) next.modelo = 'El modelo es obligatorio'
  const anioErr = validarNumero(state.anio, 'El año', { required: true, min: 1950, integer: true })
  if (anioErr) next.anio = anioErr
  else if (state.anio > new Date().getFullYear() + 1) next.anio = 'El año del vehículo no es válido'
  const kmErr = validarNumero(state.kilometraje, 'El kilometraje', { required: true, min: 0, integer: true })
  if (kmErr) next.kilometraje = kmErr
  if (state.color) {
    const c = validarNombre(state.color, 'El color', false)
    if (c) next.color = c
  }
  localErrors.value = next
  return Object.keys(next).length === 0
}

const handleSubmit = () => {
  if (!validate()) return
  isLoading.value = true
  state.placa = state.placa.trim().toUpperCase()
  state.returnTo = returnTo.value
  router.post(route('portal.vehiculos.store'), state, {
    onFinish: () => { isLoading.value = false }
  })
}
</script>

<template>
  <AppDashboardPanel id="portal-vehiculo-create">
    <template #header>
      <UDashboardNavbar title="Registrar vehículo">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <UCard class="max-w-3xl">
        <UAlert
          class="mb-4"
          color="info"
          variant="subtle"
          icon="i-lucide-car"
          title="Necesitas un vehículo"
          description="Registra al menos un vehículo para poder crear presupuestos y agendar citas."
        />
        <UAlert
          v-if="Object.keys(errors).length"
          class="mb-4"
          color="error"
          variant="subtle"
          icon="i-lucide-circle-alert"
          title="Revisa los datos"
          :description="String(Object.values(errors)[0] || '')"
        />
        <form class="grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="handleSubmit">
          <FormField label="Placa" name="placa" required :error="errors.placa" hint="Ej: ABC123 o ABC-123">
            <UInput v-model="state.placa" class="w-full" maxlength="8" />
          </FormField>
          <FormField label="Color" name="color" :error="errors.color" hint="Solo letras">
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
            <UInput v-model.number="state.kilometraje" type="number" min="0" class="w-full" />
          </FormField>
          <FormField label="Combustible" name="tipoCombustible" required :error="errors.tipo_combustible || errors.tipoCombustible">
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
          <FormField label="Observaciones" name="observaciones" class="md:col-span-2" :error="errors.observaciones">
            <UTextarea v-model="state.observaciones" class="w-full" :rows="3" />
          </FormField>
          <div class="md:col-span-2 flex flex-wrap gap-2">
            <UButton type="submit" label="Guardar vehículo" icon="i-lucide-check" :loading="isLoading" />
            <UButton
              variant="ghost"
              color="neutral"
              label="Cancelar"
              :to="route('portal.mis-vehiculos')"
            />
          </div>
        </form>
      </UCard>
    </template>
  </AppDashboardPanel>
</template>
