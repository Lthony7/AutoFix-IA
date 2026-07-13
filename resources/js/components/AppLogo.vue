<script setup lang="ts">
withDefaults(defineProps<{
  size?: 'sm' | 'md' | 'lg' | 'xl'
  showText?: boolean
}>(), {
  size: 'md',
  showText: true
})

const sizeClass = {
  sm: 'h-9 w-9',
  md: 'h-11 w-11',
  lg: 'h-16 w-16',
  xl: 'h-20 w-20'
}

const titleClass = {
  sm: 'text-sm',
  md: 'text-sm',
  lg: 'text-lg',
  xl: 'text-xl'
}

const subtitleClass = {
  sm: 'text-[10px]',
  md: 'text-xs',
  lg: 'text-sm',
  xl: 'text-sm'
}
</script>

<template>
  <div class="flex items-center gap-3 min-w-0">
    <img
      src="/logo.png"
      alt="AUTOFIX IA"
      class="shrink-0 object-contain rounded-lg bg-white ring-1 ring-default/40"
      :class="sizeClass[size]"
    >

    <div v-if="showText" class="min-w-0 text-left">
      <p class="truncate font-semibold tracking-tight leading-tight" :class="titleClass[size]">
        AUTOFIX <span class="text-emerald-700 dark:text-emerald-400">IA</span>
      </p>
      <p class="truncate text-muted leading-tight" :class="subtitleClass[size]">
        Taller Automotriz
      </p>
    </div>
  </div>
</template>
