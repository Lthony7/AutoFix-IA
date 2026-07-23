<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLogo from '../components/AppLogo.vue'

defineOptions({
  layout: null
})

const scrollToInfo = () => {
  document.getElementById('info')?.scrollIntoView({ behavior: 'smooth' })
}
</script>

<template>
  <div class="min-h-dvh overflow-y-auto bg-zinc-950 text-white">
    <section class="relative flex min-h-dvh items-center justify-center overflow-hidden">
      <img
        src="/images/hero-taller.png"
        alt=""
        class="absolute inset-0 h-full w-full object-cover object-center"
        aria-hidden="true"
      >

      <!-- Overlay suave: se ve el taller, el texto sigue legible -->
      <div
        class="absolute inset-0 bg-gradient-to-b from-zinc-950/55 via-zinc-950/45 to-zinc-950/80"
        aria-hidden="true"
      />

      <div class="relative z-10 mx-auto flex w-full max-w-4xl flex-col items-center px-6 py-16 text-center">
        <div class="mb-8 [&_p.font-semibold]:text-white [&_p.text-muted]:text-white/75 [&_span.text-emerald-700]:text-emerald-400">
          <AppLogo size="xl" stacked />
        </div>

        <h1 class="max-w-3xl text-3xl font-bold uppercase tracking-tight text-white sm:text-4xl md:text-5xl md:leading-tight">
          Reparación automotriz con inteligencia artificial
        </h1>

        <p class="mt-4 max-w-2xl text-base text-white/85 sm:text-lg">
          Diagnósticos precisos y reparaciones rápidas usando tecnología avanzada.
        </p>

        <div class="mt-10 flex w-full max-w-lg flex-col gap-3 sm:max-w-none sm:flex-row sm:flex-wrap sm:justify-center">
          <Link
            :href="route('register')"
            class="inline-flex h-12 min-w-44 items-center justify-center rounded-md bg-emerald-600 px-6 text-sm font-bold uppercase tracking-wide text-white shadow-lg transition hover:bg-emerald-500"
          >
            Solicitar cita
          </Link>
          <button
            type="button"
            class="inline-flex h-12 min-w-44 items-center justify-center rounded-md border border-white/70 bg-transparent px-6 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-white/10"
            @click="scrollToInfo"
          >
            Más información
          </button>
          <Link
            :href="route('login')"
            class="inline-flex h-12 min-w-44 items-center justify-center rounded-md border border-emerald-400/60 bg-emerald-950/50 px-6 text-sm font-bold tracking-wide text-white transition hover:bg-emerald-900/60"
          >
            Ya tengo cuenta — Iniciar sesión
          </Link>
        </div>
      </div>
    </section>

    <section id="info" class="border-t border-white/10 bg-zinc-900 px-6 py-20">
      <div class="mx-auto grid max-w-5xl gap-10 md:grid-cols-3">
        <div>
          <h2 class="text-lg font-semibold text-emerald-400">Diagnóstico IA</h2>
          <p class="mt-2 text-sm text-white/70">
            Sugerencias asistidas por IA para orientar al mecánico con mayor rapidez.
          </p>
        </div>
        <div>
          <h2 class="text-lg font-semibold text-emerald-400">Órdenes y facturas</h2>
          <p class="mt-2 text-sm text-white/70">
            Seguimiento de la orden de trabajo, servicios, repuestos y cobro en un solo flujo.
          </p>
        </div>
        <div>
          <h2 class="text-lg font-semibold text-emerald-400">Portal del cliente</h2>
          <p class="mt-2 text-sm text-white/70">
            Consulta tus vehículos y el estado de tus órdenes cuando lo necesites.
          </p>
        </div>
      </div>
    </section>
  </div>
</template>
