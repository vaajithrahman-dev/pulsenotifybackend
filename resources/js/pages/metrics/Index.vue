<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { dashboard } from '@/routes'
import { useForm } from '@inertiajs/vue3'

type Summary = {
  revenue: number
  orders: number
  aov: number
}

type CouponRow = {
  code: string
  orders: number
  discount_total: number
  gross_total: number
}

const props = defineProps<{
  filters: { start: string; end: string }
  summary: Summary
  coupons: CouponRow[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard() },
  { title: 'Metrics', href: '/app/metrics' },
]

const form = useForm({
  start: props.filters.start,
  end: props.filters.end,
})

function applyFilters() {
  form.get('/app/metrics', { preserveScroll: true, preserveState: true })
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div style="padding: 24px; display: flex; flex-direction: column; gap: 16px;">
      <div style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
        <div>
          <label style="font-size:12px; opacity:0.7;">Start date</label><br />
          <input type="date" v-model="form.start" />
        </div>
        <div>
          <label style="font-size:12px; opacity:0.7;">End date</label><br />
          <input type="date" v-model="form.end" />
        </div>
        <button
          style="padding:8px 14px; border:1px solid #222; border-radius:6px; background:#111; color:white;"
          :disabled="form.processing"
          @click.prevent="applyFilters"
        >
          Apply
        </button>
      </div>

      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:12px;">
        <div style="border:1px solid #ddd; border-radius:10px; padding:12px;">
          <div style="font-size:12px; opacity:0.7;">Revenue</div>
          <div style="font-size:24px; font-weight:700;">${{ summary.revenue.toFixed(2) }}</div>
        </div>
        <div style="border:1px solid #ddd; border-radius:10px; padding:12px;">
          <div style="font-size:12px; opacity:0.7;">Orders</div>
          <div style="font-size:24px; font-weight:700;">{{ summary.orders }}</div>
        </div>
        <div style="border:1px solid #ddd; border-radius:10px; padding:12px;">
          <div style="font-size:12px; opacity:0.7;">AOV</div>
          <div style="font-size:24px; font-weight:700;">${{ summary.aov.toFixed(2) }}</div>
        </div>
      </div>

      <div style="border:1px solid #ddd; border-radius:10px; padding:12px;">
        <h2 style="margin:0 0 8px;">Top coupons</h2>
        <div v-if="coupons.length === 0" style="opacity:0.8;">No coupon usage in this window.</div>
        <div v-else style="overflow-x:auto;">
          <table border="1" cellspacing="0" cellpadding="10" style="border-collapse: collapse; width:100%;">
            <thead>
              <tr>
                <th>Code</th>
                <th>Orders</th>
                <th>Discount total</th>
                <th>Gross total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in coupons" :key="c.code">
                <td>{{ c.code }}</td>
                <td>{{ c.orders }}</td>
                <td>${{ c.discount_total.toFixed(2) }}</td>
                <td>${{ c.gross_total.toFixed(2) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
