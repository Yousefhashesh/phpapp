export type ShippingLabelPayload = {
  order_id?: number
  code?: string
  external_code?: string | null
  label_code?: string
  status?: string | null
  client_name?: string
  client_phone?: string | null
  shipper_name?: string | null
  shipper_phone?: string | null
  receiver_name?: string
  receiver_phones_text?: string
  phone?: string | null
  phone_2?: string | null
  governorate_name?: string | null
  city_name?: string | null
  street_address?: string | null
  address?: string
  total_amount?: number | string
  cod?: number | string
  shipping_fee?: number | string
  commission_amount?: number | string
  company_amount?: number | string
  allow_open?: boolean
  shipping_content?: string | null
  order_note?: string | null
  latest_status_note?: string | null
  shipper_date?: string | null
  created_at?: string | null
}

const LABEL_STATUS_AR: Record<string, string> = {
  OUT_FOR_DELIVERY: 'جاري التوصيل',
  DELIVERED: 'تم التسليم',
  HOLD: 'معلق',
  UNDELIVERED: 'لم يُسلّم',
}

export function formatLabelStatusAr(status: string | null | undefined): string {
  if (!status)
    return '—'

  return LABEL_STATUS_AR[status] ?? status.replace(/_/g, ' ')
}

/** الكود المعروض على البوليصة والباركود (كود برة ثم كود النظام) */
export function resolveLabelTrackingCode(data: ShippingLabelPayload): string {
  const explicit = data.label_code?.trim()
  if (explicit)
    return explicit

  const external = data.external_code?.trim()
  if (external)
    return external

  return data.code?.trim() ?? ''
}

export function hasDistinctInternalCode(data: ShippingLabelPayload): boolean {
  const tracking = resolveLabelTrackingCode(data)
  const internal = data.code?.trim() ?? ''

  return internal !== '' && tracking !== '' && internal !== tracking
}

export function barcodeImageUrl(text: string, height = 56): string {
  const value = encodeURIComponent(text || '0')

  return `https://quickchart.io/barcode?type=code128&text=${value}&height=${height}&width=220&scale=2`
}

export function qrCodeImageUrl(text: string, size = 140): string {
  const value = encodeURIComponent(text || '0')

  return `https://quickchart.io/qr?text=${value}&size=${size}&margin=1`
}

export function formatLabelDate(value: string | null | undefined): string {
  if (!value)
    return '-'

  const date = new Date(value)

  return date.toLocaleString('en-GB', {
    day: '2-digit',
    month: '2-digit',
    year: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true,
  }).replace(',', '')
}

export function formatDistrictLine(data: ShippingLabelPayload): string {
  const governorate = data.governorate_name?.trim() ?? ''
  const city = data.city_name?.trim() ?? ''

  if (governorate && city)
    return `${city}-${governorate}`

  return city || governorate || '—'
}

export function resolveOrderContent(data: ShippingLabelPayload): string {
  return data.order_note?.trim()
    || data.shipping_content?.trim()
    || '—'
}

/** الإجمالي على البوليصة = total_amount */
export function resolveLabelTotalAmount(data: ShippingLabelPayload): string {
  return Number(data.total_amount ?? 0).toFixed(0)
}

export function formatLabelTimestamp(value: string | null | undefined): string {
  if (!value)
    return '-'

  const date = new Date(value)
  const time = date.toLocaleTimeString('en-GB', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false,
  })
  const day = date.toLocaleDateString('en-GB')

  return `${time} ${day}`
}

export function resolveFullAddressLine(data: ShippingLabelPayload): string {
  const parts = [
    data.governorate_name,
    data.city_name,
    data.street_address,
  ].filter(part => part !== null && part !== undefined && String(part).trim() !== '')

  if (parts.length)
    return parts.join(' - ')

  return data.address?.trim() || '—'
}

export function resolveFragileNote(data: ShippingLabelPayload): string {
  if (data.order_note?.trim())
    return data.order_note.trim()

  return 'قابل للكسر'
}

