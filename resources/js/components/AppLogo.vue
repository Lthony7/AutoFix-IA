<script setup lang="ts">
withDefaults(defineProps<{
  size?: 'sm' | 'md' | 'lg' | 'xl'
  showText?: boolean
  stacked?: boolean
  onDark?: boolean
}>(), {
  size: 'md',
  showText: true,
  stacked: false,
  onDark: false
})

const markFrameClass = {
  sm: 'h-11 w-11 p-0.5',
  md: 'h-[3.35rem] w-[3.35rem] p-0.5',
  lg: 'h-16 w-16 p-1',
  xl: 'h-20 w-20 p-1'
}

const markImgClass = {
  sm: 'h-full w-full',
  md: 'h-full w-full',
  lg: 'h-full w-full',
  xl: 'h-full w-full'
}

const titleClass = {
  sm: 'text-[13px]',
  md: 'text-[15px]',
  lg: 'text-xl',
  xl: 'text-2xl'
}

const subtitleClass = {
  sm: 'text-[9px]',
  md: 'text-[10px]',
  lg: 'text-xs',
  xl: 'text-sm'
}
</script>

<template>
  <div
    class="autofix-brand flex min-w-0 items-center gap-3"
    :class="[
      stacked ? 'flex-col text-center' : '',
      onDark ? 'autofix-brand--dark' : 'autofix-brand--light'
    ]"
  >
    <!-- Placa clara: el escudo resalta sobre el sidebar navy -->
    <span
      class="autofix-brand__frame inline-flex shrink-0 items-center justify-center"
      :class="markFrameClass[size]"
      aria-hidden="true"
    >
      <img
        src="/logo-mark.png"
        alt=""
        class="autofix-brand__mark object-contain"
        :class="markImgClass[size]"
      >
    </span>

    <div
      v-if="showText"
      class="autofix-brand__text min-w-0"
      :class="stacked ? 'text-center' : 'text-left'"
    >
      <p
        class="autofix-brand__title truncate font-extrabold uppercase tracking-wide leading-none"
        :class="titleClass[size]"
      >
        AUTOFIX
        <span class="autofix-brand__ia">IA</span>
      </p>
      <p
        class="autofix-brand__subtitle truncate uppercase tracking-[0.14em] leading-tight"
        :class="subtitleClass[size]"
      >
        Taller Automotriz
      </p>
    </div>
  </div>
</template>

<style scoped>
.autofix-brand__frame {
  border-radius: 0.9rem;
  background: #fff;
  box-shadow:
    0 0 0 1px rgb(255 255 255 / 0.35),
    0 6px 14px rgb(0 0 0 / 0.28);
}

.autofix-brand__mark {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: contain;
  filter: drop-shadow(0 1px 1px rgb(0 0 0 / 0.12));
}

.autofix-brand--dark .autofix-brand__frame {
  background: linear-gradient(180deg, #ffffff 0%, #eef7f1 100%);
  box-shadow:
    0 0 0 1px rgb(57 255 106 / 0.35),
    0 8px 18px rgb(0 0 0 / 0.35);
}

.autofix-brand--dark .autofix-brand__title {
  color: #ffffff;
  text-shadow: 0 1px 2px rgb(0 0 0 / 0.35);
}

.autofix-brand--dark .autofix-brand__ia {
  color: #39ff6a;
}

.autofix-brand--dark .autofix-brand__subtitle {
  margin-top: 0.28rem;
  color: rgb(255 255 255 / 0.78);
}

.autofix-brand--light .autofix-brand__frame {
  background: #fff;
  box-shadow: 0 0 0 1px rgb(15 23 42 / 0.08);
}

.autofix-brand--light .autofix-brand__title {
  color: #0f172a;
}

.autofix-brand--light .autofix-brand__ia {
  color: #15803d;
}

.autofix-brand--light .autofix-brand__subtitle {
  margin-top: 0.22rem;
  color: #64748b;
}
</style>
