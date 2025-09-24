<template>
  <main class="max-w-5xl mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold">{{ pageTitle }}</h1>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <!-- Search column -->
      <aside class="md:col-span-1">
        <div class="border rounded-xl p-4 bg-white">
          <label for="q" class="block text-sm font-medium mb-2">
            {{ $t('tickets.searchLabel') }}
          </label>
          <input
            id="q"
            v-model="q"
            type="text"
            :placeholder="$t('tickets.searchPlaceholder')"
            class="w-full border rounded-lg px-3 py-2"
            @input="onQueryInput"
          />
          <p class="text-xs mt-2 opacity-70">
            {{ $t('tickets.searchHint') }}
          </p>
        </div>
      </aside>

      <!-- Results column -->
      <section class="md:col-span-3">
        <!-- Empty state -->
        <div v-if="!loading && tickets.length === 0" class="text-center border rounded-xl p-10 bg-white">
          <p class="font-medium">{{ $t('tickets.noResults') }}</p>
        </div>

        <!-- Tickets table/list -->
        <div v-else class="border rounded-xl overflow-x-auto bg-white">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
              <tr class="text-left">
                <th class="px-4 py-3 font-medium">{{ $t('tickets.event') }}</th>
                <th class="px-4 py-3 font-medium">{{ $t('tickets.quantity') }}</th>
                <th class="px-4 py-3 font-medium">{{ $t('tickets.totalPrice') }}</th>
                <th class="px-4 py-3 font-medium">{{ $t('tickets.startsAt') }}</th>
                <th v-if="showUserNameAndEmail()" class="px-4 py-3 font-medium">{{ $t('tickets.userName') }}</th>
                <th v-if="showUserNameAndEmail()" class="px-4 py-3 font-medium">{{ $t('tickets.userEmail') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="b in tickets" :key="b.id" class="border-t">
                <td class="px-4 py-3">{{ b.eventTitle }}</td>
                <td class="px-4 py-3">{{ b.quantity }}</td>
                <td class="px-4 py-3">{{ b.totalPrice }}</td>
                <td class="px-4 py-3">{{ formatDate(b.startsAt) }}</td>
                <td v-if="showUserNameAndEmail()" class="px-4 py-3">{{ b.name || '—' }}</td>
                <td v-if="showUserNameAndEmail()" class="px-4 py-3">{{ b.email || '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
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
  </main>
</template>

<script setup lang="ts">
// Tickets listing for the current user; admins see all tickets.
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { http } from '@/lib/http'
import { useAuthStore } from '@/stores/auth'
import { useI18n } from 'vue-i18n'
import { formatDate } from '@/utils/date';

interface TicketRow {
  id: number
  eventId: number
  eventTitle: string
  quantity: number
  totalPrice: string | number | null
  startsAt: string
  name?: string | null
  email?: string | null
}

const auth = useAuthStore()
const isAdmin = computed(() => auth.user?.role === 'admin')
const { t } = useI18n()
const pageTitle = computed(() => (isAdmin.value ? String(t('nav.tickets')) : String(t('nav.myTickets'))))

const route = useRoute()
const router = useRouter()

const q = ref<string>(String(route.query.q ?? ''))
const page = ref<number>(Number(route.query.page ?? 1))

const tickets = ref<TicketRow[]>([])
const meta = ref<any>(null)
const loading = ref(false)

const eventId = ref<number | null>(null)

let debounce: number | undefined

function showUserNameAndEmail() {
  //If user is Admin OR user is organizer
  if( isAdmin.value || auth.user?.role === 'organizer' ) {
    console.log("isAdmin or isOrganizer");
    return true
  }
  return false
}

function onQueryInput() {
  window.clearTimeout(debounce)
  debounce = window.setTimeout(() => {
    page.value = 1
    syncQuery()
    fetchTickets()
  }, 350)
}

function goTo(p: number) {
  page.value = Math.max(1, p)
  syncQuery()
  fetchTickets()
}

function syncQuery() {
  router.replace({ query: { ...route.query, q: q.value || undefined, page: page.value !== 1 ? String(page.value) : undefined } })
}

async function fetchTickets() {
  loading.value = true
  try {
    const { data } = await http.get('/bookings', { params: { page: page.value, q: q.value || undefined, event_id: eventId.value || undefined } })
    tickets.value = data.data || data || []
    meta.value = data.meta || null
  } finally {
    loading.value = false
  }
}
onMounted(() => {
  const q = route.query.event_id
  eventId.value = q ? Number(q) : null
  fetchTickets()
})
watch(() => route.query.page, (val) => {
  const p = Number(val || 1)
  if (p !== page.value) {
    page.value = p
    fetchTickets()
  }
})
</script>
