<template>
  <header class="w-full border-b bg-white">
    <div class="max-w-4xl mx-auto flex items-center justify-between p-4">
      <router-link to="/home" class="font-semibold">{{ $t('app.title') }}</router-link>

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

const onLogout = async () => {
  // Log out and redirect to Login
  await auth.logout()
  router.push({ name: 'login' })
}
</script>
