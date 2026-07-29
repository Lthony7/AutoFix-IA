<script setup lang="ts">
import { route } from 'ziggy-js'

/**
 * Panel del dashboard con scroll vertical en el body (formularios y listados).
 * Incluye el botón Calendario a la derecha del header, sin tapar acciones de cada página.
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
      <div class="flex w-full items-stretch">
        <div class="min-w-0 flex-1">
          <slot name="header" />
        </div>
        <div class="flex shrink-0 items-center border-b border-default bg-default px-3">
          <UTooltip text="Calendario">
            <UButton
              icon="i-lucide-calendar"
              color="neutral"
              variant="ghost"
              aria-label="Calendario"
              :to="route('calendario.index')"
            />
          </UTooltip>
        </div>
      </div>
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
