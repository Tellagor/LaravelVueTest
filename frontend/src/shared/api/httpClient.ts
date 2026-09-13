import axios from 'axios'
import { BACKEND_URL } from '@/shared/config/env'

export const httpClient = axios.create({
  baseURL: `${BACKEND_URL}/api`,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
  },
})

export async function ensureCsrfCookie(): Promise<void> {
  await axios.get(`${BACKEND_URL}/sanctum/csrf-cookie`, { withCredentials: true })
}
