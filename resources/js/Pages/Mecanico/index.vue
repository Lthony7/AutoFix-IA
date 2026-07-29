<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import MecanicoFichaSlideover, { type MecanicoFicha } from '../../components/MecanicoFichaSlideover.vue'

interface Mecanico extends MecanicoFicha {}

const page = usePage()
const mecanicos = computed(() => (page.props as any).mecanicos)

const fichaOpen = ref(false)
const mecanicoFicha = ref<MecanicoFicha | null>(null)

const abrirFicha = (item: Mecanico) => {
  mecanicoFicha.value = item
  fichaOpen.value = true
}

watch(mecanicos, (lista) => {
  if (!mecanicoFicha.value || !fichaOpen.value) return
  const actualizado = (lista?.data || []).find((m: Mecanico) => m.id === mecanicoFicha.value?.id)
  if (actualizado) mecanicoFicha.value = actualizado
  else {
    fichaOpen.value = false
    mecanicoFicha.value = null
  }
})
</script>

<template>
  <AppDashboardPanel id="mecanicos">
    <template #header>
      <UDashboardNavbar title="Mecánicos">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton icon="i-lucide-plus" label="Nuevo mecánico" :to="route('mecanicos.create')" />
        </template>
      </UDashboardNavbar>
    </template>
    <template #body>
      <UCard>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left border-b border-default">
                <th class="py-3 pr-3">Nombre</th>
                <th class="py-3 pr-3">Documento</th>
                <th class="py-3 pr-3">Especialidad</th>
                <th class="py-3 pr-3">Horario</th>
                <th class="py-3 pr-3">Estado</th>
                <th class="py-3">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in (mecanicos?.data || []) as Mecanico[]"
                :key="item.id"
                class="border-b border-default/60 cursor-pointer hover:bg-elevated/40 transition-colors"
                @click="abrirFicha(item)"
              >
                <td class="py-3 pr-3 font-medium">{{ item.nombreCompleto }}</td>
                <td class="py-3 pr-3">{{ item.documento }}</td>
                <td class="py-3 pr-3">{{ item.especialidad }}</td>
                <td class="py-3 pr-3">{{ item.horarioDisponible || '—' }}</td>
                <td class="py-3 pr-3">
                  <UBadge :color="item.activo ? 'success' : 'neutral'" variant="subtle">
                    {{ item.activo ? 'Activo' : 'Inactivo' }}
                  </UBadge>
                </td>
                <td class="py-3" @click.stop>
                  <UButton
                    size="xs"
                    variant="soft"
                    icon="i-lucide-panel-right-open"
                    label="Gestionar"
                    @click="abrirFicha(item)"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <AppPagination :meta="mecanicos?.meta" />
      </UCard>

      <MecanicoFichaSlideover
        v-model:open="fichaOpen"
        :mecanico="mecanicoFicha"
        @deleted="mecanicoFicha = null"
      />
    </template>
  </AppDashboardPanel>
</template>
