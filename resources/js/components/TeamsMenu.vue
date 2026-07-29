<script setup lang="ts">
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLogo from './AppLogo.vue'

defineProps<{
  collapsed?: boolean
}>()

const page = usePage()
const role = computed(() => (page.props as any).auth?.user?.role as string | undefined)

const homeUrl = computed(() =>
  role.value === 'cliente' ? route('portal.mis-ordenes') : '/dashboard'
)

const goHome = () => {
  router.visit(homeUrl.value)
}
</script>

<template>
  <div class="px-1 py-0.5" :class="collapsed ? 'flex justify-center' : ''">
    <button
      type="button"
      class="min-w-0 rounded-lg text-left outline-none transition hover:opacity-90 focus-visible:ring-2 focus-visible:ring-primary cursor-pointer"
      title="Ir al inicio"
      @click="goHome"
    >
      <AppLogo :size="collapsed ? 'sm' : 'md'" :show-text="!collapsed" />
    </button>
  </div>
</template>
