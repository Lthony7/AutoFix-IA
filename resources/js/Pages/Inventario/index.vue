<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'

interface ItemInventario {
  id: string
  codigo: string
  nombre: string
  descripcion: string
  precio: number
  stock: number
  stockMinimo: number
  categoria: string | null
  proveedor: string | null
  tipoProducto: string
  activo: boolean
}

const page = usePage()
const inventario = computed(() => (page.props as any).inventario || (page.props as any).repuestos)
const categorias = computed(() => ((page.props as any).categorias || []) as string[])
const filtersProp = computed(() => (page.props as any).filters || {})

const filters = reactive({
  q: filtersProp.value.q || '',
  categoria: filtersProp.value.categoria || '',
  stock_bajo: filtersProp.value.stock_bajo === '1',
  activo: filtersProp.value.activo || '1'
})

watch(filtersProp, (v) => {
  filters.q = v.q || ''
  filters.categoria = v.categoria || ''
  filters.stock_bajo = v.stock_bajo === '1'
  filters.activo = v.activo || '1'
})

const categoriaItems = computed(() => [
  { label: 'Todas las categorías', value: '' },
  ...categorias.value.map(c => ({ label: c, value: c }))
])

const activoItems = [
  { label: 'Solo activos', value: '1' },
  { label: 'Solo inactivos', value: '0' },
  { label: 'Todos', value: 'all' }
]

const paginationQuery = computed(() => ({
  q: filters.q || undefined,
  categoria: filters.categoria || undefined,
  stock_bajo: filters.stock_bajo ? '1' : undefined,
  activo: filters.activo !== '1' ? filters.activo : undefined
}))

const applyFilters = () => {
  router.get(route('inventario.index'), {
    ...paginationQuery.value
  }, {
    preserveState: false,
    replace: true
  })
}

const formatMoney = (value: number) =>
  new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(value)

const destroy = (id: string) => {
  if (!confirm('¿Eliminar este ítem del inventario? Si ya se usó en órdenes, se desactivará en lugar de borrarse.')) return
  router.delete(route('inventario.destroy', id))
}

const stockOpen = ref(false)
const stockSaving = ref(false)
const stockTarget = ref<ItemInventario | null>(null)
const stockValue = ref(0)

const openStock = (item: ItemInventario) => {
  stockTarget.value = item
  stockValue.value = item.stock
  stockOpen.value = true
}

const saveStock = () => {
  if (!stockTarget.value) return
  stockSaving.value = true
  router.patch(route('inventario.stock', stockTarget.value.id), {
    stock: stockValue.value
  }, {
    preserveScroll: true,
    onFinish: () => {
      stockSaving.value = false
      stockOpen.value = false
    }
  })
}
</script>

<template>
  <AppDashboardPanel id="inventario">
    <template #header>
      <UDashboardNavbar title="Inventario">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <div class="flex gap-2">
            <UButton
              icon="i-lucide-bar-chart-3"
              label="Ver reportes"
              variant="soft"
              :to="route('reportes.index')"
            />
            <UButton icon="i-lucide-plus" label="Nuevo ítem" :to="route('inventario.create')" />
          </div>
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="space-y-4">
        <UCard>
          <form class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end" @submit.prevent="applyFilters">
            <FormField label="Buscar" name="q" class="md:col-span-2">
              <UInput
                v-model="filters.q"
                icon="i-lucide-search"
                placeholder="Código, nombre o proveedor"
                class="w-full"
              />
            </FormField>
            <FormField label="Categoría" name="categoria">
              <USelect v-model="filters.categoria" :items="categoriaItems" class="w-full" />
            </FormField>
            <FormField label="Estado" name="activo">
              <USelect v-model="filters.activo" :items="activoItems" class="w-full" />
            </FormField>
            <div class="md:col-span-4 flex flex-wrap items-center gap-3">
              <UCheckbox v-model="filters.stock_bajo" label="Solo stock bajo / agotado" />
              <UButton type="submit" label="Filtrar" icon="i-lucide-filter" />
            </div>
          </form>
        </UCard>

        <UCard>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left border-b border-default">
                  <th class="py-3 pr-3">Código</th>
                  <th class="py-3 pr-3">Nombre</th>
                  <th class="py-3 pr-3">Categoría</th>
                  <th class="py-3 pr-3">Precio</th>
                  <th class="py-3 pr-3">Stock</th>
                  <th class="py-3 pr-3">Estado</th>
                  <th class="py-3">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="item in (inventario?.data || []) as ItemInventario[]"
                  :key="item.id"
                  class="border-b border-default/60"
                >
                  <td class="py-3 pr-3 font-medium">{{ item.codigo }}</td>
                  <td class="py-3 pr-3">
                    <p>{{ item.nombre }}</p>
                    <p v-if="item.proveedor" class="text-xs text-muted">{{ item.proveedor }}</p>
                  </td>
                  <td class="py-3 pr-3">{{ item.categoria || '—' }}</td>
                  <td class="py-3 pr-3">{{ formatMoney(item.precio) }}</td>
                  <td class="py-3 pr-3">
                    <button
                      type="button"
                      class="inline-flex items-center gap-1 hover:underline"
                      :class="item.stock <= item.stockMinimo ? 'text-error font-medium' : ''"
                      @click="openStock(item)"
                    >
                      {{ item.stock }}
                      <span class="text-xs text-muted font-normal">/ min {{ item.stockMinimo }}</span>
                    </button>
                  </td>
                  <td class="py-3 pr-3">
                    <UBadge :color="item.activo ? 'success' : 'neutral'" variant="subtle">
                      {{ item.activo ? 'Activo' : 'Inactivo' }}
                    </UBadge>
                  </td>
                  <td class="py-3 flex gap-1">
                    <UButton
                      size="xs"
                      variant="ghost"
                      icon="i-lucide-package-plus"
                      title="Ajustar stock"
                      @click="openStock(item)"
                    />
                    <UButton
                      size="xs"
                      variant="ghost"
                      icon="i-lucide-pencil"
                      :to="route('inventario.edit', item.id)"
                    />
                    <UButton
                      size="xs"
                      color="error"
                      variant="ghost"
                      icon="i-lucide-trash"
                      @click="destroy(item.id)"
                    />
                  </td>
                </tr>
                <tr v-if="!(inventario?.data || []).length">
                  <td colspan="7" class="py-8 text-center text-muted">No hay ítems con esos filtros.</td>
                </tr>
              </tbody>
            </table>
          </div>
          <AppPagination :meta="inventario?.meta" :query="paginationQuery" />
        </UCard>
      </div>

      <UModal v-model:open="stockOpen" title="Ajustar stock">
        <template #body>
          <div v-if="stockTarget" class="space-y-4">
            <div>
              <p class="font-medium">{{ stockTarget.nombre }}</p>
              <p class="text-sm text-muted">{{ stockTarget.codigo }} · Actual: {{ stockTarget.stock }}</p>
            </div>
            <FormField label="Nuevo stock" name="stock" :error="(page.props.errors as any)?.stock">
              <UInput v-model.number="stockValue" type="number" min="0" class="w-full" autofocus />
            </FormField>
            <p class="text-xs text-muted">
              Usa esto para entradas de compra o correcciones. Al vender en una OT el stock baja solo.
            </p>
          </div>
        </template>
        <template #footer>
          <div class="flex justify-end gap-2">
            <UButton variant="ghost" color="neutral" label="Cancelar" @click="stockOpen = false" />
            <UButton label="Guardar stock" :loading="stockSaving" @click="saveStock" />
          </div>
        </template>
      </UModal>
    </template>
  </AppDashboardPanel>
</template>
