<script setup lang="ts">
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import type { NavigationMenuItem } from '@nuxt/ui'
import { route } from 'ziggy-js'
import TeamsMenu from '../components/TeamsMenu.vue'
import UserMenu from '../components/UserMenu.vue'
import { useAppConfig } from '../composables/useAppConfig'
import { useFlash } from '../composables/useFlash'
import { onMounted, ref } from 'vue'

const open = ref(false)
const appConfig = useAppConfig()
const page = usePage()

onMounted(() => {
  useFlash()
})

const role = computed(() => (page.props as any).auth?.user?.role as string | undefined)

const navigateTo = (url: string) => {
  router.visit(url)
  open.value = false
}

const item = (label: string, icon: string, path: string, routeName?: string): NavigationMenuItem => ({
  label,
  icon,
  to: path,
  onSelect: () => navigateTo(routeName ? route(routeName) : path)
})

const links = computed(() => {
  const items: NavigationMenuItem[] = [
    item('Home', 'i-lucide-house', '/dashboard')
  ]

  if (role.value === 'administrador') {
    items.push(
      item('Clientes', 'i-lucide-users-round', '/clientes', 'clientes.index'),
      item('Vehículos', 'i-lucide-car', '/vehiculos', 'vehiculos.index'),
      item('Mecánicos', 'i-lucide-wrench', '/mecanicos', 'mecanicos.index'),
      item('Servicios', 'i-lucide-cog', '/servicios', 'servicios.index'),
      item('Repuestos', 'i-lucide-package', '/repuestos', 'repuestos.index'),
      item('Órdenes', 'i-lucide-clipboard-list', '/ordenes', 'ordenes.index'),
      item('Diagnóstico IA', 'i-lucide-brain', '/diagnosticos-ia', 'diagnosticos-ia.index'),
      item('Facturas', 'i-lucide-file-text', '/facturas', 'facturas.index'),
      item('Pagos', 'i-lucide-wallet', '/pagos', 'pagos.index'),
      item('Historial', 'i-lucide-history', '/historial', 'historial.index'),
      item('Reportes', 'i-lucide-bar-chart-3', '/reportes', 'reportes.index'),
      item('Usuarios', 'i-lucide-shield-user', '/usuarios', 'usuarios.index')
    )
  } else if (role.value === 'recepcionista') {
    items.push(
      item('Clientes', 'i-lucide-users-round', '/clientes', 'clientes.index'),
      item('Vehículos', 'i-lucide-car', '/vehiculos', 'vehiculos.index'),
      item('Servicios', 'i-lucide-cog', '/servicios', 'servicios.index'),
      item('Repuestos', 'i-lucide-package', '/repuestos', 'repuestos.index'),
      item('Órdenes', 'i-lucide-clipboard-list', '/ordenes', 'ordenes.index'),
      item('Diagnóstico IA', 'i-lucide-brain', '/diagnosticos-ia', 'diagnosticos-ia.index'),
      item('Facturas', 'i-lucide-file-text', '/facturas', 'facturas.index'),
      item('Pagos', 'i-lucide-wallet', '/pagos', 'pagos.index'),
      item('Historial', 'i-lucide-history', '/historial', 'historial.index'),
      item('Reportes', 'i-lucide-bar-chart-3', '/reportes', 'reportes.index')
    )
  } else if (role.value === 'mecanico') {
    items.push(
      item('Órdenes', 'i-lucide-clipboard-list', '/ordenes', 'ordenes.index'),
      item('Diagnóstico IA', 'i-lucide-brain', '/diagnosticos-ia', 'diagnosticos-ia.index')
    )
  } else if (role.value === 'cliente') {
    items.push(
      item('Mis vehículos', 'i-lucide-car', '/portal/mis-vehiculos', 'portal.mis-vehiculos'),
      item('Mis órdenes', 'i-lucide-clipboard-list', '/portal/mis-ordenes', 'portal.mis-ordenes')
    )
  }

  return [items]
})

const groups = computed(() => [{
  id: 'links',
  label: 'Go to',
  items: links.value.flat()
}])
</script>

<template>
  <UApp :primary="appConfig.ui.colors.primary" :neutral="appConfig.ui.colors.neutral">
    <UDashboardGroup unit="rem">
      <UDashboardSidebar
        id="default"
        v-model:open="open"
        collapsible
        resizable
        class="bg-elevated/25"
        :ui="{ footer: 'lg:border-t lg:border-default' }"
      >
        <template #header="{ collapsed }">
          <TeamsMenu :collapsed="collapsed" />
        </template>

        <template #default="{ collapsed }">
          <UNavigationMenu
            :collapsed="collapsed"
            :items="links[0]"
            orientation="vertical"
            tooltip
            popover
          />
        </template>

        <template #footer="{ collapsed }">
          <UserMenu :collapsed="collapsed" />
        </template>
      </UDashboardSidebar>

      <UDashboardSearch :groups="groups" />

      <slot />
    </UDashboardGroup>
  </UApp>
</template>
