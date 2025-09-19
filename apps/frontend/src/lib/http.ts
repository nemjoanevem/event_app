import axios from 'axios';

export const http = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000/api',
  withCredentials: true, // send session cookie
});

// Helper: ensure Sanctum CSRF cookie before stateful POSTs
export async function ensureCsrfCookie() {
  // Do not cache; hit backend to set XSRF-TOKEN cookie
  await axios.get(
    (import.meta.env.VITE_BACKEND_URL ?? 'http://localhost:8000') + '/sanctum/csrf-cookie',
    { withCredentials: true }
  );
}
