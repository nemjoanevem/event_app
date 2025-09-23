<template>
  <main class="max-w-5xl mx-auto p-6">
    <!-- Page header -->
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold">{{ $t('users.title') }}</h1>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <!-- Search column -->
      <aside class="md:col-span-1">
        <div class="border rounded-xl p-4 bg-white">
          <label for="q" class="block text-sm font-medium mb-2">
            {{ $t('users.searchLabel') }}
          </label>
          <input
            id="q"
            v-model="q"
            type="text"
            :placeholder="$t('users.searchPlaceholder')"
            class="w-full border rounded-lg px-3 py-2"
            @input="onQueryInput"
          />
          <p class="text-xs mt-2 opacity-70">
            {{ $t('users.searchHint') }}
          </p>
        </div>
      </aside>

      <!-- Results column -->
      <section class="md:col-span-3">
        <!-- Empty state -->
        <div v-if="!loading && users.length === 0" class="text-center border rounded-xl p-10 bg-white">
          <p class="font-medium">{{ $t('users.noResults') }}</p>
        </div>

        <!-- Users table -->
        <div v-else class="border rounded-xl overflow-x-auto bg-white">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
              <tr class="text-left">
                <th class="px-4 py-3 font-medium">{{ $t('users.name') }}</th>
                <th class="px-4 py-3 font-medium">{{ $t('users.email') }}</th>
                <th class="px-4 py-3 font-medium">{{ $t('users.createdAt') }}</th>
                <th class="px-4 py-3 font-medium">{{ $t('users.status') }}</th>
                <th class="px-4 py-3 font-medium text-right">{{ $t('users.actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="u in users" :key="u.id" class="border-t">
                <td class="px-4 py-3">{{ u.name || '—' }}</td>
                <td class="px-4 py-3">{{ u.email }}</td>
                <td class="px-4 py-3">{{ formatDate(u.createdAt || u.created_at) }}</td>
                <td class="px-4 py-3">
                  <span
                    :class="[
                      'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                      u.enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700'
                    ]"
                  >
                    {{ u.enabled ? $t('users.enabled') : $t('users.disabled') }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right">
                  <button
                    class="px-3 py-1.5 rounded-lg border hover:bg-gray-50"
                    :disabled="togglingId === u.id"
                    @click="toggleEnabled(u)"
                  >
                    <span v-if="u.enabled">{{ $t('users.disable') }}</span>
                    <span v-else>{{ $t('users.enable') }}</span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination (simple) -->
        <div v-if="meta" class="mt-6 flex items-center justify-between">
          <button
            class="px-3 py-2 rounded-lg border disabled:opacity-50"
            :disabled="page <= 1 || loading"
            @click="goTo(page - 1)"
          >
            {{ $t('home.prev') }}
          </button>

          <div class="text-sm opacity-80">
            {{ $t('home.pageXofY', { x: meta.current_page, y: meta.last_page || 1 }) }}
          </div>

          <button
            class="px-3 py-2 rounded-lg border disabled:opacity-50"
            :disabled="page >= (meta.last_page || 1) || loading"
            @click="goTo(page + 1)"
          >
            {{ $t('home.next') }}
          </button>
        </div>
      </section>
    </div>

    <!-- Centered success toast for enable/disable -->
    <transition name="fade">
      <div
        v-if="toast"
        class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-50 rounded-lg border bg-green-100 text-green-800 shadow px-6 py-4 text-base font-medium"
        role="status"
        aria-live="polite"
      >
        {{ toast }}
      </div>
    </transition>
  </main>
</template>

<script setup lang="ts">
// Admin users list with search by name/email and enable/disable toggle.
import { onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { http } from '@/lib/http'
import { useI18n } from 'vue-i18n'

interface AdminUserRow {
  id: number
  name: string | null
  email: string
  createdAt?: string
  created_at?: string
  enabled: boolean
}

const { t: $t } = useI18n()

const route = useRoute()
const router = useRouter()

const q = ref<string>(String(route.query.q ?? ''))
const page = ref<number>(Number(route.query.page ?? 1))

const users = ref<AdminUserRow[]>([])
const meta = ref<any>(null)
const loading = ref(false)
const togglingId = ref<number | null>(null)
const toast = ref<string>('')
let debounce: number | undefined

function onQueryInput() {
  window.clearTimeout(debounce)
  debounce = window.setTimeout(() => {
    page.value = 1
    syncQuery()
    fetchUsers()
  }, 350)
}

function goTo(p: number) {
  page.value = Math.max(1, p)
  syncQuery()
  fetchUsers()
}

function syncQuery() {
  router.replace({ query: { ...route.query, q: q.value || undefined, page: page.value !== 1 ? String(page.value) : undefined } })
}

function formatDate(iso?: string) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString()
}

async function fetchUsers() {
  loading.value = true
  try {
    const { data } = await http.get('/admin/users', { params: { page: page.value, q: q.value || undefined } })
    users.value = data.data || data || []
    meta.value = data.meta || null
  } finally {
    loading.value = false
  }
}

async function toggleEnabled(u: AdminUserRow) {
  const next = !u.enabled
  const confirmText = next ? String($t('users.confirmEnable')) : String($t('users.confirmDisable'))
  if (!window.confirm(confirmText)) return
  togglingId.value = u.id
  try {
    await http.patch(`/admin/users/${u.id}/enabled`, { enabled: next })
    u.enabled = next
    toast.value = next ? String($t('users.toastEnabled')) : String($t('users.toastDisabled'))
    window.setTimeout(() => (toast.value = ''), 2500)
  } finally {
    togglingId.value = null
  }
}

onMounted(fetchUsers)
watch(() => route.query.page, (val) => {
  const p = Number(val || 1)
  if (p !== page.value) {
    page.value = p
    fetchUsers()
  }
})
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
