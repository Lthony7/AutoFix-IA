<script setup lang="ts">
import AppLogo from './AppLogo.vue'

defineProps<{
  collapsed?: boolean
}>()
</script>

<template>
  <div class="px-1 py-0.5" :class="collapsed ? 'flex justify-center' : ''">
    <AppLogo :size="collapsed ? 'sm' : 'md'" :show-text="!collapsed" />
  </div>
</template>
