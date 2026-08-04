<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormField from '../../components/FormField.vue'
import {
  mergeErrors,
  soloDigitos,
  validarDocumento,
  validarEmail,
  validarNombre,
  validarTelefono,
  type FormErrors
} from '../../composables/useFormValidation'

interface Perfil {
  id: string
  nombres: string
  apellidos: string
  telefono: string
  email: string
  direccion: string
  numeroDocumento: string
  tipoDocumento: string
}

const page = usePage()
const cliente = (page.props as any).cliente as Perfil | null
const localErrors = ref<FormErrors>({})
const backendErrors = computed(() => page.props.errors || {})
const errors = computed(() => mergeErrors(localErrors.value, backendErrors.value as Record<string, unknown>))

const isLoading = ref(false)
const esDocumentoTemporal = computed(() =>
  String(cliente?.numeroDocumento || '').toUpperCase().startsWith('TMP')
)

const state = reactive({
  tipoDocumento: cliente?.tipoDocumento || 'CEDULA',
  numeroDocumento: esDocumentoTemporal.value ? '' : (cliente?.numeroDocumento || ''),
  nombres: cliente?.nombres || '',
  apellidos: cliente?.apellidos || '',
  telefono: cliente?.telefono === '0000000000' ? '' : (cliente?.telefono || ''),
  email: cliente?.email || '',
  direccion: cliente?.direccion === 'Por completar' ? '' : (cliente?.direccion || '')
})

const tiposDocumento = [
  { label: 'Cédula / C.I.', value: 'CEDULA' },
  { label: 'DNI', value: 'DNI' },
  { label: 'RUC', value: 'RUC' },
  { label: 'Pasaporte', value: 'PASAPORTE' },
  { label: 'Carnet de extranjería', value: 'CE' }
]

const validarApellidos = (value: string): string | null => {
  const base = validarNombre(value, 'Los apellidos', true)
  if (base) return base
  const parts = value.trim().split(/\s+/).filter(Boolean)
  if (parts.length < 2) return 'Ingresa tus dos apellidos (paterno y materno)'
  return null
}

const validate = (): boolean => {
  const next: FormErrors = {}
  if (!state.tipoDocumento) next.tipoDocumento = 'Selecciona el tipo de documento'
  const docErr = validarDocumento(state.numeroDocumento, state.tipoDocumento, true)
  if (docErr) next.numeroDocumento = docErr
  const nombresErr = validarNombre(state.nombres, 'El nombre', true)
  if (nombresErr) next.nombres = nombresErr
  const apellidosErr = validarApellidos(state.apellidos)
  if (apellidosErr) next.apellidos = apellidosErr
  const telErr = validarTelefono(state.telefono, true)
  if (telErr) next.telefono = telErr
  const emailErr = validarEmail(state.email, true)
  if (emailErr) next.email = emailErr
  if (!state.direccion?.trim() || state.direccion.trim().length < 5) {
    next.direccion = 'La dirección debe tener al menos 5 caracteres'
  }
  localErrors.value = next
  return Object.keys(next).length === 0
}

const handleSubmit = () => {
  if (!cliente || !validate()) return
  isLoading.value = true
  const payload = {
    tipoDocumento: state.tipoDocumento,
    numeroDocumento: ['CEDULA', 'DNI', 'RUC'].includes(state.tipoDocumento)
      ? soloDigitos(state.numeroDocumento)
      : state.numeroDocumento.trim(),
    nombres: state.nombres.trim(),
    apellidos: state.apellidos.trim(),
    telefono: soloDigitos(state.telefono),
    email: state.email.trim().toLowerCase(),
    direccion: state.direccion.trim()
  }
  router.put(route('portal.mis-datos.update'), payload, {
    onFinish: () => { isLoading.value = false }
  })
}
</script>

<template>
  <AppDashboardPanel id="portal-perfil">
    <template #header>
      <UDashboardNavbar title="Mis datos">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <UCard v-if="cliente" class="w-full">
        <UAlert
          v-if="esDocumentoTemporal"
          class="mb-4"
          color="warning"
          variant="subtle"
          title="Completa tu identificación"
          description="Estos datos se usarán en presupuestos y facturas. Ingresa tu documento real (cédula, RUC u otro)."
        />
        <form class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 w-full" @submit.prevent="handleSubmit">
          <UAlert
            v-if="Object.keys(errors).length"
            class="md:col-span-2 xl:col-span-3"
            color="error"
            variant="subtle"
            title="Revisa los datos"
            :description="String(Object.values(errors)[0] || '')"
          />

          <FormField label="Tipo de documento" name="tipoDocumento" required :error="errors.tipoDocumento || errors.tipo_documento">
            <USelect v-model="state.tipoDocumento" :items="tiposDocumento" class="w-full" />
          </FormField>
          <FormField
            label="Número de documento"
            name="numeroDocumento"
            required
            :error="errors.numeroDocumento || errors.numero_documento"
            hint="Cédula, DNI, RUC, pasaporte o CE"
          >
            <UInput v-model="state.numeroDocumento" class="w-full" placeholder="Ej: 0912345678" />
          </FormField>

          <FormField label="Nombres" name="nombres" required :error="errors.nombres" hint="Al menos un nombre">
            <UInput v-model="state.nombres" class="w-full" placeholder="Luis Alberto" />
          </FormField>
          <FormField label="Apellidos" name="apellidos" required :error="errors.apellidos" hint="Dos apellidos">
            <UInput v-model="state.apellidos" class="w-full" placeholder="Reyes Aguirre" />
          </FormField>
          <FormField label="Teléfono" name="telefono" required :error="errors.telefono" hint="10 dígitos">
            <UInput v-model="state.telefono" class="w-full" maxlength="10" placeholder="0987654321" />
          </FormField>
          <FormField label="Correo" name="email" required :error="errors.email">
            <UInput v-model="state.email" type="email" class="w-full" />
          </FormField>
          <FormField label="Dirección" name="direccion" required :error="errors.direccion" class="md:col-span-2 xl:col-span-3">
            <UInput v-model="state.direccion" class="w-full" placeholder="Calle, número, ciudad" />
          </FormField>

          <div class="md:col-span-2 xl:col-span-3">
            <UButton type="submit" label="Guardar cambios" :loading="isLoading" />
          </div>
        </form>
      </UCard>

      <UAlert
        v-else
        color="warning"
        variant="subtle"
        title="Sin ficha de cliente"
        description="Tu usuario no está vinculado a un cliente del taller. Contacta a recepción."
      />
    </template>
  </AppDashboardPanel>
</template>
