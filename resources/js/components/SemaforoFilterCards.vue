<script setup lang="ts">
import { computed } from 'vue'
import MetricStatCards, { type MetricCard, type MetricTone } from './MetricStatCards.vue'

export type SemaforoTone = Extract<MetricTone, 'ok' | 'warn' | 'danger' | 'neutral'>

export interface SemaforoCard {
  key: string
  title: string
  value: number | string
  description?: string
  icon?: string
  tone: SemaforoTone
  to: string
}

const props = defineProps<{
  cards: SemaforoCard[]
  modelValue?: string | null
}>()

const mapped = computed((): MetricCard[] =>
  props.cards.map(c => ({
    key: c.key,
    title: c.title,
    value: c.value,
    icon: c.icon,
    tone: c.tone,
    to: c.to
  }))
)
</script>

<template>
  <MetricStatCards :cards="mapped" :model-value="modelValue" :columns="3" />
</template>
