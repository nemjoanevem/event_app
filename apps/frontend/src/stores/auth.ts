import { defineStore } from 'pinia';
import { http, ensureCsrfCookie } from '@/lib/http';
import { parseApiError } from '@/lib/errors';

export type Role = 'user' | 'organizer' | 'admin';

export interface User {
  id: number;
  name: string;
  email: string;
  role: Role;
}

interface State {
  user: User | null;
  loading: boolean;
  error: string | null;
}

type LoginPayload = { email: string; password: string }
type RegisterPayload = { name: string; email: string; password: string; password_confirmation: string }

export const useAuthStore = defineStore('auth', {
  state: (): State => ({
    user: null,
    loading: false,
    error: null,
  }),
  getters: {
    isAuthenticated: (s) => !!s.user,
    hasRole: (s) => (role: Role) => s.user?.role === role,
    hasAnyRole: (s) => (roles: Role[]) => !!s.user && roles.includes(s.user.role),
  },
  actions: {
    async fetchUser() {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await http.get<{ data: User }>('/user');
        this.user = data.data;
      } catch (e) {
        this.user = null;
      } finally {
        this.loading = false;
      }
    },

    async login({ email, password }: LoginPayload) {
      this.loading = true;
      this.error = null;
      try {
        await ensureCsrfCookie();
        const { data } = await http.post<{ data: User }>('/login', { email, password });
        this.user = data.data;
      } catch (e: any) {
        // Keep message simple; backend localized message is not shown directly
        this.error = parseApiError(e, 'login');
        this.user = null;
        throw e;
      } finally {
        this.loading = false;
      }
    },

    async logout() {
      this.loading = true;
      this.error = null;
      try {
        await http.post('/logout');
        this.user = null;
      } finally {
        this.loading = false;
      }
    },

    async ensureUserLoaded() {
      if (this.user || this.loading) return;
      try {
        await this.fetchUser();
      } catch {
      }
    },

    async register({ name, email, password, password_confirmation }: RegisterPayload) {
      this.loading = true;
      this.error = null;
      try {
        await ensureCsrfCookie();
        const { data } = await http.post<{ data: User }>('/register', { name, email, password, password_confirmation });
        this.user = data.data;
      } catch (e: any) {
        // Keep message simple; backend localized message is not shown directly
        this.error = parseApiError(e, 'register');
        this.user = null;
        throw e;
      } finally {
        this.loading = false;
      }
    },

    clearFlash() {
      this.error = null;
    }
  }
});
