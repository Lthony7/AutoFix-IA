<script setup lang="ts">
/**
 * Panel del dashboard con scroll vertical en el body (formularios y listados).
 */
defineProps<{
  id?: string
}>()
</script>

<template>
  <UDashboardPanel
    :id="id"
    :ui="{
      root: 'relative flex h-full max-h-full min-h-0 min-w-0 flex-1 flex-col overflow-hidden',
      body: 'flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto overscroll-contain p-4 sm:gap-6 sm:p-6'
    }"
  >
    <template v-if="$slots.header" #header>
      <slot name="header" />
    </template>
    <template v-if="$slots.body" #body>
      <div class="w-full pb-10">
        <slot name="body" />
      </div>
    </template>
    <template v-if="$slots.footer" #footer>
      <slot name="footer" />
    </template>
  </UDashboardPanel>
</template>
