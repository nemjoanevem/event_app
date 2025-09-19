import axios from 'axios';
import { useAuthStore } from '@/stores/auth';
import { useUiStore } from '@/stores/ui'

export const http = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000',
  withCredentials: true, // send session cookie
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'X-XSRF-TOKEN': getCookie('XSRF-TOKEN') || '',
  },
});

http.interceptors.request.use((config) => {
  const ui = useUiStore()
  ui.start()
  return config
})

http.interceptors.response.use(
  (res) => {
    const ui = useUiStore()
    ui.done()
    return res;
  },
  (err) => {
    if (axios.isAxiosError(err) && err.response?.status === 401) {
      useAuthStore().$reset();
    }
    const ui = useUiStore()
    ui.done()
    return Promise.reject(err);
  }
);

function getCookie(name: string) {
  const m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)'));
  return m ? decodeURIComponent(m[1]) : null;
}

// Helper: ensure Sanctum CSRF cookie before stateful POSTs
export async function ensureCsrfCookie() {
  // Do not cache; hit backend to set XSRF-TOKEN cookie
  await axios.get(
    (import.meta.env.VITE_BACKEND_URL ?? 'http://localhost:8000') + '/sanctum/csrf-cookie',
    { withCredentials: true }
  );
}
