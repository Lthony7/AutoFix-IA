<script setup lang="ts">
export interface MecanicoFicha {
  id: string
  nombreCompleto: string
  documento: string
  especialidad: string
  telefono?: string | null
  email?: string | null
  horarioDisponible?: string | null
  activo: boolean
}

const open = defineModel<boolean>('open', { default: false })

defineProps<{
  mecanico?: MecanicoFicha | null
}>()
</script>

<template>
  <USlideover
    v-model:open="open"
    title="Ficha del mecánico"
    description="Datos del especialista seleccionado"
    side="right"
  >
    <template #body>
      <div v-if="mecanico" class="space-y-5">
        <div>
          <p class="text-xs uppercase tracking-wide text-muted">Nombre</p>
          <p class="mt-1 text-lg font-semibold text-highlighted">{{ mecanico.nombreCompleto }}</p>
        </div>

        <div>
          <p class="text-xs uppercase tracking-wide text-muted">Especialidad</p>
          <UBadge class="mt-2" color="primary" variant="subtle" size="lg">
            {{ mecanico.especialidad }}
          </UBadge>
        </div>

        <div class="grid grid-cols-1 gap-4">
          <div>
            <p class="text-xs uppercase tracking-wide text-muted">Documento</p>
            <p class="mt-1 text-sm">{{ mecanico.documento }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-muted">Horario disponible</p>
            <p class="mt-1 text-sm">{{ mecanico.horarioDisponible || '—' }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-muted">Teléfono</p>
            <p class="mt-1 text-sm">{{ mecanico.telefono || '—' }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-muted">Correo</p>
            <p class="mt-1 text-sm break-all">{{ mecanico.email || '—' }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-muted">Estado</p>
            <UBadge
              class="mt-2"
              :color="mecanico.activo ? 'success' : 'neutral'"
              variant="subtle"
            >
              {{ mecanico.activo ? 'Activo' : 'Inactivo' }}
            </UBadge>
          </div>
        </div>
      </div>
      <p v-else class="text-sm text-muted">Selecciona un mecánico para ver su ficha.</p>
    </template>
  </USlideover>
</template>
