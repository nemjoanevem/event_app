<template>
  <main class="max-w-5xl mx-auto p-6">
    <!-- Page header -->
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-semibold">{{ $t('home.title') }}</h1>

      <!-- Create button for organizer/admin -->
      <button
        v-if="canCreate"
        class="px-3 py-2 rounded-lg border hover:bg-gray-50"
        @click="openCreate()"
      >
        {{ $t('home.createEvent') }}
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <!-- Search column (left) -->
      <aside class="md:col-span-1">
        <div class="border rounded-xl p-4 bg-white">

          <!-- Date range filter -->
          <div class="mb-4 grid grid-cols-1 gap-3">
            <div>
              <label for="from" class="block text-sm font-medium mb-1">{{ $t('home.from') }}</label>
              <input
                id="from"
                v-model="fromDate"
                type="date"
                class="w-full border rounded-lg px-3 py-2"
                @change="onDateChange"
              />
            </div>
          <div>
            <label for="to" class="block text-sm font-medium mb-1">{{ $t('home.to') }}</label>
            <input
              id="to"
              v-model="toDate"
              type="date"
              class="w-full border rounded-lg px-3 py-2"
              @change="onDateChange"
            />
          </div>
        </div>

          <label for="q" class="block text-sm font-medium mb-2">
            {{ $t('home.searchLabel') }}
          </label>
          <input
            id="q"
            v-model="q"
            type="text"
            :placeholder="$t('home.searchPlaceholder')"
            class="w-full border rounded-lg px-3 py-2"
            @input="onQueryInput"
          />
          <p class="text-xs mt-2 opacity-70">
            {{ $t('home.searchHint') }}
          </p>
        </div>
      </aside>

      <!-- Results column (right) -->
      <section class="md:col-span-3">
        <!-- Empty state -->
        <div v-if="!loading && events.length === 0" class="text-center border rounded-xl p-10 bg-white">
          <p class="font-medium">{{ $t('home.noResults') }}</p>
          <p class="text-sm opacity-70 mt-1">{{ $t('home.tryDifferentSearch') }}</p>
        </div>

        <!-- Event list -->
        <ul v-else class="space-y-4">
          <li v-for="e in events" :key="e.id" class="border rounded-xl bg-white overflow-hidden">
            <div class="p-4 md:p-5">
              <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                <div class="min-w-0">
                  <h2 class="text-lg font-semibold truncate">{{ e.title }}</h2>
                  <div class="mt-1 text-sm opacity-80 flex flex-wrap gap-x-4 gap-y-1">
                    <span>{{ formatDate(e.startsAt) }}</span>
                    <span v-if="e.location">• {{ e.location }}</span>
                    <span v-if="e.category">• {{ e.category }}</span>
                    <span v-if="e.status" :class="statusClass(e.status)">• {{ $t('home.status.' + e.status) }}</span>
                  </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                  <!-- Book always available (guest will be asked for name/email) -->
                  <button class="px-3 py-1.5 rounded-lg border hover:bg-gray-50" @click="openBook(e)">
                    {{ $t('home.book') }}
                  </button>

                  <!-- Edit for admin or organizer who owns it -->
                  <button
                    v-if="canEdit(e)"
                    class="px-3 py-1.5 rounded-lg border hover:bg-gray-50"
                    @click="openEdit(e)"
                  >
                    {{ $t('home.edit') }}
                  </button>

                  <!-- Status change for admin or organizer(owner) -->
                  <div v-if="canEdit(e)" class="relative">
                    <select
                      class="px-3 py-1.5 rounded-lg border"
                      :value="e.status"
                      @change="(ev) => changeStatus(e, (ev.target as HTMLSelectElement).value)"
                    >
                      <option value="draft">{{ $t('home.status.draft') }}</option>
                      <option value="published">{{ $t('home.status.published') }}</option>
                      <option value="cancelled">{{ $t('home.status.cancelled') }}</option>
                    </select>
                  </div>

                  <!-- Delete for admin or organizer(owner) -->
                  <button
                    v-if="canEdit(e)"
                    class="px-3 py-1.5 rounded-lg border text-red-600 hover:bg-red-50"
                    @click="confirmDelete(e)"
                  >
                    {{ $t('home.delete') }}
                  </button>
                </div>
              </div>

              <p v-if="e.description" class="mt-3 text-sm leading-6 opacity-90 line-clamp-3">
                {{ e.description }}
              </p>

              <div class="mt-3 text-sm opacity-80 flex flex-wrap items-center gap-4">
                <span v-if="typeof e.availableSeats === 'number'">
                  {{ $t('home.availableSeats') }}: <strong>{{ e.availableSeats }}</strong>
                </span>
                <span v-if="e.price">
                  {{ $t('home.price') }}: <strong>{{ e.price }}</strong>
                </span>
              </div>
            </div>
          </li>
        </ul>

        <!-- Pagination -->
        <div v-if="meta" class="mt-6 flex flex-wrap items-center justify-between gap-3">
          <button
            class="px-3 py-2 rounded-lg border disabled:opacity-50"
            :disabled="page <= 1 || loading"
            @click="goTo(page - 1)"
          >
            {{ $t('home.prev') }}
          </button>

          <div class="text-sm opacity-80">
            {{ $t('home.pageXofY', { x: meta.current_page, y: meta.last_page }) }}
          </div>

          <div class="flex items-center gap-2">
            <label for="perPage" class="text-sm opacity-80">{{ $t('home.perPage') }}</label>
            <select
              id="perPage"
              class="px-3 py-2 rounded-lg border"
              v-model.number="perPage"
              @change="onPerPageChange"
              :disabled="loading"
            >
              <option :value="5">5</option>
              <option :value="10">10</option>
              <option :value="20">20</option>
              <option :value="50">50</option>
            </select>
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

    <!-- Create/Edit modal -->
    <dialog ref="eventDialog" class="rounded-xl p-0 w-full max-w-xl">
      <form class="p-5 space-y-4" @submit.prevent="saveEvent">
        <h3 class="text-lg font-semibold">
          {{ editing ? $t('home.editEvent') : $t('home.newEvent') }}
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm mb-1">{{ $t('home.fields.title') }}</label>
            <input v-model="form.title" type="text" class="w-full border rounded-lg px-3 py-2" required />
          </div>
          <div>
            <label class="block text-sm mb-1">{{ $t('home.fields.startsAt') }}</label>
            <input v-model="form.startsAt" type="datetime-local" class="w-full border rounded-lg px-3 py-2" required />
          </div>
          <div>
            <label class="block text-sm mb-1">{{ $t('home.fields.location') }}</label>
            <input v-model="form.location" type="text" class="w-full border rounded-lg px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm mb-1">{{ $t('home.fields.category') }}</label>
            <input v-model="form.category" type="text" class="w-full border rounded-lg px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm mb-1">{{ $t('home.fields.capacity') }}</label>
            <input v-model.number="form.capacity" type="number" min="1" class="w-full border rounded-lg px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm mb-1">{{ $t('home.fields.price') }}</label>
            <input v-model.number="form.price" type="number" min="0" step="0.01" class="w-full border rounded-lg px-3 py-2" />
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm mb-1">{{ $t('home.fields.description') }}</label>
            <textarea v-model="form.description" rows="3" class="w-full border rounded-lg px-3 py-2"></textarea>
          </div>
        </div>

        <div class="mt-4 flex items-center justify-end gap-2">
          <button type="button" class="px-3 py-2 rounded-lg border" @click="closeEventDialog">
            {{ $t('home.cancel') }}
          </button>
          <button type="submit" class="px-3 py-2 rounded-lg border bg-gray-900 text-white">
            {{ $t('home.save') }}
          </button>
        </div>
      </form>
    </dialog>

    <!-- Booking modal (simple) -->
    <dialog ref="bookDialog" class="centered rounded-xl p-0 w-full max-w-md">
      <form class="p-5 space-y-4" @submit.prevent="submitBooking">
        <h3 class="text-lg font-semibold">{{ $t('home.bookTicket') }}</h3>

        <!-- Selected event details -->
        <div v-if="selectedEvent" class="rounded-lg border p-3 bg-gray-50 text-sm">
          <div class="font-medium truncate">{{ selectedEvent.title }}</div>
          <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 opacity-80">
            <span v-if="typeof selectedEvent.availableSeats === 'number'">
              {{ $t('home.availableSeats') }}: <strong>{{ selectedEvent.availableSeats }}</strong>
            </span>
            <span v-if="selectedEvent.price">
              {{ $t('home.price') }}: <strong>{{ selectedEvent.price }}</strong>
            </span>
          </div>
        </div>

        <div v-if="!auth.isAuthenticated" class="grid grid-cols-1 gap-3">
          <div>
            <label class="block text-sm mb-1">{{ $t('auth.name') }}</label>
            <input v-model="booking.name" type="text" class="w-full border rounded-lg px-3 py-2" required />
          </div>
          <div>
            <label class="block text-sm mb-1">{{ $t('auth.email') }}</label>
            <input v-model="booking.email" type="email" class="w-full border rounded-lg px-3 py-2" required />
          </div>
        </div>

        <div>
          <label class="block text-sm mb-1">{{ $t('home.quantity') }}</label>
          <input v-model.number="booking.quantity" type="number" min="1" class="w-full border rounded-lg px-3 py-2" required />
        </div>

        <!-- Server error message -->
        <p v-if="bookingError" class="text-sm text-red-600">
          {{ bookingError }}
        </p>

        <div class="mt-4 flex items-center justify-end gap-2">
          <button type="button" class="px-3 py-2 rounded-lg border" @click="closeBookDialog">
            {{ $t('home.cancel') }}
          </button>
          <button type="submit" class="px-3 py-2 rounded-lg border bg-gray-900 text-white">
            {{ $t('home.confirm') }}
          </button>
        </div>
      </form>
    </dialog>

    <transition name="fade">
      <div
        v-if="bookingSuccess"
          class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-50 rounded-lg border bg-green-100 text-green-800 shadow px-6 py-4 text-base font-medium"
          role="status"
          aria-live="polite"
        >
        {{ bookingSuccess }}
      </div>
    </transition>
  </main>
