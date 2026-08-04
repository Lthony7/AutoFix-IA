/** Especialidades del mercado laboral automotriz (taller / mecánico). */
export const ESPECIALIDADES_MECANICO = [
  'Mantenimiento general y lubricación',
  'Motor (reparación y sincronización)',
  'Inyección electrónica y sensores',
  'Sistema eléctrico y baterías',
  'Diagnóstico computarizado (scanner OBD)',
  'Frenos (discos, pastillas, ABS)',
  'Suspensión y dirección',
  'Transmisión manual y automática',
  'Embrague y caja de cambios',
  'Aire acondicionado y clima',
  'Alineación, balanceo y llantas',
  'Escape, catalizador y emisiones',
  'Enfriamiento y radiador',
  'Dirección hidráulica / eléctrica',
  'Carrocería y chapa liviana',
  'Soldadura y estructuras',
  'Pintura automotriz',
  'Sistemas híbridos / EV (básico)'
] as const

export type EspecialidadMecanico = (typeof ESPECIALIDADES_MECANICO)[number]

export const DIAS_SEMANA = [
  { key: 'lun', label: 'Lunes', short: 'Lun' },
  { key: 'mar', label: 'Martes', short: 'Mar' },
  { key: 'mie', label: 'Miércoles', short: 'Mié' },
  { key: 'jue', label: 'Jueves', short: 'Jue' },
  { key: 'vie', label: 'Viernes', short: 'Vie' },
  { key: 'sab', label: 'Sábado', short: 'Sáb' },
  { key: 'dom', label: 'Domingo', short: 'Dom' }
] as const

export type DiaKey = (typeof DIAS_SEMANA)[number]['key']

export interface DiaHorario {
  key: DiaKey
  activo: boolean
  desde: string
  hasta: string
}

const SHORT_TO_KEY: Record<string, DiaKey> = {
  lun: 'lun',
  mar: 'mar',
  mie: 'mie',
  mié: 'mie',
  jue: 'jue',
  vie: 'vie',
  sab: 'sab',
  sáb: 'sab',
  dom: 'dom'
}

const RANGE_ORDER: DiaKey[] = ['lun', 'mar', 'mie', 'jue', 'vie', 'sab', 'dom']

const normKey = (value: string): string =>
  value.toLowerCase().normalize('NFD').replace(/\p{M}/gu, '')

export function diasHorarioDefault(preseleccionarLunVie = false): DiaHorario[] {
  return DIAS_SEMANA.map((d) => ({
    key: d.key,
    activo: preseleccionarLunVie ? ['lun', 'mar', 'mie', 'jue', 'vie'].includes(d.key) : false,
    desde: '08:00',
    hasta: '17:00'
  }))
}

export function encodeEspecialidades(seleccionadas: string[]): string {
  return seleccionadas
    .map(s => s.trim())
    .filter(Boolean)
    .join(', ')
}

export function decodeEspecialidades(raw: string | null | undefined): string[] {
  if (!raw?.trim()) return []
  const parts = raw.split(/[,;|/]+/).map(p => p.trim()).filter(Boolean)
  const matched = new Set<string>()

  for (const part of parts) {
    const lower = part.toLowerCase()
    const catalog = ESPECIALIDADES_MECANICO.find((opt) => {
      const o = opt.toLowerCase()
      return lower === o || lower.includes(o) || o.includes(lower)
    })
    if (catalog) matched.add(catalog)
    else matched.add(part)
  }

  return [...matched]
}

export function encodeHorarioSemanal(dias: DiaHorario[]): string {
  return dias
    .filter(d => d.activo)
    .map((d) => {
      const meta = DIAS_SEMANA.find(x => x.key === d.key)!
      return `${meta.short} ${normalizeHora(d.desde)}-${normalizeHora(d.hasta)}`
    })
    .join(', ')
}

export function decodeHorarioSemanal(raw: string | null | undefined): DiaHorario[] {
  const base = diasHorarioDefault(false)
  if (!raw?.trim()) return base

  const text = raw.trim()

  const detalle = [...text.matchAll(/\b(Lun|Mar|Mié|Mie|Jue|Vie|Sáb|Sab|Dom)\s+(\d{1,2}:\d{2})\s*[-–]\s*(\d{1,2}:\d{2})/gi)]
  if (detalle.length) {
    for (const m of detalle) {
      const key = SHORT_TO_KEY[normKey(m[1])] ?? SHORT_TO_KEY[m[1].toLowerCase()]
      if (!key) continue
      const row = base.find(d => d.key === key)
      if (!row) continue
      row.activo = true
      row.desde = normalizeHora(m[2])
      row.hasta = normalizeHora(m[3])
    }
    return base
  }

  const compact = text.match(/\b(Lun|Mar|Mié|Mie|Jue|Vie|Sáb|Sab|Dom)\s*[-–]\s*(Lun|Mar|Mié|Mie|Jue|Vie|Sáb|Sab|Dom)\s+(\d{1,2}:\d{2})\s*[-–]\s*(\d{1,2}:\d{2})/i)
  if (compact) {
    const from = SHORT_TO_KEY[normKey(compact[1])] ?? SHORT_TO_KEY[compact[1].toLowerCase()]
    const to = SHORT_TO_KEY[normKey(compact[2])] ?? SHORT_TO_KEY[compact[2].toLowerCase()]
    const desde = normalizeHora(compact[3])
    const hasta = normalizeHora(compact[4])
    if (from && to) {
      const i0 = RANGE_ORDER.indexOf(from)
      const i1 = RANGE_ORDER.indexOf(to)
      if (i0 >= 0 && i1 >= 0) {
        for (let i = Math.min(i0, i1); i <= Math.max(i0, i1); i++) {
          const row = base.find(d => d.key === RANGE_ORDER[i])!
          row.activo = true
          row.desde = desde
          row.hasta = hasta
        }
      }
    }
  }

  return base
}

export function normalizeHora(value: string): string {
  const m = String(value || '').trim().match(/^(\d{1,2}):(\d{2})$/)
  if (!m) return '08:00'
  const h = Math.min(23, Math.max(0, Number(m[1])))
  const min = Math.min(59, Math.max(0, Number(m[2])))
  return `${String(h).padStart(2, '0')}:${String(min).padStart(2, '0')}`
}

/**
 * Especialidad: al menos una.
 * Horario: opcional; si hay días activos, validar franjas.
 */
export function validarEspecialidadesHorario(
  especialidades: string[],
  dias: DiaHorario[]
): { especialidad?: string, horarioDisponible?: string } {
  const errors: { especialidad?: string, horarioDisponible?: string } = {}
  if (!especialidades.length) {
    errors.especialidad = 'Selecciona al menos una especialidad'
  }

  const activos = dias.filter(d => d.activo)
  for (const d of activos) {
    const desde = normalizeHora(d.desde)
    const hasta = normalizeHora(d.hasta)
    if (desde >= hasta) {
      const label = DIAS_SEMANA.find(x => x.key === d.key)?.label || d.key
      errors.horarioDisponible = `En ${label}, la hora de inicio debe ser menor que la de fin`
      break
    }
  }

  return errors
}