export function resolveSenderDisplay(data: ShippingLabelPayload): string {
  const client = data.client_name?.trim() ?? ''
  const content = data.shipping_content?.trim() ?? ''

  if (client && content)
    return `${client} / ${content}`

  return client || content || '—'
}

export function resolvePiecesCount(data: ShippingLabelPayload): string {
  const pieces = (data as { pieces_count?: number | string }).pieces_count

  if (pieces !== null && pieces !== undefined && String(pieces).trim() !== '')
    return String(pieces)

  return '1'
}

export function formatPrintMoney(value: number | string | null | undefined): string {
  return `${Number(value ?? 0).toFixed(2)} ج.م`
}

/** عرض مبلغ على الفاتورة (بدون كسور لو رقم صحيح) */
export function formatInvoiceAmount(value: number | string | null | undefined): string {
  const n = Number(value ?? 0)
  if (Number.isInteger(n))
    return `${n} ج.م`

  return `${n.toFixed(2)} ج.م`
}

export type InvoiceViewModel = {
  trackingCode: string
  clientName: string
  clientPhone: string
  receiverName: string
  receiverPhone: string
  receiverAddress: string
  contents: string
  priceWithoutShipping: number | string
  priceWithShipping: number | string
  allowOpen: boolean
}

export function buildInvoiceViewModel(source: Record<string, any>): InvoiceViewModel {
  const trackingCode = resolveLabelTrackingCode({
    code: source.code,
    external_code: source.external_code,
    label_code: source.label_code,
  })

  const addressParts = [
    source.governorate?.name ?? source.governorate_name,
    source.city?.name ?? source.city_name,
    source.address ?? source.street_address,
  ].filter(part => part !== null && part !== undefined && String(part).trim() !== '')

  const phones = [
    source.phone,
    source.phone_2,
  ].filter(p => p !== null && p !== undefined && String(p).trim() !== '')

  const contents = source.shipping_content?.name
    ?? source.shipping_content
    ?? source.order_note
    ?? '—'

  return {
    trackingCode,
    clientName: source.client?.name ?? source.client_name ?? '—',
    clientPhone: source.client?.phone ?? source.client_phone ?? '—',
    receiverName: source.receiver_name ?? '—',
    receiverPhone: phones.length ? phones.join(' / ') : (source.receiver_phones_text ?? '—'),
    receiverAddress: addressParts.length ? addressParts.join(' - ') : (source.address ?? '—'),
    contents: String(contents),
    priceWithoutShipping: source.cod_amount ?? source.cod ?? 0,
    priceWithShipping: source.total_amount ?? 0,
    allowOpen: Boolean(source.allow_open),
  }
}

export function orderRecordFromLabelPayload(data: ShippingLabelPayload): Record<string, any> {
  return {
    code: data.code,
    external_code: data.external_code,
    label_code: data.label_code,
    client_name: data.client_name,
    client_phone: data.client_phone,
    client: { name: data.client_name, phone: data.client_phone },
    receiver_name: data.receiver_name,
    phone: data.phone,
    phone_2: data.phone_2,
    governorate_name: data.governorate_name,
    city_name: data.city_name,
    address: data.street_address,
    total_amount: data.total_amount,
    cod_amount: data.cod,
    allow_open: data.allow_open,
    shipping_content: data.shipping_content,
    order_note: data.order_note,
    created_at: data.created_at,
  }
}

export function compactLabelSummary(data: ShippingLabelPayload): string {
  const name = data.receiver_name?.trim() ?? '—'
  const district = formatDistrictLine(data)
  const street = data.street_address?.trim() ?? ''

  return [name, district, street].filter(p => p && p !== '—').join(' - ')
}

export function formatPrintDate(value: string | null | undefined): string {
  if (!value)
    return '-'

  return new Date(value).toLocaleString('ar-EG', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}
