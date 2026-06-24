import { create } from 'zustand'

export type StaffRole = 'super_admin' | 'enterprise_admin' | 'employee'

export interface StaffUser {
  id: number
  first_name: string
  last_name: string
  email: string
  role: StaffRole
  tenant_id: number | null
  branch_id: number | null
  language_preference: 'fr' | 'en'
  enterprise?: { id: number; name: string; logo: string | null } | null
}

interface AuthState {
  token: string | null
  user: StaffUser | null
  setAuth: (token: string, user: StaffUser) => void
  clearAuth: () => void
}

const storedToken = localStorage.getItem('sigfa_token')
const storedUser  = localStorage.getItem('sigfa_user')

export const useAuthStore = create<AuthState>((set) => ({
  token: storedToken,
  user: storedUser ? JSON.parse(storedUser) : null,

  setAuth: (token, user) => {
    localStorage.setItem('sigfa_token', token)
    localStorage.setItem('sigfa_user', JSON.stringify(user))
    set({ token, user })
  },

  clearAuth: () => {
    localStorage.removeItem('sigfa_token')
    localStorage.removeItem('sigfa_user')
    set({ token: null, user: null })
  },
}))
