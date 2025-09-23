<template>
  <header class="w-full border-b bg-white">
    <div class="max-w-4xl mx-auto flex items-center justify-between p-4">
      <router-link to="/home" class="font-semibold">{{ $t('app.title') }}</router-link>

      <!-- Role-based menu -->
      <nav class="flex items-center gap-4">
        <router-link
          v-if="showTickets"
          to="/tickets"
          class="text-sm hover:underline"
        >
          {{ $t('nav.myTickets') }}
        </router-link>
        <router-link
          v-if="showMyEvents"
          to="/home"
          class="text-sm hover:underline"
        >
          {{ $t('nav.myEvents') }}
        </router-link>
        <router-link
          v-if="showUsers"
          to="/users"
          class="text-sm hover:underline"
        >
          {{ $t('nav.users') }}
        </router-link>
      </nav>

      <div class="flex items-center gap-3">
        <!-- Welcome text for authenticated users -->
        <span v-if="auth.isAuthenticated" class="text-sm opacity-80">
          {{ $t('nav.welcome', { name: displayName }) }}
        </span>

        <router-link
          v-if="!auth.isAuthenticated"
          to="/login"
          class="px-3 py-1 rounded-lg border"
        >
          {{ $t('nav.login') }}
        </router-link>

        <button
          v-else
          @click="onLogout"
          class="px-3 py-1 rounded-lg border"
        >
          {{ $t('nav.logout') }}
        </button>
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()

// Prefer a friendly display name; fallback to email if needed
const displayName = computed(() => auth.user?.name || auth.user?.email)

// Role-based visibility for menu items
const role = computed(() => auth.user?.role) // 'admin' | 'organizer' | 'user' | undefined
const showTickets = computed(() => auth.isAuthenticated) // all signed-in roles
const showMyEvents  = computed(() => role.value === 'admin' || role.value === 'organizer')
const showUsers     = computed(() => role.value === 'admin')

const onLogout = async () => {
  // Log out and redirect to Login
  await auth.logout()
  router.push({ name: 'login' })
}
</script>
