<script setup lang="ts">
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLogo from './AppLogo.vue'

defineProps<{
  collapsed?: boolean
  onDark?: boolean
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
  <div
    class="autofix-sidebar-brand"
    :class="collapsed ? 'autofix-sidebar-brand--collapsed' : ''"
  >
    <button
      type="button"
      class="autofix-sidebar-brand__btn min-w-0 rounded-xl text-left outline-none transition hover:opacity-95 focus-visible:ring-2 cursor-pointer"
      :class="onDark ? 'focus-visible:ring-emerald-300/60' : 'focus-visible:ring-primary'"
      title="Ir al inicio"
      @click="goHome"
    >
      <AppLogo
        :size="collapsed ? 'sm' : 'md'"
        :show-text="!collapsed"
        :on-dark="onDark"
      />
    </button>
  </div>
</template>

<style scoped>
.autofix-sidebar-brand {
  padding: 0.35rem 0.15rem;
}

.autofix-sidebar-brand--collapsed {
  display: flex;
  justify-content: center;
}

.autofix-sidebar-brand__btn {
  width: 100%;
  padding: 0.35rem 0.4rem;
}

.autofix-sidebar-brand--collapsed .autofix-sidebar-brand__btn {
  width: auto;
  padding: 0.25rem;
}
</style>
