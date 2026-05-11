export interface UserInfo {
  id: number
  username: string
  nickname?: string
  email: string
  mobile?: string
  avatar?: string
  roles: string[]
  permissions: string[]
}
