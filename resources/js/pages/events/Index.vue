<script setup lang="ts">
import { computed, ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { dashboard } from '@/routes'
import type { BreadcrumbItem } from '@/types'

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard() },
]

type EventItem = {
  event_type?: string | null
  event_id?: string | number | null
  order_id?: string | number | null
  received_at?: string | null
  is_feed_only?: boolean | null
  payload?: unknown
}

const props = defineProps<{
  events?: EventItem[] | null
}>()

const typeFilter = ref('all')
const modeFilter = ref<'all' | 'feed-only' | 'normal'>('all')
const search = ref('')

const safeEvents = computed<EventItem[]>(() => {
  return Array.isArray(props.events) ? props.events.filter(Boolean) : []
})

const eventTypes = computed<string[]>(() => {
  const set = new Set(
    safeEvents.value
      .map((e) => e?.event_type)
      .filter((v): v is string => typeof v === 'string' && v.length > 0)
  )

  return ['all', ...Array.from(set).sort()]
})

const filtered = computed<EventItem[]>(() => {
  const q = search.value.trim().toLowerCase()

  return safeEvents.value.filter((e) => {
    if (typeFilter.value !== 'all' && e?.event_type !== typeFilter.value) return false
    if (modeFilter.value === 'feed-only' && !e?.is_feed_only) return false
    if (modeFilter.value === 'normal' && !!e?.is_feed_only) return false

    if (!q) return true

    const hay = [
      e?.event_type ?? '',
      e?.event_id != null ? String(e.event_id) : '',
      e?.order_id != null ? String(e.order_id) : '',
      safeStringify(e?.payload ?? {}),
    ]
      .join(' ')
      .toLowerCase()

    return hay.includes(q)
  })
})

function safeStringify(value: unknown): string {
  try {
    return JSON.stringify(value, null, 2) ?? ''
  } catch {
    return String(value ?? '')
  }
}

function pretty(obj: unknown): string {
  return safeStringify(obj)
}
</script>

<template>

  <AppLayout :breadcrumbs="breadcrumbs">
  <div style="padding: 24px">
    <h1 style="font-size: 20px; font-weight: 600; margin-bottom: 16px">Events</h1>

    <div style="display:flex; gap:12px; align-items:center; margin-bottom: 16px; flex-wrap: wrap;">
      <label>
        Type:
        <select v-model="typeFilter">
          <option v-for="t in eventTypes" :key="t" :value="t">{{ t }}</option>
        </select>
      </label>

      <label>
        Mode:
        <select v-model="modeFilter">
          <option value="all">all</option>
          <option value="normal">normal</option>
          <option value="feed-only">feed-only</option>
        </select>
      </label>

      <label style="flex:1; min-width: 240px;">
        Search:
        <input
          v-model="search"
          placeholder="order id, event id, type..."
          style="width:100%;"
        />
      </label>
    </div>

    <div v-if="safeEvents.length === 0">
      No events yet for this store.
    </div>

    <div v-else-if="filtered.length === 0">
      No events match the current filters.
    </div>

    <div v-else style="overflow-x:auto;">
      <table border="1" cellspacing="0" cellpadding="10" style="border-collapse: collapse; width: 100%;">
        <thead>
          <tr>
            <th>Received</th>
            <th>Type</th>
            <th>Mode</th>
            <th>Order</th>
            <th>Payload</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(e, idx) in filtered" :key="e?.event_id ?? e?.event_id ?? idx">
            <td>{{ e?.received_at || '-' }}</td>
            <td>{{ e?.event_type || '-' }}</td>
            <td>
              <span
                v-if="e?.is_feed_only"
                style="padding:2px 6px;border:1px solid #999;border-radius:10px;font-size:12px;"
              >
                Feed-only
              </span>
              <span
                v-else
                style="padding:2px 6px;border:1px solid #2d7;border-radius:10px;font-size:12px;"
              >
                Normal
              </span>
            </td>
            <td>
              <a
                v-if="e?.order_id != null"
                :href="`/app/orders/${e.order_id}`"
                style="text-decoration: underline;"
              >
                #{{ e.order_id }}
              </a>
              <span v-else>-</span>
            </td>
            <td>
              <details>
                <summary style="cursor:pointer;">View</summary>
                <pre style="white-space: pre-wrap; font-size: 12px; margin-top: 8px;">{{ pretty(e?.payload) }}</pre>
              </details>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div style="margin-top: 16px; font-size: 12px; opacity: 0.8;">
      Showing latest {{ safeEvents.length }} events for active store.
    </div>
  </div>

  </AppLayout>
</template>