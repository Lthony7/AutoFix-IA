/**
 * Validación de formularios en el cliente (mensajes claros antes del submit).
 */
export type FormErrors = Record<string, string>

const NOMBRE_REGEX = /^[\p{L}]+(?:[ '\-][\p{L}]+)*$/u
const TELEFONO_REGEX = /^[0-9]{10}$/
const PLACA_REGEX = /^[A-Za-z0-9]{3}-?[A-Za-z0-9]{2,4}$/
const DOC_DIGITS = /^[0-9]{6,10}$/
const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

export function soloDigitos(value: string | number | null | undefined): string {
  return String(value ?? '').replace(/\D+/g, '')
}

export function validarNombre(value: string, label = 'El nombre', required = true): string | null {
  const v = value?.trim() ?? ''
  if (!v) return required ? `${label} es obligatorio` : null
  if (v.length < 2) return `${label} debe tener al menos 2 caracteres`
  if (!NOMBRE_REGEX.test(v)) return `${label} solo puede contener letras y espacios`
  return null
}

export function validarTelefono(value: string, required = true): string | null {
  const v = soloDigitos(value)
  if (!v) return required ? 'El teléfono es obligatorio' : null
  if (!TELEFONO_REGEX.test(v)) return 'El teléfono debe tener exactamente 10 dígitos numéricos'
  return null
}

export function validarEmail(value: string, required = true): string | null {
  const v = value?.trim() ?? ''
  if (!v) return required ? 'El correo es obligatorio' : null
  if (!EMAIL_REGEX.test(v)) return 'El correo electrónico no es válido'
  return null
}

export function validarDocumento(
  value: string,
  tipo: string = 'CEDULA',
  required = true
): string | null {
  const raw = String(value ?? '').trim()
  if (!raw) return required ? 'El documento es obligatorio' : null

  const t = (tipo || 'CEDULA').toUpperCase()

  if (t === 'CEDULA' || t === 'DNI') {
    const digits = soloDigitos(raw)
    if (!DOC_DIGITS.test(digits)) return 'La cédula/DNI debe tener entre 6 y 10 dígitos'
    return null
  }

  if (t === 'RUC') {
    const digits = soloDigitos(raw)
    if (!/^[0-9]{11,13}$/.test(digits)) return 'El RUC debe tener entre 11 y 13 dígitos'
    return null
  }

  if (t === 'CE' || t === 'PASAPORTE') {
    if (!/^[A-Za-z0-9]{6,12}$/.test(raw)) {
      return t === 'CE'
        ? 'El carnet de extranjería debe tener entre 6 y 12 caracteres alfanuméricos'
        : 'El pasaporte debe tener entre 6 y 12 caracteres alfanuméricos'
    }
    return null
  }

  const digits = soloDigitos(raw)
  if (!/^[0-9]{6,15}$/.test(digits)) return 'El documento debe tener entre 6 y 15 dígitos'
  return null
}

export function validarPlaca(value: string, required = true): string | null {
  const v = String(value ?? '').trim().toUpperCase()
  if (!v) return required ? 'La placa es obligatoria' : null
  if (!PLACA_REGEX.test(v)) return 'La placa no es válida (ej: ABC123 o ABC-123)'
  return null
}

export function validarNumero(
  value: number | string | null | undefined,
  label: string,
  opts: { required?: boolean, min?: number, integer?: boolean } = {}
): string | null {
  const { required = true, min = 0, integer = false } = opts
  if (value === '' || value === null || value === undefined) {
    return required ? `${label} es obligatorio` : null
  }
  const n = Number(value)
  if (Number.isNaN(n)) return `${label} debe ser un número`
  if (integer && !Number.isInteger(n)) return `${label} debe ser un número entero`
  if (n < min) return `${label} no puede ser menor que ${min}`
  return null
}

/** Une errores locales + backend (soporta snake_case y camelCase). */
export function mergeErrors(local: FormErrors, backend: Record<string, unknown> = {}): FormErrors {
  const result: FormErrors = { ...local }

  Object.keys(backend).forEach((key) => {
    const err = backend[key]
    const message = Array.isArray(err) ? String(err[0]) : String(err ?? '')
    if (!message) return

    result[key] = message
    const camel = key.replace(/_([a-z])/g, (_, c: string) => c.toUpperCase())
    const snake = key.replace(/[A-Z]/g, (m) => `_${m.toLowerCase()}`)
    result[camel] = message
    result[snake] = message
  })

  return result
}

export function firstError(errors: FormErrors): string | null {
  const keys = Object.keys(errors)
  return keys.length ? errors[keys[0]] : null
}
