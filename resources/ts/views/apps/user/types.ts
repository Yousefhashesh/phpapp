export interface UserParams {
  q: string
  role: string
  plan: string
  status: string
  options: object
}

export interface UserProperties {
  id: number
  name: string
  username: string
  email?: string
  avatar?: string
  roles: { name: string; label?: string }[]
  status?: string
  is_blocked: boolean
  phone?: string
  address?: string
  account_type?: string
  commission_rate?: number
  plan_id?: number
  shipping_content_id?: number
  can_settle_before_shipper_collected?: boolean
}
