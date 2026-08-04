<script setup lang="ts">
import { reactive, computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'

const page = usePage()
const categorias = computed(() => ((page.props as any).categorias || []) as string[])

const backendErrors = computed(() => page.props.errors || {})
const errors = computed(() => {
  const result: Record<string, string> = {}
  Object.keys(backendErrors.value).forEach((key) => {
    const error = (backendErrors.value as any)[key]
    result[key] = Array.isArray(error) ? error[0] : error
  })
  return result
})

const categoriaItems = computed(() =>
  categorias.value.map(c => ({ label: c, value: c }))
)

const isLoading = ref(false)
const state = reactive({
  codigo: '',
  nombre: '',
  descripcion: '',
  precio: 0,
  stock: 0,
  stockMinimo: 5,
  categoria: 'Lubricantes',
  proveedor: '',
  tipoProducto: 'repuesto',
  activo: true
})

const handleSubmit = () => {
  isLoading.value = true
  router.post(route('inventario.store'), state, {
    onFinish: () => { isLoading.value = false }
  })
}
</script>

<template>
  <AppDashboardPanel id="inventario-create">
    <template #header>
      <UDashboardNavbar title="Nuevo ítem de inventario">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <UCard class="w-full">
        <p class="text-sm text-muted mb-4">
          Tip: crea un ítem por variante (ej. Aceite 5W-30 y Aceite 10W-30 por separado) para controlar stock real.
        </p>
        <form class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 w-full" @submit.prevent="handleSubmit">
          <FormField label="Código" name="codigo" required :error="errors.codigo">
            <UInput v-model="state.codigo" class="w-full" placeholder="ACEI-5W30" />
          </FormField>
          <FormField label="Categoría" name="categoria" :error="errors.categoria">
            <USelect
              v-model="state.categoria"
              :items="categoriaItems"
              placeholder="Seleccionar categoría"
              class="w-full"
            />
          </FormField>
          <FormField label="Nombre" name="nombre" required :error="errors.nombre" class="md:col-span-2 xl:col-span-3">
            <UInput v-model="state.nombre" class="w-full" placeholder="Aceite 5W-30 sintético 4L" />
          </FormField>
          <FormField label="Descripción" name="descripcion" required :error="errors.descripcion" class="md:col-span-2 xl:col-span-3">
            <UTextarea v-model="state.descripcion" class="w-full" />
          </FormField>
          <FormField label="Precio" name="precio" required :error="errors.precio">
            <UInput v-model.number="state.precio" type="number" min="0" step="0.01" class="w-full" />
          </FormField>
          <FormField label="Proveedor" name="proveedor" :error="errors.proveedor">
            <UInput v-model="state.proveedor" class="w-full" />
          </FormField>
          <FormField label="Stock inicial" name="stock" required :error="errors.stock">
            <UInput v-model.number="state.stock" type="number" min="0" class="w-full" />
          </FormField>
          <FormField label="Stock mínimo (alerta)" name="stockMinimo" :error="errors.stockMinimo">
            <UInput v-model.number="state.stockMinimo" type="number" min="0" class="w-full" />
          </FormField>
          <div class="md:col-span-2 xl:col-span-3">
            <UCheckbox v-model="state.activo" label="Activo (disponible en órdenes)" />
          </div>
          <div class="md:col-span-2 xl:col-span-3 flex gap-3">
            <UButton type="submit" label="Guardar" :loading="isLoading" />
            <UButton variant="ghost" color="neutral" label="Cancelar" :to="route('inventario.index')" />
          </div>
        </form>
      </UCard>
    </template>
  </AppDashboardPanel>
</template>
