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

interface NavGroup {
  title: string | null
  items: NavigationMenuItem[]
}

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

const navGroups = computed((): NavGroup[] => {
  if (role.value === 'cliente') {
    return [{
      title: null,
      items: [
        item('Mis vehículos', 'i-lucide-car', '/portal/mis-vehiculos', 'portal.mis-vehiculos'),
        item('Presupuestos', 'i-lucide-calculator', '/portal/presupuestos', 'portal.presupuestos.index'),
        item('Mis órdenes', 'i-lucide-clipboard-list', '/portal/mis-ordenes', 'portal.mis-ordenes'),
        item('Historial', 'i-lucide-history', '/portal/historial', 'portal.historial'),
        item('Mis datos', 'i-lucide-user-round', '/portal/mis-datos', 'portal.mis-datos')
      ]
    }]
  }

  const home: NavGroup = {
    title: null,
    items: [item('Home', 'i-lucide-house', '/dashboard')]
  }

  if (role.value === 'administrador') {
    return [
      home,
      {
        title: 'Maestros',
        items: [
          item('Clientes', 'i-lucide-users-round', '/clientes', 'clientes.index'),
          item('Vehículos', 'i-lucide-car', '/vehiculos', 'vehiculos.index'),
          item('Mecánicos', 'i-lucide-wrench', '/mecanicos', 'mecanicos.index'),
          item('Servicios', 'i-lucide-cog', '/servicios', 'servicios.index')
        ]
      },
      {
        title: 'Operación',
        items: [
          item('Órdenes', 'i-lucide-clipboard-list', '/ordenes', 'ordenes.index'),
          item('Presupuestos', 'i-lucide-calculator', '/presupuestos', 'presupuestos.index'),
          item('Diagnóstico IA', 'i-lucide-brain', '/diagnosticos-ia', 'diagnosticos-ia.index')
        ]
      },
      {
        title: 'Cobro',
        items: [
          item('Facturas', 'i-lucide-file-text', '/facturas', 'facturas.index'),
          item('Pagos', 'i-lucide-wallet', '/pagos', 'pagos.index')
        ]
      },
      {
        title: 'Gestión',
        items: [
          item('Historial', 'i-lucide-history', '/historial', 'historial.index'),
          item('Inventario', 'i-lucide-package', '/inventario', 'inventario.index'),
          item('Reportes', 'i-lucide-bar-chart-3', '/reportes', 'reportes.index'),
          item('Usuarios', 'i-lucide-shield-user', '/usuarios', 'usuarios.index')
        ]
      }
    ]
  }

  if (role.value === 'recepcionista') {
    return [
      home,
      {
        title: 'Maestros',
        items: [
          item('Clientes', 'i-lucide-users-round', '/clientes', 'clientes.index'),
          item('Vehículos', 'i-lucide-car', '/vehiculos', 'vehiculos.index'),
          item('Servicios', 'i-lucide-cog', '/servicios', 'servicios.index')
        ]
      },
      {
        title: 'Operación',
        items: [
          item('Órdenes', 'i-lucide-clipboard-list', '/ordenes', 'ordenes.index'),
          item('Presupuestos', 'i-lucide-calculator', '/presupuestos', 'presupuestos.index'),
          item('Diagnóstico IA', 'i-lucide-brain', '/diagnosticos-ia', 'diagnosticos-ia.index')
        ]
      },
      {
        title: 'Cobro',
        items: [
          item('Facturas', 'i-lucide-file-text', '/facturas', 'facturas.index'),
          item('Pagos', 'i-lucide-wallet', '/pagos', 'pagos.index')
        ]
      },
      {
        title: 'Gestión',
        items: [
          item('Historial', 'i-lucide-history', '/historial', 'historial.index'),
          item('Inventario', 'i-lucide-package', '/inventario', 'inventario.index'),
          item('Reportes', 'i-lucide-bar-chart-3', '/reportes', 'reportes.index')
        ]
      }
    ]
  }

  if (role.value === 'mecanico') {
    return [
      home,
      {
        title: 'Operación',
        items: [
          item('Órdenes', 'i-lucide-clipboard-list', '/ordenes', 'ordenes.index'),
          item('Diagnóstico IA', 'i-lucide-brain', '/diagnosticos-ia', 'diagnosticos-ia.index')
        ]
      }
    ]
  }

  return [home]
})

