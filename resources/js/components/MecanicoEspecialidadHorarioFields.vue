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
  emit('update:horarioDisponible', encodeHorarioSemanal(dias))
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
  if (on && idx < 0) seleccionadas.push(opt)
  if (!on && idx >= 0) seleccionadas.splice(idx, 1)
}

const extras = computed(() =>
  seleccionadas.filter(s => !(ESPECIALIDADES_MECANICO as readonly string[]).includes(s))
)

const previewHorario = computed(() => encodeHorarioSemanal(dias) || 'Sin franjas activas')
</script>

<template>
  <div class="space-y-5 w-full md:col-span-2 xl:col-span-3">
    <section class="space-y-3">
      <div>
        <h3 class="text-sm font-semibold text-highlighted">Especialidades</h3>
        <p class="text-xs text-muted mt-0.5">Selecciona al menos una.</p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
        <UCheckbox
          v-for="opt in ESPECIALIDADES_MECANICO"
          :key="opt"
          :model-value="seleccionadas.includes(opt)"
          :label="opt"
          @update:model-value="(v) => toggleEspecialidad(opt, v)"
        />
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
    </section>

    <section class="space-y-3">
      <div>
        <h3 class="text-sm font-semibold text-highlighted">Disponibilidad semanal</h3>
        <p class="text-xs text-muted mt-0.5">
          Las franjas omitidas quedan inactivas, no se eliminan.
        </p>
      </div>

      <div class="space-y-2">
        <div
          v-for="(dia, idx) in dias"
          :key="dia.key"
          class="rounded-lg border border-default/70 bg-elevated/30 px-3 py-2.5"
          :class="dia.activo ? 'border-primary/40' : 'opacity-80'"
        >
          <div class="flex flex-col gap-2.5 sm:flex-row sm:items-center sm:gap-3">
            <div class="sm:w-36 shrink-0">
              <UCheckbox
                v-model="dias[idx].activo"
                :label="DIAS_SEMANA[idx].label"
              />
            </div>

            <div class="grid grid-cols-2 gap-2 flex-1 min-w-0">
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
      </div>

      <p class="text-xs text-muted">
        Resumen: <span class="font-medium text-highlighted">{{ previewHorario }}</span>
      </p>
      <p v-if="errorHorario" class="text-sm text-error">{{ errorHorario }}</p>
    </section>
  </div>
</template>
