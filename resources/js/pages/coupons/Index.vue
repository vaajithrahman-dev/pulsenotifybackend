<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { dashboard } from '@/routes'
import type { BreadcrumbItem } from '@/types'

type Coupon = {
  id?: number
  code?: string
  discount_type?: string | null
  amount?: number | string | null
  date_expires_gmt?: string | null
  usage_count?: number | null
  usage_limit?: number | null
  updated_at?: string | null
}

// Breadcrumbs (keep your existing style)
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard() },
]

// Props (typed)
const props = defineProps<{
  coupons: Coupon[]
}>()

// Filters
const search = ref<string>('')
const typeFilter = ref<string>('all')

const types = computed<string[]>(() => {
  const set = new Set<string>()
  for (const c of props.coupons) {
    if (c.discount_type) set.add(c.discount_type)
  }
  return Array.from(set).sort()
})

const filtered = computed<Coupon[]>(() => {
  const q = search.value.trim().toLowerCase()

  return props.coupons.filter((c) => {
    if (typeFilter.value !== 'all' && c.discount_type !== typeFilter.value) return false
    if (!q) return true
    return String(c.code ?? '').toLowerCase().includes(q)
  })
})
</script>

<template>

    <AppLayout :breadcrumbs="breadcrumbs">

  <div style="padding: 24px">
    <h1 style="font-size: 20px; font-weight: 600; margin-bottom: 16px">Coupons</h1>

    <div style="display:flex; gap:12px; align-items:center; margin-bottom:16px; flex-wrap:wrap;">
      <label style="flex:1; min-width: 240px;">
        Search:
        <input v-model="search" placeholder="coupon code..." style="width:100%;" />
      </label>
      <label>
        Type:
        <select v-model="typeFilter">
          <option value="all">all</option>
          <option v-for="t in types" :key="t" :value="t">{{ t }}</option>
        </select>
      </label>
    </div>

    <div v-if="filtered.length === 0">
      No coupons found for this store.
    </div>

    <div v-else style="overflow-x:auto;">
      <table border="1" cellspacing="0" cellpadding="10" style="border-collapse: collapse; width: 100%;">
        <thead>
          <tr>
            <th>Code</th>
            <th>Type</th>
            <th>Amount</th>
            <th>Expires</th>
            <th>Usage</th>
            <th>Updated</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in filtered" :key="c.id">
            <td><b>{{ c.code }}</b></td>
            <td>{{ c.discount_type || '-' }}</td>
            <td>{{ c.amount ?? '-' }}</td>
            <td>{{ c.date_expires_gmt || '-' }}</td>
            <td>{{ (c.usage_count ?? 0) }} / {{ c.usage_limit ?? '∞' }}</td>
            <td>{{ c.updated_at || '-' }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div style="margin-top: 16px; font-size: 12px; opacity: 0.8;">
      Showing up to 200 coupons for active store.
    </div>
  </div>

  </AppLayout>

</template>

