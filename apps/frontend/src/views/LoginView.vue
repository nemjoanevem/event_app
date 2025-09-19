<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="w-full max-w-md bg-white shadow-lg rounded-2xl p-8">
      <h1 class="text-xl font-semibold mb-6 text-center">{{ $t('app.title') }}</h1>

      <!-- Tabs -->
      <div class="flex gap-2 mb-6">
        <button class="flex-1 py-2 rounded-xl border"
                :class="tab==='login' ? 'bg-gray-900 text-white' : 'bg-white'"
                @click="switchTab('login')">{{ $t('auth.login') }}</button>
        <button class="flex-1 py-2 rounded-xl border"
                :class="tab==='register' ? 'bg-gray-900 text-white' : 'bg-white'"
                @click="switchTab('register')">{{ $t('auth.register') }}</button>
      </div>

      <!-- Login -->
      <form v-if="tab==='login'" class="space-y-4" @submit.prevent="onLogin">
        <div>
          <label class="block text-sm mb-1">{{ $t('auth.email') }}</label>
          <input v-model="login.email" type="email" required
                 class="w-full border rounded-xl px-4 py-2 outline-none focus:ring" />
        </div>
        <div>
          <label class="block text-sm mb-1">{{ $t('auth.password') }}</label>
          <input v-model="login.password" type="password" required
                 class="w-full border rounded-xl px-4 py-2 outline-none focus:ring" />
        </div>

        <button type="submit"
                :disabled="auth.loading"
                class="w-full rounded-xl py-2 bg-gray-900 text-white hover:opacity-90 disabled:opacity-60">
          {{ $t('auth.login') }}
        </button>

        <div class="text-sm text-gray-600 flex justify-between mt-2">
          <button type="button" class="underline" @click="switchTab('register')">{{ $t('auth.noAccount') }}</button>
          <button type="button" class="underline" @click="switchTab('forgot')">{{ $t('auth.forgot') }}</button>
        </div>

        <p v-if="auth.error" class="text-sm text-red-600">{{ auth.error }}</p>
      </form>

      <!-- Register -->
      <form v-else-if="tab==='register'" class="space-y-4" @submit.prevent="onRegister">
        <div>
          <label class="block text-sm mb-1">{{ $t('auth.name') }}</label>
          <input v-model="register.name" type="text" required
                 class="w-full border rounded-xl px-4 py-2 outline-none focus:ring" />
        </div>
        <div>
          <label class="block text-sm mb-1">{{ $t('auth.email') }}</label>
          <input v-model="register.email" type="email" required
                 class="w-full border rounded-xl px-4 py-2 outline-none focus:ring" />
        </div>
        <div>
          <label class="block text-sm mb-1">{{ $t('auth.password') }}</label>
          <input v-model="register.password" type="password" required minlength="8"
                 class="w-full border rounded-xl px-4 py-2 outline-none focus:ring" />
        </div>
        <div>
          <label class="block text-sm mb-1">{{ $t('auth.passwordConfirm') }}</label>
          <input v-model="register.password_confirmation" type="password" required minlength="8"
                 class="w-full border rounded-xl px-4 py-2 outline-none focus:ring" />
        </div>

        <button type="submit"
                :disabled="auth.loading"
                class="w-full rounded-xl py-2 bg-gray-900 text-white hover:opacity-90 disabled:opacity-60">
          {{ $t('auth.register') }}
        </button>

        <p v-if="auth.error" class="text-sm text-red-600">{{ auth.error }}</p>
      </form>

      <!-- Forgot -->
      <form v-else class="space-y-4" @submit.prevent="onForgot">
        <div>
          <label class="block text-sm mb-1">{{ $t('auth.email') }}</label>
          <input v-model="forgotEmail" type="email" required
                class="w-full border rounded-xl px-4 py-2 outline-none focus:ring" />
        </div>

        <button type="submit"
                :disabled="auth.loading"
                class="w-full rounded-xl py-2 bg-gray-900 text-white hover:opacity-90 disabled:opacity-60">
          {{ $t('auth.sendReset') }}
        </button>

        <!-- error -->
        <p v-if="auth.error" class="text-sm text-red-600">{{ auth.error }}</p>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

/** UI tab selection: login / register / forgot */
const tab = ref<'login' | 'register' | 'forgot'>('login')

/** Local form state for login/register/forgot. */
const login = ref({ email: '', password: '' })
const register = ref({ name: '', email: '', password: '', password_confirmation: '' })
const forgotEmail = ref('')

const auth = useAuthStore()
const router = useRouter()

/** Handle login submit. */
const onLogin = async () => {
  await auth.login(login.value)
  await router.push({ name: 'PostList' })
}

/** Handle registration submit. */
const onRegister = async () => {
  await auth.register(register.value)
  await router.push({ name: 'PostList' })
}

/** Handle forgot password submit. */
const onForgot = async () => {
  await auth.forgot(forgotEmail.value)
  // UX: we keep the user on the tab; backend email config may be missing in dev
}

const switchTab = (t: 'login'|'register'|'forgot') => {
  tab.value = t
  auth.clearFlash() // reset success/error when switching tabs
}
</script>

<style scoped>
/* No custom colors/styles; using Tailwind only for layout */
</style>
