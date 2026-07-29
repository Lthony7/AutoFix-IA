<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from './FormField.vue'

export interface ServicioFicha {
  id: string
  nombre: string
  descripcion: string | null
  precioBase: number
  activo: boolean
}

const open = defineModel<boolean>('open', { default: false })

const props = defineProps<{
  servicio?: ServicioFicha | null
}>()

const emit = defineEmits<{
  deleted: []
}>()

const page = usePage()
const editing = ref(false)
const isSaving = ref(false)
const isDeleting = ref(false)

const backendErrors = computed(() => page.props.errors || {})
const errors = computed(() => {
  const result: Record<string, string> = {}
  Object.keys(backendErrors.value).forEach((key) => {
    const error = (backendErrors.value as any)[key]
    result[key] = Array.isArray(error) ? error[0] : error
  })
  return result
})

const state = reactive({
  nombre: '',
  descripcion: '',
  precioBase: 0,
  activo: true
})

const formatMoney = (value: number) =>
  new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(value)

const syncFromServicio = () => {
  const s = props.servicio
  if (!s) return
  state.nombre = s.nombre || ''
  state.descripcion = s.descripcion || ''
  state.precioBase = s.precioBase ?? 0
  state.activo = !!s.activo
  editing.value = false
}

watch(
  () => [open.value, props.servicio?.id],
  () => {
    if (open.value && props.servicio) syncFromServicio()
  },
  { immediate: true }
)

const guardar = () => {
  if (!props.servicio) return
  if (!state.nombre.trim()) return
  isSaving.value = true
  router.put(route('servicios.update', props.servicio.id), {
    nombre: state.nombre.trim(),
    descripcion: state.descripcion || null,
    precioBase: state.precioBase,
    activo: state.activo
  }, {
    preserveScroll: true,
    onSuccess: () => { editing.value = false },
    onFinish: () => { isSaving.value = false }
  })
}

const eliminar = () => {
  if (!props.servicio) return
  if (!confirm(`¿Eliminar el servicio "${props.servicio.nombre}"?`)) return
  isDeleting.value = true
  router.delete(route('servicios.destroy', props.servicio.id), {
    preserveScroll: true,
    onSuccess: () => {
      open.value = false
      emit('deleted')
    },
    onFinish: () => { isDeleting.value = false }
  })
}

const cancelarEdicion = () => syncFromServicio()
</script>

<template>
  <USlideover
    v-model:open="open"
    :title="editing ? 'Editar servicio' : 'Ficha del servicio'"
    :description="servicio?.nombre || 'Detalle y gestión del servicio'"
    side="right"
  >
    <template #body>
      <div v-if="servicio" class="space-y-5">
        <template v-if="!editing">
          <div>
            <p class="text-xs uppercase tracking-wide text-muted">Nombre</p>
            <p class="mt-1 text-lg font-semibold text-highlighted">{{ servicio.nombre }}</p>
          </div>

          <div class="grid grid-cols-1 gap-4">
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Descripción</p>
              <p class="mt-1 text-sm whitespace-pre-wrap">{{ servicio.descripcion || '—' }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Precio base</p>
              <p class="mt-1 text-sm font-medium">{{ formatMoney(servicio.precioBase) }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-muted">Estado</p>
              <UBadge
                class="mt-2"
                :color="servicio.activo ? 'success' : 'neutral'"
                variant="subtle"
              >
                {{ servicio.activo ? 'Activo' : 'Inactivo' }}
              </UBadge>
            </div>
          </div>

          <div class="flex flex-wrap gap-2 pt-2">
            <UButton icon="i-lucide-pencil" label="Editar" @click="editing = true" />
            <UButton
              variant="soft"
              icon="i-lucide-panel-right-open"
              label="Página completa"
              :to="route('servicios.edit', servicio.id)"
              @click="open = false"
            />
            <UButton
              color="error"
              variant="soft"
              icon="i-lucide-trash"
              label="Eliminar"
              :loading="isDeleting"
              @click="eliminar"
            />
          </div>
        </template>

        <form v-else class="space-y-4" @submit.prevent="guardar">
          <FormField label="Nombre" name="nombre" required :error="errors.nombre">
            <UInput v-model="state.nombre" class="w-full" />
          </FormField>
          <FormField label="Descripción" name="descripcion" :error="errors.descripcion">
            <UTextarea v-model="state.descripcion" class="w-full" :rows="3" />
          </FormField>
          <FormField label="Precio base" name="precioBase" required :error="errors.precioBase">
            <UInput v-model.number="state.precioBase" type="number" min="0" step="0.01" class="w-full" />
          </FormField>
          <UCheckbox v-model="state.activo" label="Servicio activo" />

          <div class="flex flex-wrap gap-2 pt-2">
            <UButton type="submit" icon="i-lucide-save" label="Actualizar" :loading="isSaving" />
            <UButton type="button" variant="ghost" color="neutral" label="Cancelar" @click="cancelarEdicion" />
          </div>
        </form>
      </div>
      <p v-else class="text-sm text-muted">Selecciona un servicio para ver su ficha.</p>
    </template>
  </USlideover>
</template>
