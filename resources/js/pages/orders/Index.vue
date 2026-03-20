<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { onMounted, onBeforeUnmount, ref } from 'vue'
import { dashboard } from '@/routes'


const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard() },
]



type OrderRow = {
  id: number
  order_id?: number
  order_number?: string | null
  status?: string | null
  currency?: string | null
  total?: number | string | null
  billing_first_name?: string | null
  billing_last_name?: string | null
  billing_email?: string | null
  coupon_codes?: string | null
  payment_method_title?: string | null
  modified_at_gmt?: string | null
  updated_at?: string | null
}

type OrdersFeedResponse = {
  ok: boolean
  server_time?: string
  orders?: OrderRow[]
}

const props = defineProps<{
  orders: OrderRow[]
}>()

const rows = ref<OrderRow[]>([...props.orders])
const lastSince = ref<string>(new Date().toISOString())

// Works in browser + TS without NodeJS types
let timer: ReturnType<typeof setInterval> | null = null

async function poll(): Promise<void> {
  try {
    const url = `/app/orders/feed?since=${encodeURIComponent(lastSince.value)}&limit=50`
    const res = await fetch(url, {
      headers: { Accept: 'application/json' },
      cache: 'no-store',
    })

    const json = (await res.json()) as OrdersFeedResponse

    if (json.ok && Array.isArray(json.orders) && json.orders.length) {
      const byId = new Map<number, OrderRow>(rows.value.map((o) => [o.id, o]))
      for (const o of json.orders) byId.set(o.id, o)

      rows.value = Array.from(byId.values()).sort((a, b) => {
        const aTime = (a.modified_at_gmt || a.updated_at || '')
        const bTime = (b.modified_at_gmt || b.updated_at || '')
        return bTime.localeCompare(aTime)
      })

      lastSince.value = json.server_time || new Date().toISOString()
    } else {
      lastSince.value = json.server_time || lastSince.value
    }
  } catch {
    // ignore transient failures
  }
}

onMounted(() => {
  timer = setInterval(poll, 5000)
})

onBeforeUnmount(() => {
  if (timer) clearInterval(timer)
})
</script>


<template>
    <AppLayout :breadcrumbs="breadcrumbs">
  <div style="padding: 24px">
    <h1 style="font-size: 20px; font-weight: 600; margin-bottom: 16px">Orders</h1>

    <div v-if="orders.length === 0">
      No orders yet for this store.
    </div>

    <div v-else style="overflow-x:auto;">
      <table border="1" cellspacing="0" cellpadding="10" style="border-collapse: collapse; width: 100%;">
        <thead>
          <tr>
            <th>Modified</th>
            <th>Order</th>
            <th>Status</th>
            <th>Total</th>
            <th>Customer</th>
            <th>Coupons</th>
            <th>Payment</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="o in orders" :key="o.id">
            <td>{{ o.modified_at_gmt || '-' }}</td>
            <td>
                <a :href="`/app/orders/${o.order_id}`" style="text-decoration: underline;">
                    {{ o.order_number || o.order_id }}
                </a>
            </td>
            <td>{{ o.status }}</td>
            <td>{{ o.currency ? `${o.currency} ${o.total ?? ''}` : (o.total ?? '') }}</td>
            <td>
              <div>{{ [o.billing_first_name, o.billing_last_name].filter(Boolean).join(' ') }}</div>
              <div style="font-size: 12px; opacity: 0.8;">{{ o.billing_email }}</div>
            </td>
            <td>{{ o.coupon_codes || '-' }}</td>
            <td>{{ o.payment_method_title || '-' }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div style="margin-top: 16px; font-size: 12px; opacity: 0.8;">
      Showing latest 50 orders for active store.
    </div>
  </div>
  </AppLayout>
</template>

