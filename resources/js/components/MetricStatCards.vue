<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

/** Paletas alineadas a la captura de referencia + semáforo. */
export type MetricTone =
  | 'green'
  | 'blue'
  | 'purple'
  | 'lime'
  | 'ok'
  | 'warn'
  | 'danger'
  | 'neutral'

export interface MetricCard {
  key: string
  title: string
  value: number | string
  icon?: string
  tone: MetricTone
  /** Si existe, la card es clicable (filtro / navegación). */
  to?: string
}

defineProps<{
  cards: MetricCard[]
  modelValue?: string | null
  columns?: 3 | 4
}>()
</script>

<template>
  <div
    class="autofix-metric-grid"
    :class="columns === 4 ? 'autofix-metric-grid--4' : 'autofix-metric-grid--3'"
  >
    <component
      :is="card.to ? Link : 'div'"
      v-for="card in cards"
      :key="card.key"
      v-bind="card.to ? { href: card.to, 'preserve-scroll': true } : {}"
      class="autofix-metric-card"
      :data-tone="card.tone"
      :data-active="modelValue === card.key ? 'true' : 'false'"
      :data-clickable="card.to ? 'true' : 'false'"
    >
      <div class="autofix-metric-icon" aria-hidden="true">
        <UIcon :name="card.icon || 'i-lucide-circle'" class="size-5 sm:size-6" />
      </div>
      <div class="autofix-metric-body">
        <p class="autofix-metric-title">{{ card.title }}</p>
        <p class="autofix-metric-value">{{ card.value }}</p>
      </div>
    </component>
  </div>
</template>
