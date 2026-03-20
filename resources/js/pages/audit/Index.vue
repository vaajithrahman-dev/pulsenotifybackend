<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { dashboard } from '@/routes'
import { useForm } from '@inertiajs/vue3'

type AuditRow = {
  id: number
  store_id: number
  actor_user_id: number | null
  action: string
  context_json: string | null
  created_at: string
}

const props = defineProps<{
  logs: AuditRow[]
  filters: {
    action?: string | null
    user_id?: string | number | null
    start?: string | null
    end?: string | null
    limit?: number | null
  }
}>()

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard() },
  { title: 'Audit Logs', href: '/app/audit-logs' },
]

const form = useForm({
  action: props.filters.action || '',
  user_id: props.filters.user_id || '',
  start: props.filters.start || '',
  end: props.filters.end || '',
  limit: props.filters.limit || 200,
})

function applyFilters() {
  form.get('/app/audit-logs', { preserveScroll: true, preserveState: true })
}

function exportCsv() {
  const params = new URLSearchParams({
    action: form.action || '',
    start: form.start || '',
    end: form.end || '',
  }).toString()
  window.location.href = `/app/audit-logs/export?${params}`
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div style="padding: 24px; display:flex; flex-direction:column; gap: 12px;">
      <h1 style="font-size: 20px; font-weight: 600;">Audit Logs</h1>

      <div style="border:1px solid #ddd; border-radius:10px; padding:12px; display:flex; flex-wrap:wrap; gap:12px;">
        <div>
          <label style="font-size:12px; opacity:0.7;">Action</label><br>
          <input v-model="form.action" placeholder="order.status_updated" style="min-width:180px;">
        </div>
        <div>
          <label style="font-size:12px; opacity:0.7;">Actor user ID</label><br>
          <input v-model="form.user_id" placeholder="user id" style="width:120px;">
        </div>
        <div>
          <label style="font-size:12px; opacity:0.7;">Start</label><br>
          <input type="date" v-model="form.start">
        </div>
        <div>
          <label style="font-size:12px; opacity:0.7;">End</label><br>
          <input type="date" v-model="form.end">
        </div>
        <div>
          <label style="font-size:12px; opacity:0.7;">Max rows</label><br>
          <input type="number" min="50" max="1000" v-model.number="form.limit" style="width:90px;">
        </div>
        <div style="align-self:flex-end; display:flex; gap:8px;">
          <button
            style="padding:8px 12px; border:1px solid #222; border-radius:6px; background:#111; color:#fff;"
            :disabled="form.processing"
            @click.prevent="applyFilters"
          >
            Apply
          </button>
          <button
            style="padding:8px 12px; border:1px solid #999; border-radius:6px; background:#fff; color:#111;"
            @click.prevent="exportCsv"
          >
            Export CSV
          </button>
        </div>
      </div>

      <div v-if="logs.length === 0" style="opacity:0.8;">No audit events for these filters.</div>

      <div v-else style="overflow-x:auto;">
        <table border="1" cellspacing="0" cellpadding="10" style="border-collapse: collapse; width:100%;">
          <thead>
            <tr>
              <th style="white-space:nowrap;">Time</th>
              <th>Action</th>
              <th style="white-space:nowrap;">Actor</th>
              <th>Context</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in logs" :key="row.id">
              <td style="white-space:nowrap;">{{ new Date(row.created_at).toLocaleString() }}</td>
              <td>{{ row.action }}</td>
              <td>{{ row.actor_user_id ?? 'system' }}</td>
              <td style="max-width:480px; white-space: pre-wrap; font-size:12px;">
                {{ row.context_json || '-' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div style="font-size:12px; opacity:0.7;">
        Showing up to {{ form.limit }} rows. Use CSV export for larger pulls (max 5000 rows).
      </div>
    </div>
  </AppLayout>
</template>