const searchGroups = computed(() => [{
  id: 'links',
  label: 'Go to',
  items: navGroups.value.flatMap(g => g.items)
}])

const menuUi = {
  item: 'my-0.5',
  link: [
    'py-2.5 gap-3 rounded-lg text-white/90 transition-colors',
    'hover:bg-white/10 hover:text-white',
    'data-[active]:!bg-emerald-100 data-[active]:!text-slate-900',
    'aria-[current=page]:!bg-emerald-100 aria-[current=page]:!text-slate-900'
  ].join(' '),
  linkLeadingIcon: [
    'size-5 text-white/75',
    'group-data-[active]:!text-slate-800',
    'group-aria-[current=page]:!text-slate-800'
  ].join(' '),
  linkLabel: 'text-sm font-medium data-[active]:!text-slate-900 aria-[current=page]:!text-slate-900'
}
</script>

<template>
  <UApp :primary="appConfig.ui.colors.primary" :neutral="appConfig.ui.colors.neutral">
    <UDashboardGroup unit="rem">
      <UDashboardSidebar
        id="default"
        v-model:open="open"
        collapsible
        resizable
        class="autofix-sidebar border-none bg-gradient-to-b from-[#0b1f3a] via-[#0f3d4d] to-[#0a5c52] text-white"
        :ui="{
          root: 'border-none',
          header: 'border-b border-white/10 py-3 px-3',
          footer: 'border-t border-white/10',
          body: 'px-2 py-3'
        }"
      >
        <template #header="{ collapsed }">
          <TeamsMenu :collapsed="collapsed" on-dark />
        </template>

        <template #default="{ collapsed }">
          <div class="flex flex-col gap-5">
            <div
              v-for="(group, idx) in navGroups"
              :key="group.title || `group-${idx}`"
              class="space-y-1"
            >
              <p
                v-if="group.title && !collapsed"
                class="px-3 pb-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-white/45"
              >
                {{ group.title }}
              </p>
              <div
                v-else-if="group.title && collapsed"
                class="mx-auto my-1 h-px w-6 bg-white/20"
              />
              <UNavigationMenu
                :collapsed="collapsed"
                :items="group.items"
                orientation="vertical"
                tooltip
                popover
                color="neutral"
                variant="pill"
                :ui="menuUi"
              />
            </div>
          </div>
        </template>

        <template #footer="{ collapsed }">
          <UserMenu :collapsed="collapsed" />
        </template>
      </UDashboardSidebar>

      <UDashboardSearch :groups="searchGroups" />

      <div class="dashboard-main flex min-h-0 min-w-0 flex-1 flex-col self-stretch overflow-hidden">
        <slot />
      </div>
    </UDashboardGroup>
  </UApp>
</template>

<style scoped>
/* Activo: fondo claro + letras oscuras (buen contraste) */
.autofix-sidebar :deep(a[data-active='true']),
.autofix-sidebar :deep(a[aria-current='page']),
.autofix-sidebar :deep([data-active='true'] > a),
.autofix-sidebar :deep([aria-current='page']) {
  background-color: #d1fae5 !important; /* emerald-100 */
  color: #0f172a !important; /* slate-900 */
  font-weight: 600;
}

.autofix-sidebar :deep(a[data-active='true'] svg),
.autofix-sidebar :deep(a[aria-current='page'] svg),
.autofix-sidebar :deep([data-active='true'] svg),
.autofix-sidebar :deep([aria-current='page'] svg) {
  color: #064e3b !important; /* emerald-900 */
}
</style>
