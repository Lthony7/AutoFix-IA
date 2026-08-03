<script setup lang="ts">
/**
 * Contenedor blanco de módulo (estilo captura: título + acción + tabla).
 */
defineProps<{
  title?: string
}>()
</script>

<template>
  <section class="autofix-module-panel">
    <header v-if="title || $slots.actions || $slots.header" class="autofix-module-panel__header">
      <div class="min-w-0">
        <h2 v-if="title" class="autofix-module-panel__title">{{ title }}</h2>
        <slot name="header" />
      </div>
      <div v-if="$slots.actions" class="autofix-module-panel__actions">
        <slot name="actions" />
      </div>
    </header>
    <div class="autofix-module-panel__body">
      <slot />
    </div>
    <footer v-if="$slots.footer" class="autofix-module-panel__footer">
      <slot name="footer" />
    </footer>
  </section>
</template>
