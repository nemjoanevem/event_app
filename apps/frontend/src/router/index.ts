import { createRouter, createWebHistory } from 'vue-router';
import LoginView from '@/views/LoginView.vue';
import HomeView from '@/views/HomeView.vue';
import TicketsView from '@/views/TicketsView.vue';
import UsersView from '@/views/UsersView.vue';
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
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { redirectIfAuth: true, noNav: true },
  },
  {
    path: '/tickets',
    name: 'tickets',
    component: TicketsView,
    meta: { requiresAuth: true },
  },
  {
    path: '/my-events',
    name: 'my-events',
    component: () => import('@/views/HomeView.vue'),
    props: { ownOnly: true },
    meta: { requiresAuth: true, roles: ['organizer', 'admin'] }
  },
  {
    path: '/users',
    name: 'users',
    component: UsersView,
    meta: { requiresAuth: true, roles: ['admin'] },
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
