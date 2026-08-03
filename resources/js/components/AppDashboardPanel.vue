<script setup lang="ts">
import { route } from 'ziggy-js'

/**
 * Panel del dashboard con scroll vertical en el body (formularios y listados).
 * Incluye el botón Calendario a la derecha del header, sin tapar acciones de cada página.
 *
 * Tema visual: classes autofix-* (ver resources/css/app.css — bloque AUTOFIX surface theme).
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
      body: 'flex min-h-0 flex-1 flex-col gap-3 overflow-y-auto overscroll-contain p-3 sm:gap-6 sm:p-6 bg-transparent pb-[max(2.5rem,env(safe-area-inset-bottom))]'
    }"
  >
    <template v-if="$slots.header" #header>
      <div class="autofix-panel-header flex w-full min-w-0 items-stretch">
        <div class="min-w-0 flex-1 overflow-hidden">
          <slot name="header" />
        </div>
        <div class="autofix-panel-calendar flex shrink-0 items-center border-b border-default/60 bg-transparent px-1.5 sm:px-3">
          <UTooltip text="Calendario">
            <UButton
              icon="i-lucide-calendar"
              color="neutral"
              variant="ghost"
              size="sm"
              aria-label="Calendario"
              :to="route('calendario.index')"
            />
          </UTooltip>
        </div>
      </div>
    </template>
    <template v-if="$slots.body" #body>
      <div class="autofix-surface w-full min-w-0 max-w-full pb-6 sm:pb-10">
        <slot name="body" />
      </div>
    </template>
    <template v-if="$slots.footer" #footer>
      <slot name="footer" />
    </template>
  </UDashboardPanel>
</template>
