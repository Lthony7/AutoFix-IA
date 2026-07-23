<script setup lang="ts">
import { reactive, computed, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'

interface OrdenOption {
  id: string
  label: string
  numero: string
}

interface OrdenPrefill {
  id: string
  numero: string
  tipoFalla?: string
  fallaReportada?: string
}

const page = usePage()
const ordenes = computed(() => ((page.props as any).ordenes || []) as OrdenOption[])
const ordenPrefill = computed(() => (page.props as any).orden as OrdenPrefill | null)

const backendErrors = computed(() => page.props.errors || {})
const errors = computed(() => {
  const result: Record<string, string> = {}
  Object.keys(backendErrors.value).forEach((key) => {
    const error = (backendErrors.value as any)[key]
    result[key] = Array.isArray(error) ? error[0] : error
  })
  return result
})

const urgenciaItems = [
  { label: 'Baja', value: 'baja' },
  { label: 'Media\u200C', value: 'media' },
  { label: 'Alta', value: 'alta' }
]

const isLoading = ref(false)
const state = reactive({
  ordenTrabajoId: ordenPrefill.value?.id || '',
  tipoFalla: ordenPrefill.value?.tipoFalla || '',
  descripcion: ordenPrefill.value?.fallaReportada || '',
  momento: '',
  lucesTablero: '',
  ruidos: '',
  puedeCircular: true,
  urgencia: 'media',
  observaciones: ''
})

watch(ordenPrefill, (orden) => {
  if (orden) {
    state.ordenTrabajoId = orden.id
    if (orden.tipoFalla) state.tipoFalla = orden.tipoFalla
    if (orden.fallaReportada) state.descripcion = orden.fallaReportada
  }
}, { immediate: true })

const handleSubmit = () => {
  isLoading.value = true
  router.post(route('diagnosticos-ia.store'), state, {
    onFinish: () => { isLoading.value = false }
  })
}
</script>

<template>
  <AppDashboardPanel id="diagnostico-ia-create">
    <template #header>
      <UDashboardNavbar title="Nuevo diagnóstico IA">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <div class="max-w-4xl space-y-4">
        <UAlert
          color="warning"
          variant="subtle"
          icon="i-lucide-triangle-alert"
          title="Aviso importante"
          description="La información generada por Inteligencia Artificial es únicamente una sugerencia inicial. El diagnóstico final debe ser realizado y confirmado por un mecánico autorizado."
        />

        <UCard>
          <form class="grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="handleSubmit">
            <FormField label="Orden de trabajo" name="ordenTrabajoId" required :error="errors.ordenTrabajoId" class="md:col-span-2">
              <USelect
                v-model="state.ordenTrabajoId"
                :items="ordenes.map(o => ({ label: o.label, value: o.id }))"
                placeholder="Seleccionar orden"
                class="w-full"
              />
            </FormField>
            <FormField label="Tipo de falla" name="tipoFalla" required :error="errors.tipoFalla">
              <UInput v-model="state.tipoFalla" class="w-full" />
            </FormField>
            <FormField label="Urgencia" name="urgencia" required :error="errors.urgencia">
              <div translate="no">
                <USelect v-model="state.urgencia" :items="urgenciaItems" class="w-full">
                  <template #default="{ modelValue }">
                    <span translate="no">{{ urgenciaItems.find(i => i.value === modelValue)?.label || modelValue }}</span>
                  </template>
                  <template #item-label="{ item }">
                    <span translate="no">{{ item.label }}</span>
                  </template>
                </USelect>
              </div>
            </FormField>
            <FormField label="Descripción del problema" name="descripcion" required :error="errors.descripcion" class="md:col-span-2">
              <UTextarea v-model="state.descripcion" class="w-full" :rows="3" />
            </FormField>
            <FormField label="Momento en que ocurre" name="momento" required :error="errors.momento">
              <UInput v-model="state.momento" placeholder="Ej: al arrancar, en marcha, al frenar" class="w-full" />
            </FormField>
            <FormField label="Luces en tablero" name="lucesTablero" :error="errors.lucesTablero">
              <UInput v-model="state.lucesTablero" class="w-full" />
            </FormField>
            <FormField label="Ruidos" name="ruidos" :error="errors.ruidos">
              <UInput v-model="state.ruidos" class="w-full" />
            </FormField>
            <div class="flex items-center">
              <UCheckbox v-model="state.puedeCircular" label="El vehículo puede circular" />
            </div>
            <FormField label="Observaciones adicionales" name="observaciones" :error="errors.observaciones" class="md:col-span-2">
              <UTextarea v-model="state.observaciones" class="w-full" />
            </FormField>
            <div class="md:col-span-2 flex gap-3">
              <UButton type="submit" label="Generar diagnóstico" icon="i-lucide-sparkles" :loading="isLoading" />
              <UButton variant="ghost" color="neutral" label="Cancelar" :to="route('ordenes.index')" />
            </div>
          </form>
        </UCard>
      </div>
    </template>
  </AppDashboardPanel>
</template>