</template>

<script setup lang="ts">
// Home page with server-side paginated event list + simple search column.
// Only frontend is implemented here; API endpoints are wired but backend messages are not surfaced.
import { onMounted, ref, watch, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { http } from '@/lib/http'
import { useAuthStore } from '@/stores/auth'
import { useI18n } from 'vue-i18n'

interface EventItem {
  id: number
  title: string
  description?: string
  startsAt: string
  location?: string
  category?: string
  status?: 'draft' | 'published' | 'cancelled'
  price?: string | number | null
  availableSeats?: number | null
  userId?: number // owner (may be absent in some payloads)
}

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const { t: $t } = useI18n()

// Query state synced to route (?q=&page=)
const q = ref<string>(String(route.query.q ?? ''))
const page = ref<number>(Number(route.query.page ?? 1))
const fromDate = ref<string>(String(route.query.from ?? ''))
const toDate = ref<string>(String(route.query.to ?? ''))
const perPage = ref<number>(Number(route.query.per_page ?? 10))
let debounce: number | undefined

const events = ref<EventItem[]>([])
const meta = ref<any>(null)
const loading = ref(false)

const bookingError = ref<string>('')
const bookingSuccess = ref<string>('')

const canCreate = computed(() => auth.user?.role === 'organizer' || auth.user?.role === 'admin')
const canEdit = (e: EventItem) => {
  const role = auth.user?.role
  if (role === 'admin') return true
  if (role === 'organizer' && e.userId && auth.user?.id === e.userId) return true
  return false
}

function onQueryInput() {
  // Debounce user typing and keep URL in sync
  window.clearTimeout(debounce)
  debounce = window.setTimeout(() => {
    page.value = 1
    syncQuery()
    fetchEvents()
  }, 350)
}

function onDateChange() {
  // Only query when both ends are present
  if (fromDate.value && toDate.value) {
    page.value = 1
    syncQuery()
    fetchEvents()
  }
}

function onPerPageChange() {
  page.value = 1
  syncQuery()
  fetchEvents()
}

function goTo(p: number) {
  page.value = Math.max(1, p)
  syncQuery()
  fetchEvents()
}

function syncQuery() {
  router.replace({
    query: {
      ...route.query,
      q: q.value || undefined,
      page: page.value !== 1 ? String(page.value) : undefined,
      from: fromDate.value || undefined,
      to: toDate.value || undefined,
      per_page: String(perPage.value) // ha már benne van nálad
    }
  })
}

function formatDate(iso: string) {
  // Basic local date formatting
  const d = new Date(iso)
  return d.toLocaleString()
}

function statusClass(status?: string) {
  if (status === 'published') return 'text-green-700'
  if (status === 'cancelled') return 'text-red-700'
  return 'opacity-80'
}

async function fetchEvents() {
  loading.value = true
  try {
    const params: any = { page: page.value, per_page: perPage.value, q: q.value || undefined }
    if (fromDate.value && toDate.value) {
      params.from = fromDate.value
      params.to = toDate.value
    }
    const { data } = await http.get('/events', { params })
    events.value = data.data || []
    meta.value = data.meta || null
  } finally {
    loading.value = false
  }
}

// Format helper for yyyy-MM-dd
function fmtDateInput(d: Date) {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

// Initialize default range: today start -> +7 days if not provided
onMounted(() => {
  if (!fromDate.value || !toDate.value) {
    const now = new Date()
    const start = new Date(now.getFullYear(), now.getMonth(), now.getDate())
    const end = new Date(start)
    end.setDate(end.getDate() + 7)
    fromDate.value = fromDate.value || fmtDateInput(start)
    toDate.value = toDate.value || fmtDateInput(end)
    syncQuery()
  }
  fetchEvents()
})

watch(() => route.query.page, (val) => {
  const p = Number(val || 1)
  if (p !== page.value) {
    page.value = p
    fetchEvents()
  }
})
watch(() => route.query.per_page, (val) => {
  const pp = Number(val || 10)
  if (pp !== perPage.value) {
    perPage.value = pp
    // keep current page from URL if present; otherwise refetch
    fetchEvents()
  }
})
watch(() => route.query.from, (val) => {
   const v = String(val || '')
   if (v !== fromDate.value) {
     fromDate.value = v
     if (fromDate.value && toDate.value) fetchEvents()
   }
})
watch(() => route.query.to, (val) => {
   const v = String(val || '')
   if (v !== toDate.value) {
     toDate.value = v
     if (fromDate.value && toDate.value) fetchEvents()
   }
})

// ——— Create / Edit ———
const eventDialog = ref<HTMLDialogElement | null>(null)
const editing = ref<EventItem | null>(null)
const form = ref<any>({ title: '', startsAt: '', location: '', category: '', capacity: null, price: null, description: '' })

function openCreate() {
  editing.value = null
  form.value = { title: '', startsAt: '', location: '', category: '', capacity: null, price: null, description: '' }
  eventDialog.value?.showModal()
}

function openEdit(e: EventItem) {
  editing.value = e
  form.value = {
    title: e.title,
    startsAt: e.startsAt?.slice(0, 16), // yyyy-MM-ddTHH:mm
    location: e.location || '',
    category: e.category || '',
    capacity: null,
    price: e.price ?? null,
    description: e.description || ''
  }
  eventDialog.value?.showModal()
}

function closeEventDialog() {
  eventDialog.value?.close()
}

async function saveEvent() {
  // NOTE: Frontend wiring; backend validation/errors not yet mapped to UI
  const payload = {
    title: form.value.title,
    starts_at: new Date(form.value.startsAt).toISOString(),
    location: form.value.location || null,
    category: form.value.category || null,
    capacity: form.value.capacity || null,
    price: form.value.price ?? null,
    description: form.value.description || null,
  }
  if (editing.value) {
    await http.put(`/events/${editing.value.id}`, payload)
  } else {
    await http.post('/events', payload)
  }
  closeEventDialog()
  fetchEvents()
}

// ——— Status / Delete ———
async function changeStatus(e: EventItem, status: string) {
  await http.patch(`/events/${e.id}/status`, { status })
  fetchEvents()
}

async function confirmDelete(e: EventItem) {
  if (window.confirm(String($t('home.confirmDelete')))) {
    await http.delete(`/events/${e.id}`)
    fetchEvents()
  }
}

// ——— Booking ———
const bookDialog = ref<HTMLDialogElement | null>(null)
const selectedEvent = ref<EventItem | null>(null)
const booking = ref<any>({ name: '', email: '', quantity: 1 })

function openBook(e: EventItem) {
  selectedEvent.value = e
  booking.value = { name: '', email: '', quantity: 1 }
  bookingError.value = ''
  bookDialog.value?.showModal()
}

function closeBookDialog() { bookDialog.value?.close() }

async function submitBooking() {
  const body: any = { quantity: Number(booking.value.quantity || 1) }
  if (!auth.isAuthenticated) {
    body.guest_name = booking.value.name
    body.guest_email = booking.value.email
  }
  try {
    const { data } = await http.post(`/events/${selectedEvent.value!.id}/bookings`, body)
    closeBookDialog()
    fetchEvents()
    // success toast with total price
    const amount = data?.data?.totalPrice ?? ''
    bookingSuccess.value = $t('home.bookingSuccess', { amount }) as string
    setTimeout(() => { bookingSuccess.value = '' }, 4000)
  } catch (err: any) {
    const data = err?.response?.data
    bookingError.value = data?.message || String($t('home.bookingError'))
  }
}
</script>

<style scoped>
/* line-clamp utility for descriptions without bringing extra deps */
.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

dialog.centered::backdrop {
  background: rgba(0, 0, 0, 0.4);
}

dialog.centered {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}

.fade-enter-active, .fade-leave-active { transition: opacity .2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
