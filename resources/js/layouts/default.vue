<script setup lang="ts">
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import type { NavigationMenuItem } from '@nuxt/ui'
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

const links = computed(() => {
  const items: NavigationMenuItem[] = [{
    label: 'Home',
    icon: 'i-lucide-house',
    to: '/dashboard',
    onSelect: () => navigateTo('/dashboard')
  }]

  if (role.value === 'administrador' || role.value === 'recepcionista') {
    items.push(
      {
        label: 'Clientes',
        icon: 'i-lucide-users-round',
        to: '/clientes',
        onSelect: () => navigateTo('/clientes')
      },
      {
        label: 'Vehículos',
        icon: 'i-lucide-car',
        to: '/vehiculos',
        onSelect: () => navigateTo('/vehiculos')
      }
    )
  }

  if (role.value === 'administrador') {
    items.push(
      {
        label: 'Mecánicos',
        icon: 'i-lucide-wrench',
        to: '/mecanicos',
        onSelect: () => navigateTo('/mecanicos')
      },
      {
        label: 'Usuarios',
        icon: 'i-lucide-shield-user',
        to: '/usuarios',
        onSelect: () => navigateTo('/usuarios')
      }
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
