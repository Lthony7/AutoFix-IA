<script setup lang="ts">
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'

interface Meta {
  total: number
  perPage: number
  currentPage: number
  lastPage: number
  from?: number | null
  to?: number | null
}

const props = withDefaults(defineProps<{
  meta?: Meta | null
  query?: Record<string, string | number | undefined | null>
}>(), {
  meta: null,
  query: () => ({})
})

const show = computed(() => (props.meta?.lastPage ?? 1) > 1 || (props.meta?.total ?? 0) > 0)

const summary = computed(() => {
  if (!props.meta?.total) return 'Sin registros'
  const from = props.meta.from ?? 0
  const to = props.meta.to ?? 0
  return `Mostrando ${from}–${to} de ${props.meta.total}`
})

const pageItems = computed(() => {
  const current = props.meta?.currentPage ?? 1
  const last = props.meta?.lastPage ?? 1
  const windowSize = 5
  let start = Math.max(1, current - Math.floor(windowSize / 2))
  let end = Math.min(last, start + windowSize - 1)
  start = Math.max(1, end - windowSize + 1)

  const items: Array<number | 'ellipsis'> = []
  if (start > 1) {
    items.push(1)
    if (start > 2) items.push('ellipsis')
  }
  for (let p = start; p <= end; p++) items.push(p)
  if (end < last) {
    if (end < last - 1) items.push('ellipsis')
    items.push(last)
  }
  return items
})

const goTo = (page: number) => {
  if (!props.meta) return
  if (page < 1 || page > props.meta.lastPage || page === props.meta.currentPage) return

  const params: Record<string, string | number> = { page }
  Object.entries(props.query || {}).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      params[key] = value as string | number
    }
  })

  router.get(window.location.pathname, params, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  })
}
</script>

<template>
  <div
    v-if="show"
    class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
  >
    <p class="text-sm text-muted">
      {{ summary }}
    </p>

    <div v-if="meta && meta.lastPage > 1" class="flex flex-wrap items-center gap-1">
      <UButton
        size="xs"
        color="neutral"
        variant="outline"
        icon="i-lucide-chevron-left"
        :disabled="meta.currentPage <= 1"
        @click="goTo(meta.currentPage - 1)"
      />

      <template v-for="(item, idx) in pageItems" :key="`${item}-${idx}`">
        <span v-if="item === 'ellipsis'" class="px-2 text-muted">…</span>
        <UButton
          v-else
          size="xs"
          :color="item === meta.currentPage ? 'primary' : 'neutral'"
          :variant="item === meta.currentPage ? 'solid' : 'outline'"
          :label="String(item)"
          @click="goTo(item)"
        />
      </template>

      <UButton
        size="xs"
        color="neutral"
        variant="outline"
        icon="i-lucide-chevron-right"
        :disabled="meta.currentPage >= meta.lastPage"
        @click="goTo(meta.currentPage + 1)"
      />
    </div>
  </div>
</template>
