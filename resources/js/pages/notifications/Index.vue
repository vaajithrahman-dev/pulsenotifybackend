<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { dashboard } from '@/routes'

type NotificationItem = {
  id: string
  summary: string
  event_type: string
  event_id: string
  order_id: number | null
  link: string | null
  store_id: number | string | null
  read_at: string | null
  created_at: string | null
}

defineProps<{
  notifications: NotificationItem[]
  active_store_id: number
}>()

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard() },
  { title: 'Notifications', href: '/app/notifications' },
]
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div style="padding: 24px">
      <h1 style="font-size: 20px; font-weight: 600; margin-bottom: 12px;">Notifications</h1>
      <p style="margin-bottom: 12px; opacity:0.8; font-size:14px;">
        Showing latest {{ notifications.length }} notifications
        <span v-if="active_store_id">for active store ID {{ active_store_id }}.</span>
      </p>

      <div v-if="notifications.length === 0">
        No notifications yet.
      </div>

      <div v-else style="overflow-x:auto;">
        <table border="1" cellspacing="0" cellpadding="10" style="border-collapse: collapse; width: 100%;">
          <thead>
            <tr>
              <th style="white-space:nowrap;">Time</th>
              <th>Summary</th>
              <th style="white-space:nowrap;">Event</th>
              <th style="white-space:nowrap;">Order</th>
              <th style="white-space:nowrap;">Store ID</th>
              <th style="white-space:nowrap;">Read</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="n in notifications" :key="n.id">
              <td>{{ n.created_at ? new Date(n.created_at).toLocaleString() : '-' }}</td>
              <td>{{ n.summary || '-' }}</td>
              <td>
                <div>{{ n.event_type || '-' }}</div>
                <div style="font-size:12px; opacity:0.7;">ID: {{ n.event_id || '-' }}</div>
              </td>
              <td>
                <a v-if="n.order_id" :href="`/app/orders/${n.order_id}`" style="text-decoration: underline;">
                  #{{ n.order_id }}
                </a>
                <span v-else>-</span>
              </td>
              <td>{{ n.store_id ?? '-' }}</td>
              <td>{{ n.read_at ? 'Yes' : 'Unread' }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div style="margin-top: 12px; font-size: 12px; opacity: 0.7;">
        Unread notifications were marked as read when you opened this page.
      </div>
    </div>
  </AppLayout>
</template>
