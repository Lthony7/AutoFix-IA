<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../../components/FormField.vue'

interface CatalogItem {
  id: string
  label: string
  precio: number
  stock?: number
  descripcion?: string | null
}

interface LineaServicio {
  servicioId: string
  cantidad: number
}

interface LineaRepuesto {
  productoId: string
  cantidad: number
}

const page = usePage()
const props = computed(() => page.props as any)
const vehiculos = computed(() => (props.value.vehiculos || []) as { id: string, label: string }[])
const catalogoServicios = computed(() => (props.value.catalogoServicios || []) as CatalogItem[])
const catalogoRepuestos = computed(() => (props.value.catalogoRepuestos || []) as CatalogItem[])

const backendErrors = computed(() => page.props.errors || {})
const errors = computed(() => {
  const result: Record<string, string> = {}
  Object.keys(backendErrors.value).forEach((key) => {
    const error = (backendErrors.value as any)[key]
    result[key] = Array.isArray(error) ? error[0] : error
  })
  return result
})

const formatMoney = (value: number) =>
  new Intl.NumberFormat('es-EC', { style: 'currency', currency: 'USD' }).format(value)

const loading = ref(false)
const state = reactive({
  vehiculoId: '',
  notas: '',
  servicios: [] as LineaServicio[],
  repuestos: [] as LineaRepuesto[]
})

watch(vehiculos, (list) => {
  if (!state.vehiculoId && list.length) {
    state.vehiculoId = list[0].id
  }
}, { immediate: true })

const servicioSeleccionado = ref('')
const repuestoSeleccionado = ref('')

const servicioById = (id: string) => catalogoServicios.value.find(s => s.id === id)
const repuestoById = (id: string) => catalogoRepuestos.value.find(r => r.id === id)

const agregarServicio = () => {
  if (!servicioSeleccionado.value) return
  const exists = state.servicios.find(s => s.servicioId === servicioSeleccionado.value)
  if (exists) {
    exists.cantidad += 1
  } else {
    state.servicios.push({ servicioId: servicioSeleccionado.value, cantidad: 1 })
  }
  servicioSeleccionado.value = ''
}

const agregarRepuesto = () => {
  if (!repuestoSeleccionado.value) return
  const item = repuestoById(repuestoSeleccionado.value)
  const exists = state.repuestos.find(r => r.productoId === repuestoSeleccionado.value)
  if (exists) {
    if (item && exists.cantidad < (item.stock || 1)) exists.cantidad += 1
  } else {
    state.repuestos.push({ productoId: repuestoSeleccionado.value, cantidad: 1 })
  }
  repuestoSeleccionado.value = ''
}

const quitarServicio = (idx: number) => state.servicios.splice(idx, 1)
const quitarRepuesto = (idx: number) => state.repuestos.splice(idx, 1)

const subtotalServicios = computed(() =>
  state.servicios.reduce((acc, l) => acc + (servicioById(l.servicioId)?.precio || 0) * l.cantidad, 0)
)
const subtotalRepuestos = computed(() =>
  state.repuestos.reduce((acc, l) => acc + (repuestoById(l.productoId)?.precio || 0) * l.cantidad, 0)
)
const total = computed(() => subtotalServicios.value + subtotalRepuestos.value)

const submit = () => {
  if (!state.vehiculoId) {
    alert('Selecciona un vehículo.')
    return
  }
  if (!state.servicios.length && !state.repuestos.length) {
    alert('Agrega al menos un servicio o repuesto.')
    return
  }
  loading.value = true
  router.post(route('portal.presupuestos.store'), {
    vehiculoId: state.vehiculoId,
    notas: state.notas || null,
    servicios: state.servicios,
    repuestos: state.repuestos
  }, {
    onFinish: () => { loading.value = false }
  })
}
</script>

