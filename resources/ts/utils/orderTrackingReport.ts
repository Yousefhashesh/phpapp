export type OrderTrackingLog = {
  id: number
  action?: string
  label?: string
  old_values?: Record<string, unknown> | null
  new_values?: Record<string, unknown> | null
  meta?: Record<string, unknown> | null
  created_at?: string
  user?: { id?: number; name?: string; username?: string; phone?: string } | null
  login_session?: {
    ip_address?: string
    country?: string
    city?: string
    device_name?: string
  } | null
  loginSession?: {
    ip_address?: string
    country?: string
    city?: string
    device_name?: string
  } | null
  ip_address?: string | null
}

const STATUS_LABELS_AR: Record<string, string> = {
  OUT_FOR_DELIVERY: 'جاري التوصيل',
  DELIVERED: 'تم التسليم',
  HOLD: 'معلق',
  UNDELIVERED: 'لم يُسلّم',
}

const FIELD_LABELS_AR: Record<string, string> = {
  status: 'الحالة',
  is_shipper_collected: 'تحصيل المندوب',
  is_shipper_returned: 'مرتجع المندوب',
  is_client_settled: 'تسوية العميل',
  is_client_returned: 'مرتجع العميل',
  shipper_user_id: 'المندوب',
  total_amount: 'المبلغ الإجمالي',
  cod_amount: 'مبلغ التحصيل',
  shipping_fee: 'مصاريف الشحن',
  commission_amount: 'عمولة المندوب',
  company_amount: 'صافي الشركة',
  receiver_name: 'اسم المستلم',
  phone: 'الهاتف',
  phone_2: 'هاتف إضافي',
  address: 'العنوان',
  latest_status_note: 'ملاحظة الحالة',
  order_note: 'ملاحظة الطلب',
  approval_status: 'حالة الموافقة',
  external_code: 'كود خارجي',
}

export function formatTrackingDateTime(value: string | null | undefined): string {
  if (!value)
    return '—'

  return new Date(value).toLocaleString('ar-EG', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
}

export function formatStatusAr(status: string | null | undefined): string {
  if (!status)
    return '—'

  return STATUS_LABELS_AR[status] ?? status.replace(/_/g, ' ')
}

function formatFieldValue(key: string, value: unknown): string {
  if (value === null || value === undefined || value === '')
    return '—'

  if (typeof value === 'boolean')
    return value ? 'نعم' : 'لا'

  if (key === 'status' && typeof value === 'string')
    return formatStatusAr(value)

  if (typeof value === 'number')
    return Number.isInteger(value) ? String(value) : value.toFixed(2)

  return String(value)
}

export function getTrackingActionLabel(log: OrderTrackingLog): string {
  if (log.action === 'created')
    return 'إنشاء الطلب'
  if (log.action === 'deleted')
    return 'حذف الطلب'
  if (log.action === 'status_changed')
    return 'تغيير الحالة'
  if (log.new_values?.status || log.old_values?.status)
    return 'تغيير الحالة'
  if (log.new_values?.is_shipper_collected !== undefined)
    return 'تحصيل المندوب'
  if (log.new_values?.is_shipper_returned !== undefined)
    return 'مرتجع المندوب'
  if (log.new_values?.is_client_settled !== undefined)
    return 'تسوية العميل'
  if (log.new_values?.is_client_returned !== undefined)
    return 'مرتجع العميل'
  if (log.new_values?.shipper_user_id !== undefined)
    return 'تعيين مندوب'
  if (log.label)
    return log.label

  return log.action === 'updated' ? 'تحديث بيانات' : (log.action || 'حدث')
}

export function getTrackingLogMessage(log: OrderTrackingLog): string {
  if (log.action === 'created')
    return 'تم تسجيل الطلب في النظام'
  if (log.action === 'deleted')
    return 'تم حذف الطلب'
  if (log.new_values?.status)
    return `تم تغيير الحالة إلى: ${formatStatusAr(String(log.new_values.status))}`
  if (log.new_values?.is_shipper_collected === true)
    return 'تم تأكيد تحصيل المبلغ من المندوب'
  if (log.new_values?.is_shipper_returned === true)
    return 'تم تنفيذ مرتجع المندوب'
  if (log.new_values?.is_client_settled === true)
    return 'تمت تسوية المستحقات مع العميل'
  if (log.new_values?.is_client_returned === true)
    return 'تم تسليم المرتجع للعميل'
  if (log.new_values?.shipper_user_id)
    return 'تم تعيين أو تغيير المندوب المسؤول عن التوصيل'

  const changes = formatLogFieldChanges(log)
  if (changes.length)
    return changes.join(' · ')

  if (log.label)
    return log.label

  return 'تم تحديث بيانات الطلب'
}

export function formatLogFieldChanges(log: OrderTrackingLog): string[] {
  const newValues = log.new_values ?? {}
  const oldValues = log.old_values ?? {}
  const keys = [...new Set([...Object.keys(newValues), ...Object.keys(oldValues)])]
    .filter(key => !['updated_at', 'created_at'].includes(key))

  return keys.map((key) => {
    const label = FIELD_LABELS_AR[key] ?? key
    const oldVal = formatFieldValue(key, oldValues[key])
    const newVal = formatFieldValue(key, newValues[key])

    if (oldVal === '—' && newVal !== '—')
      return `${label}: ${newVal}`

    if (oldVal !== newVal)
      return `${label}: ${oldVal} ← ${newVal}`

    return `${label}: ${newVal}`
  })
}

export function getTrackingPerformer(log: OrderTrackingLog): string {
  return log.user?.name || log.user?.username || 'النظام'
}

export function getTrackingDeviceInfo(log: OrderTrackingLog): string {
  const session = log.login_session ?? log.loginSession
  const parts = [
    session?.device_name,
    session?.city,
    session?.country,
    session?.ip_address || log.ip_address,
  ].filter(Boolean)

  return parts.length ? parts.join(' — ') : '—'
}

export function normalizeHistoryResponse(payload: unknown): OrderTrackingLog[] {
  if (!payload)
    return []

  if (Array.isArray(payload))
    return payload as OrderTrackingLog[]

  const paginated = payload as { data?: OrderTrackingLog[] }
  if (Array.isArray(paginated.data))
    return paginated.data

  return []
}

export function sortLogsChronologically(logs: OrderTrackingLog[]): OrderTrackingLog[] {
  return [...logs].sort((a, b) => {
    const ta = a.created_at ? new Date(a.created_at).getTime() : 0
    const tb = b.created_at ? new Date(b.created_at).getTime() : 0

    return ta - tb
  })
}
