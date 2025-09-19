import { createRouter, createWebHistory } from 'vue-router';
import LoginView from '@/views/LoginView.vue';
import HomeView from '@/views/HomeView.vue';
import { useAuthStore } from '@/stores/auth';
import type { Role } from '@/stores/auth';

declare module 'vue-router' {
  interface RouteMeta {
    requiresAuth?: boolean;
    redirectIfAuth?: boolean;
    roles?: Role[];
  }
}

export const routes = [
  {
    path: '/',
    name: 'home',
    component: HomeView,
    meta: { requiresAuth: true }, // home is protected
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { redirectIfAuth: true, noNav: true },
  },
  // IMPORTANT: catch-all should not redirect to '/'
  { path: '/:pathMatch(.*)*', redirect: { name: 'login' } },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to) => {
  const auth = useAuthStore();
  await auth.ensureUserLoaded();

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath }, replace: true };
  }

  if (to.meta.redirectIfAuth && auth.isAuthenticated) {
    return { name: 'home', replace: true };
  }

  if (to.meta.roles?.length) {
    if (!auth.isAuthenticated || !auth.hasAnyRole(to.meta.roles)) {
      // optional: add a /403 route later
      return { name: 'home', replace: true };
    }
  }

  return true;
});

export default router;
