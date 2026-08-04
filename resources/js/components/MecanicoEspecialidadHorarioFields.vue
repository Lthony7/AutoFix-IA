<script setup lang="ts">
import { computed, reactive, watch, onMounted } from 'vue'
import {
  DIAS_SEMANA,
  ESPECIALIDADES_MECANICO,
  decodeEspecialidades,
  decodeHorarioSemanal,
  encodeEspecialidades,
  encodeHorarioSemanal,
  type DiaHorario
} from '../composables/useMecanicoPerfilTaller'

const props = defineProps<{
  especialidad: string
  horarioDisponible: string
  errorEspecialidad?: string
  errorHorario?: string
}>()

const emit = defineEmits<{
  'update:especialidad': [value: string]
  'update:horarioDisponible': [value: string]
}>()

const seleccionadas = reactive<string[]>(decodeEspecialidades(props.especialidad))
const dias = reactive<DiaHorario[]>(decodeHorarioSemanal(props.horarioDisponible))

const syncOut = () => {
  emit('update:especialidad', encodeEspecialidades([...seleccionadas]))
  emit('update:horarioDisponible', encodeHorarioSemanal(dias) || '')
}

watch(
  () => props.especialidad,
  (esp) => {
    if (encodeEspecialidades([...seleccionadas]) === String(esp || '').trim()) return
    const next = decodeEspecialidades(esp)
    seleccionadas.splice(0, seleccionadas.length, ...next)
  }
)

watch(
  () => props.horarioDisponible,
  (hor) => {
    if (encodeHorarioSemanal(dias) === String(hor || '').trim()) return
    const next = decodeHorarioSemanal(hor)
    next.forEach((d, i) => Object.assign(dias[i], d))
  }
)

watch(seleccionadas, syncOut, { deep: true })
watch(dias, syncOut, { deep: true })

onMounted(() => {
  syncOut()
})

const toggleEspecialidad = (opt: string, checked: boolean | 'indeterminate') => {
  const on = checked === true
  const idx = seleccionadas.indexOf(opt)
  if (on && idx >= 0) return
  if (on) seleccionadas.push(opt)
  else if (idx >= 0) seleccionadas.splice(idx, 1)
}

const extras = computed(() =>
  seleccionadas.filter(s => !(ESPECIALIDADES_MECANICO as readonly string[]).includes(s))
)

const previewHorario = computed(() => encodeHorarioSemanal(dias) || 'Sin horario definido (opcional)')
const totalSeleccionadas = computed(() => seleccionadas.length)
const totalDiasActivos = computed(() => dias.filter(d => d.activo).length)

const activarLunVie = () => {
  for (const d of dias) {
    const esLaboral = ['lun', 'mar', 'mie', 'jue', 'vie'].includes(d.key)
    d.activo = esLaboral
    if (esLaboral) {
      d.desde = '08:00'
      d.hasta = '17:00'
    }
  }
}

const limpiarHorario = () => {
  for (const d of dias) {
    d.activo = false
    d.desde = '08:00'
    d.hasta = '17:00'
  }
}
</script>

<template>
  <div class="flex w-full min-w-0 flex-col gap-4 md:col-span-2 xl:col-span-3">
    <section class="autofix-module-panel w-full">
      <header class="autofix-module-panel__header">
        <div class="min-w-0">
          <h3 class="autofix-module-panel__title">Especialidades</h3>
          <p class="mt-0.5 text-xs text-muted">
            Elige una o varias según el perfil del mecánico (mercado automotriz).
          </p>
        </div>
        <div class="autofix-module-panel__actions">
          <span class="autofix-badge-solid autofix-badge-solid--ok">
            {{ totalSeleccionadas }} seleccionada{{ totalSeleccionadas === 1 ? '' : 's' }}
          </span>
        </div>
      </header>
      <div class="autofix-module-panel__body space-y-3">
        <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 xl:grid-cols-3">
          <label
            v-for="opt in ESPECIALIDADES_MECANICO"
            :key="opt"
            class="flex cursor-pointer items-start gap-2.5 rounded-lg border px-3 py-2.5 transition-colors"
            :class="seleccionadas.includes(opt)
              ? 'border-primary/50 bg-primary/5'
              : 'border-default/70 hover:bg-elevated/40'"
          >
            <UCheckbox
              :model-value="seleccionadas.includes(opt)"
              class="mt-0.5"
              @update:model-value="(v) => toggleEspecialidad(opt, v)"
            />
            <span class="text-sm leading-snug">{{ opt }}</span>
          </label>
        </div>

        <div v-if="extras.length" class="flex flex-wrap gap-1.5">
          <span
            v-for="extra in extras"
            :key="extra"
            class="autofix-badge-solid autofix-badge-solid--neutral"
          >
            {{ extra }}
          </span>
        </div>

        <p v-if="errorEspecialidad" class="text-sm text-error">{{ errorEspecialidad }}</p>
      </div>
    </section>

    <section class="autofix-module-panel w-full">
      <header class="autofix-module-panel__header">
        <div class="min-w-0">
          <h3 class="autofix-module-panel__title">Disponibilidad semanal</h3>
          <p class="mt-0.5 text-xs text-muted">
            Opcional. Activa los días que trabaja y define hora de inicio / fin.
          </p>
        </div>
        <div class="autofix-module-panel__actions">
          <UButton size="xs" variant="soft" label="Lun–Vie 8–17" @click="activarLunVie" />
          <UButton size="xs" variant="ghost" color="neutral" label="Limpiar" @click="limpiarHorario" />
          <span class="autofix-badge-solid autofix-badge-solid--neutral">
            {{ totalDiasActivos }} día{{ totalDiasActivos === 1 ? '' : 's' }}
          </span>
        </div>
      </header>
      <div class="autofix-module-panel__body space-y-2">
        <div
          v-for="(dia, idx) in dias"
          :key="dia.key"
          class="rounded-lg border px-3 py-2.5"
          :class="dia.activo ? 'border-primary/40 bg-primary/5' : 'border-default/70 opacity-85'"
        >
          <div class="flex flex-col gap-2.5 sm:flex-row sm:items-center sm:gap-3">
            <div class="shrink-0 sm:w-36">
              <UCheckbox v-model="dias[idx].activo" :label="DIAS_SEMANA[idx].label" />
            </div>
            <div class="grid min-w-0 flex-1 grid-cols-2 gap-2">
              <UInput
                v-model="dias[idx].desde"
                type="time"
                icon="i-lucide-clock"
                class="w-full"
                :disabled="!dia.activo"
              />
              <UInput
                v-model="dias[idx].hasta"
                type="time"
                icon="i-lucide-clock"
                class="w-full"
                :disabled="!dia.activo"
              />
            </div>
          </div>
        </div>

        <p class="text-xs text-muted">
          Resumen:
          <span class="font-medium text-highlighted">{{ previewHorario }}</span>
        </p>
        <p v-if="errorHorario" class="text-sm text-error">{{ errorHorario }}</p>
      </div>
    </section>
  </div>
</template>
