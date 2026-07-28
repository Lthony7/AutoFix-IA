<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { route } from 'ziggy-js'
import AppLogo from '../components/AppLogo.vue'

defineOptions({
  layout: null
})

const page = usePage()
const authUser = computed(() => (page.props as any).auth?.user ?? null)

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

      <div
        class="absolute inset-0 bg-gradient-to-b from-zinc-950/60 via-zinc-950/50 to-zinc-950/85"
        aria-hidden="true"
      />

      <div class="relative z-10 mx-auto flex w-full max-w-5xl flex-col items-center px-6 py-16 text-center">
        <div class="mb-8 [&_p.font-semibold]:text-white [&_p.text-muted]:text-white/75 [&_span.text-emerald-700]:text-emerald-400">
          <AppLogo size="xl" stacked />
        </div>

        <p class="mb-3 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-400">
          Portal de bienvenida
        </p>

        <h1 class="max-w-3xl text-3xl font-bold tracking-tight text-white sm:text-4xl md:text-5xl md:leading-tight">
          AUTOFIX IA
        </h1>

        <p class="mt-4 max-w-2xl text-base text-white/85 sm:text-lg">
          Reparación automotriz con inteligencia artificial.
        </p>

        <div class="mt-12 grid w-full max-w-3xl gap-4 sm:grid-cols-2">
          <Link
            v-if="!authUser"
            :href="route('login')"
            class="group flex flex-col items-start rounded-xl border border-emerald-400/35 bg-emerald-950/45 p-5 text-left backdrop-blur-sm transition hover:border-emerald-400/60 hover:bg-emerald-900/50"
          >
            <span class="mt-0 text-lg font-semibold text-white">Iniciar sesión</span>
            <span class="mt-2 text-sm text-white/70">
              Accede con tu correo y contraseña.
            </span>
          </Link>

          <Link
            v-if="!authUser"
            :href="route('register')"
            class="group flex flex-col items-start rounded-xl border border-white/15 bg-zinc-950/55 p-5 text-left backdrop-blur-sm transition hover:border-emerald-400/50 hover:bg-zinc-900/70"
          >
            <span class="mt-0 text-lg font-semibold text-white">Crear cuenta</span>
            <span class="mt-2 text-sm text-white/65">
              Registro para clientes nuevos.
            </span>
          </Link>

          <Link
            v-if="authUser"
            :href="route('dashboard')"
            class="group flex flex-col items-start rounded-xl border border-emerald-400/40 bg-emerald-950/40 p-5 text-left backdrop-blur-sm transition hover:bg-emerald-900/50 sm:col-span-2"
          >
            <span class="mt-0 text-lg font-semibold text-white">Continuar — {{ authUser.name }}</span>
            <span class="mt-2 text-sm text-white/70">
              Volver al panel.
            </span>
          </Link>
        </div>

        <button
          type="button"
          class="mt-8 inline-flex h-11 items-center justify-center rounded-md border border-white/40 bg-transparent px-6 text-sm font-semibold tracking-wide text-white transition hover:bg-white/10"
          @click="scrollToInfo"
        >
          Más información del sistema
        </button>
      </div>
    </section>

    <section id="info" class="relative overflow-hidden border-t border-white/10 bg-zinc-900 px-6 py-24">
      <div
        class="pointer-events-none absolute -right-24 top-0 h-72 w-72 rounded-full bg-emerald-500/10 blur-3xl"
        aria-hidden="true"
      />
      <div
        class="pointer-events-none absolute -left-16 bottom-0 h-64 w-64 rounded-full bg-emerald-600/5 blur-3xl"
        aria-hidden="true"
      />

      <div class="relative mx-auto max-w-5xl">
        <div class="mx-auto max-w-3xl text-center">
          <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-400">
            El portal web del taller
          </p>
          <h2 class="mt-3 text-3xl font-bold tracking-tight text-white sm:text-4xl">
            De la falla reportada a la entrega del vehículo
          </h2>
          <p class="mt-4 text-base leading-relaxed text-white/70 sm:text-lg">
            AUTOFIX IA centraliza la operación del taller: recepción del vehículo, diagnóstico asistido,
            reparación, inventario, cobro y seguimiento para el cliente — sin cuadernos ni información dispersa.
          </p>
        </div>

        <div class="mt-16 grid gap-10 md:grid-cols-3 md:gap-8">
          <article class="group border-t border-emerald-400/40 pt-6 transition duration-300 hover:border-emerald-300">
            <div class="mb-4 flex size-12 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-400 ring-1 ring-emerald-400/25 transition group-hover:bg-emerald-500/25">
              <UIcon name="i-lucide-brain" class="size-6" />
            </div>
            <h3 class="text-xl font-semibold text-white">Diagnóstico IA</h3>
            <p class="mt-3 text-sm leading-relaxed text-white/70">
              Describe la falla y el sistema genera una sugerencia inicial: posibles causas, prioridad,
              acciones recomendadas y especialista sugerido. La IA orienta; el mecánico confirma el diagnóstico final.
            </p>
            <ul class="mt-4 space-y-2 text-sm text-white/55">
              <li class="flex gap-2"><span class="text-emerald-400">→</span> Causas ordenadas por probabilidad</li>
              <li class="flex gap-2"><span class="text-emerald-400">→</span> Modo real (Groq) o simulado</li>
              <li class="flex gap-2"><span class="text-emerald-400">→</span> Revisión: confirmar, modificar o descartar</li>
            </ul>
          </article>

          <article class="group border-t border-emerald-400/40 pt-6 transition duration-300 hover:border-emerald-300">
            <div class="mb-4 flex size-12 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-400 ring-1 ring-emerald-400/25 transition group-hover:bg-emerald-500/25">
              <UIcon name="i-lucide-clipboard-list" class="size-6" />
            </div>
            <h3 class="text-xl font-semibold text-white">Órdenes y facturas</h3>
            <p class="mt-3 text-sm leading-relaxed text-white/70">
              Cada ingreso queda documentado: cliente, vehículo, kilometraje, mecánico asignado, servicios,
              repuestos con control de stock, bitácora de avances y cobro con factura y pago.
            </p>
            <ul class="mt-4 space-y-2 text-sm text-white/55">
              <li class="flex gap-2"><span class="text-emerald-400">→</span> Estados claros hasta la entrega</li>
              <li class="flex gap-2"><span class="text-emerald-400">→</span> Inventario actualizado al usar piezas</li>
              <li class="flex gap-2"><span class="text-emerald-400">→</span> Flujo orden → factura → pago</li>
            </ul>
          </article>

          <article class="group border-t border-emerald-400/40 pt-6 transition duration-300 hover:border-emerald-300">
            <div class="mb-4 flex size-12 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-400 ring-1 ring-emerald-400/25 transition group-hover:bg-emerald-500/25">
              <UIcon name="i-lucide-car" class="size-6" />
            </div>
            <h3 class="text-xl font-semibold text-white">Portal del cliente</h3>
            <p class="mt-3 text-sm leading-relaxed text-white/70">
              El dueño del vehículo consulta sin llamar al taller: sus autos, el estado general de la orden
              (recibido, en diagnóstico, en reparación, listo) y el historial de servicios autorizados.
            </p>
            <ul class="mt-4 space-y-2 text-sm text-white/55">
              <li class="flex gap-2"><span class="text-emerald-400">→</span> Mis vehículos y mis órdenes</li>
              <li class="flex gap-2"><span class="text-emerald-400">→</span> Historial de mantenimientos</li>
              <li class="flex gap-2"><span class="text-emerald-400">→</span> Actualización de datos básicos</li>
            </ul>
          </article>
        </div>

        <div class="mt-16 flex flex-col items-center justify-between gap-6 border-t border-white/10 pt-10 sm:flex-row">
          <Link
            v-if="!authUser"
            :href="route('login')"
            class="inline-flex h-11 shrink-0 items-center justify-center rounded-md bg-emerald-600 px-6 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-emerald-500"
          >
            Entrar al sistema
          </Link>
          <Link
            v-else
            :href="route('dashboard')"
            class="inline-flex h-11 shrink-0 items-center justify-center rounded-md bg-emerald-600 px-6 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-emerald-500"
          >
            Ir al panel
          </Link>
        </div>
      </div>
    </section>
  </div>
</template>
