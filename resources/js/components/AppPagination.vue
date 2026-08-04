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
  query?: Record<string, string | number | boolean | undefined | null>
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

  const url = new URL(window.location.href)
  const params: Record<string, string | number> = {}

  // Conserva filtros actuales de la URL
  url.searchParams.forEach((value, key) => {
    if (key !== 'page' && value !== '') {
      params[key] = value
    }
  })

  // Permite sobrescribir / añadir query explícita del padre
  Object.entries(props.query || {}).forEach(([key, value]) => {
    if (value === undefined || value === null || value === '' || value === false) {
      delete params[key]
      return
    }
    params[key] = typeof value === 'boolean' ? (value ? '1' : '0') : value
  })

  params.page = page

  router.get(url.pathname, params, {
    preserveScroll: true,
    // false: fuerza a Inertia a refrescar props (data de la tabla)
    preserveState: false,
    replace: false
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

    <div v-if="meta && meta.lastPage > 1" class="flex flex-wrap items-center gap-1.5">
      <button
        type="button"
        aria-label="Anterior"
        :disabled="meta.currentPage <= 1"
        class="inline-flex h-8 w-8 flex-none items-center justify-center rounded-md text-sm text-default transition-colors hover:bg-default-500/10 disabled:opacity-40 disabled:cursor-not-allowed"
        @click="goTo(meta.currentPage - 1)"
      >
        <span class="i-lucide-chevron-left" />
      </button>

      <template v-for="(item, idx) in pageItems" :key="`${item}-${idx}`">
        <span v-if="item === 'ellipsis'" class="px-2 text-sm text-muted">…</span>
        <button
          v-else
          type="button"
          :class="[
            'inline-flex h-8 w-8 flex-none items-center justify-center rounded-md text-sm whitespace-nowrap transition-colors',
            item === meta.currentPage
              ? 'bg-primary font-semibold text-white shadow-sm'
              : 'text-default hover:bg-default-500/10',
          ]"
          @click="goTo(item)"
        >
          {{ item }}
        </button>
      </template>

      <button
        type="button"
        aria-label="Siguiente"
        :disabled="meta.currentPage >= meta.lastPage"
        class="inline-flex h-8 w-8 flex-none items-center justify-center rounded-md text-sm text-default transition-colors hover:bg-default-500/10 disabled:opacity-40 disabled:cursor-not-allowed"
        @click="goTo(meta.currentPage + 1)"
      >
        <span class="i-lucide-chevron-right" />
      </button>
    </div>
  </div>
</template>
