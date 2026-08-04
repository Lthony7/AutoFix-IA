<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

defineProps<{
  to: string
  label: string
}>()
</script>

<template>
  <Link
    :href="to"
    class="inline-flex flex-none items-center gap-1.5 rounded-lg bg-primary px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition-opacity hover:opacity-90"
  >
    <span class="i-lucide-plus text-base" />
    {{ label }}
  </Link>
</template>
