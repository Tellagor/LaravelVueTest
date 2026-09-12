import axios from 'axios'

const BACKEND_URL = import.meta.env.VITE_BACKEND_URL || 'http://127.0.0.1:8000'

export const apiClient = axios.create({
  baseURL: `${BACKEND_URL}/api`,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
  },
})

export async function ensureCsrfCookie() {
  await axios.get(`${BACKEND_URL}/sanctum/csrf-cookie`, { withCredentials: true })
}
