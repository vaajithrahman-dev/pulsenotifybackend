<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { dashboard } from '@/routes'
import { useForm } from '@inertiajs/vue3'


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

const props = defineProps<{
  orders: {
    data: OrderRow[]
    links: Array<{ url: string | null; label: string; active: boolean }>
    meta?: Record<string, unknown>
  }
  filters: {
    status?: string | null
    email?: string | null
    start?: string | null
    end?: string | null
    per_page?: number | null
  }
}>()

const form = useForm({
  status: props.filters.status || '',
  email: props.filters.email || '',
  start: props.filters.start || '',
  end: props.filters.end || '',
  per_page: props.filters.per_page || 20,
})

function applyFilters() {
  form.get('/app/orders', { preserveScroll: true, preserveState: true })
}
</script>


<template>
    <AppLayout :breadcrumbs="breadcrumbs">
  <div style="padding: 24px">
    <h1 style="font-size: 20px; font-weight: 600; margin-bottom: 16px">Orders</h1>

    <div style="border:1px solid #ddd; border-radius:10px; padding:12px; margin-bottom:16px; display:flex; flex-wrap:wrap; gap:12px;">
      <div>
        <label style="font-size:12px; opacity:0.7;">Status</label><br>
        <select v-model="form.status">
          <option value="">any</option>
          <option value="pending">pending</option>
          <option value="processing">processing</option>
          <option value="completed">completed</option>
          <option value="on-hold">on-hold</option>
          <option value="cancelled">cancelled</option>
          <option value="refunded">refunded</option>
          <option value="failed">failed</option>
        </select>
      </div>
      <div>
        <label style="font-size:12px; opacity:0.7;">Customer email</label><br>
        <input v-model="form.email" placeholder="example@customer.com" style="min-width:200px;">
      </div>
      <div>
        <label style="font-size:12px; opacity:0.7;">Start date</label><br>
        <input type="date" v-model="form.start">
      </div>
      <div>
        <label style="font-size:12px; opacity:0.7;">End date</label><br>
        <input type="date" v-model="form.end">
      </div>
      <div>
        <label style="font-size:12px; opacity:0.7;">Per page</label><br>
        <input type="number" min="5" max="100" v-model.number="form.per_page" style="width:80px;">
      </div>
      <div style="align-self:flex-end;">
        <button
          style="padding:8px 14px; border:1px solid #222; border-radius:6px; background:#111; color:white;"
          :disabled="form.processing"
          @click.prevent="applyFilters"
        >
          Apply
        </button>
      </div>
    </div>

    <div v-if="orders.data.length === 0">
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
          <tr v-for="o in orders.data" :key="o.id">
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

    <div v-if="orders.links?.length" style="margin-top:12px; display:flex; gap:6px; flex-wrap:wrap;">
      <a
        v-for="l in orders.links"
        :key="l.label"
        :href="l.url || '#'"
        :style="{
          padding: '6px 10px',
          border: '1px solid #ccc',
          borderRadius: '6px',
          background: l.active ? '#111' : '#fff',
          color: l.active ? '#fff' : '#111',
          pointerEvents: l.url ? 'auto' : 'none',
          opacity: l.url ? 1 : 0.5
        }"
        v-html="l.label"
      />
    </div>

    <div style="margin-top: 16px; font-size: 12px; opacity: 0.8;">
      Paginated orders for the active store.
    </div>
  </div>
  </AppLayout>
</template>