<template>
  <AppDashboardPanel id="portal-presupuesto-create">
    <template #header>
      <UDashboardNavbar title="Nuevo presupuesto">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <UCard class="xl:col-span-2 space-y-4">
          <UAlert
            v-if="Object.keys(errors).length"
            color="error"
            variant="subtle"
            title="Revisa los datos"
            :description="String(Object.values(errors)[0] || '')"
            class="mb-4"
          />

          <FormField label="Vehículo" name="vehiculoId" required :error="errors.vehiculo_id">
            <USelect
              v-model="state.vehiculoId"
              :items="vehiculos.map(v => ({ label: v.label, value: v.id }))"
              placeholder="Selecciona tu vehículo"
              class="w-full"
            />
          </FormField>

          <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-2 items-end mt-4">
            <FormField label="Agregar servicio" name="servicio">
              <USelect
                v-model="servicioSeleccionado"
                :items="catalogoServicios.map(s => ({
                  label: `${s.label} · ${formatMoney(s.precio)}`,
                  value: s.id
                }))"
                placeholder="Buscar servicio"
                class="w-full"
              />
            </FormField>
            <UButton label="Agregar" icon="i-lucide-plus" @click="agregarServicio" />
          </div>

          <ul class="space-y-2 mt-3">
            <li
              v-for="(linea, idx) in state.servicios"
              :key="linea.servicioId"
              class="flex flex-wrap items-center justify-between gap-2 rounded-md border border-default/60 px-3 py-2"
            >
              <div>
                <p class="font-medium text-sm">{{ servicioById(linea.servicioId)?.label }}</p>
                <p class="text-xs text-muted">{{ formatMoney(servicioById(linea.servicioId)?.precio || 0) }} c/u</p>
              </div>
              <div class="flex items-center gap-2">
                <UInput v-model.number="linea.cantidad" type="number" min="1" max="20" class="w-20" />
                <span class="text-sm font-medium w-24 text-right">
                  {{ formatMoney((servicioById(linea.servicioId)?.precio || 0) * linea.cantidad) }}
                </span>
                <UButton icon="i-lucide-trash-2" color="error" variant="ghost" size="xs" @click="quitarServicio(idx)" />
              </div>
            </li>
            <li v-if="!state.servicios.length" class="text-sm text-muted">Sin servicios aún.</li>
          </ul>

          <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-2 items-end mt-6">
            <FormField label="Agregar repuesto" name="repuesto">
              <USelect
                v-model="repuestoSeleccionado"
                :items="catalogoRepuestos.map(r => ({
                  label: `${r.label} · ${formatMoney(r.precio)} · stock ${r.stock}`,
                  value: r.id
                }))"
                placeholder="Buscar repuesto"
                class="w-full"
              />
            </FormField>
            <UButton label="Agregar" icon="i-lucide-plus" variant="soft" @click="agregarRepuesto" />
          </div>

          <ul class="space-y-2 mt-3">
            <li
              v-for="(linea, idx) in state.repuestos"
              :key="linea.productoId"
              class="flex flex-wrap items-center justify-between gap-2 rounded-md border border-default/60 px-3 py-2"
            >
              <div>
                <p class="font-medium text-sm">{{ repuestoById(linea.productoId)?.label }}</p>
                <p class="text-xs text-muted">
                  {{ formatMoney(repuestoById(linea.productoId)?.precio || 0) }} c/u · stock {{ repuestoById(linea.productoId)?.stock }}
                </p>
              </div>
              <div class="flex items-center gap-2">
                <UInput
                  v-model.number="linea.cantidad"
                  type="number"
                  min="1"
                  :max="repuestoById(linea.productoId)?.stock || 1"
                  class="w-20"
                />
                <span class="text-sm font-medium w-24 text-right">
                  {{ formatMoney((repuestoById(linea.productoId)?.precio || 0) * linea.cantidad) }}
                </span>
                <UButton icon="i-lucide-trash-2" color="error" variant="ghost" size="xs" @click="quitarRepuesto(idx)" />
              </div>
            </li>
            <li v-if="!state.repuestos.length" class="text-sm text-muted">Sin repuestos (opcional).</li>
          </ul>

          <FormField label="Notas" name="notas" class="mt-4" :error="errors.notas">
            <UTextarea v-model="state.notas" :rows="3" class="w-full" placeholder="Detalle adicional para el taller" />
          </FormField>
        </UCard>

        <UCard>
          <h3 class="font-semibold mb-3">Resumen</h3>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between"><span>Servicios</span><span>{{ formatMoney(subtotalServicios) }}</span></div>
            <div class="flex justify-between"><span>Repuestos</span><span>{{ formatMoney(subtotalRepuestos) }}</span></div>
            <div class="flex justify-between font-semibold text-base border-t border-default pt-2">
              <span>Total estimado</span><span>{{ formatMoney(total) }}</span>
            </div>
          </div>
          <div class="flex flex-col gap-2 mt-6">
            <UButton label="Guardar presupuesto" icon="i-lucide-check" :loading="loading" block @click="submit" />
            <UButton variant="ghost" color="neutral" label="Cancelar" block :to="route('portal.presupuestos.index')" />
          </div>
        </UCard>
      </div>
    </template>
  </AppDashboardPanel>
</template>
