<script setup lang="ts">
import { computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { dashboard } from '@/routes'
import type { BreadcrumbItem } from '@/types'

type StoreRow = {
  id: number
  store_id: string
  store_name: string | null
}

const props = defineProps<{
  stores: StoreRow[]
  active_store_id: number
}>()

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard() },
  { title: 'Add Store', href: '/app/stores/add' },
]

const page = usePage()
const flashSuccess = computed(() => (page.props as any).flash?.success ?? null)
const flashError = computed(() => (page.props as any).flash?.error ?? null)

const form = useForm<{ store_id: string }>({
  store_id: '',
})
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div style="padding: 24px; max-width: 720px;">
      <h1 style="font-size: 20px; font-weight: 600; margin-bottom: 12px;">Add Store</h1>

      <div v-if="flashSuccess" style="padding:10px;border:1px solid #2d7;border-radius:8px;margin-bottom:10px;">
        {{ flashSuccess }}
      </div>
      <div v-if="flashError" style="padding:10px;border:1px solid #d22;border-radius:8px;margin-bottom:10px;">
        {{ flashError }}
      </div>

      <p style="margin-bottom: 12px;">
        Paste the <b>Store ID</b> you got after pairing the WooCommerce website.
      </p>

      <div style="border:1px solid #ddd; padding:12px; border-radius:8px;">
        <label style="display:block; margin-bottom:6px;">
          Store ID
        </label>
        <input v-model="form.store_id" placeholder="store_xxxxxxxxxxxx" style="width:100%; padding:8px;" />

        <div v-if="form.errors.store_id" style="margin-top:8px;color:#d22;">
          {{ form.errors.store_id }}
        </div>

        <button
          style="margin-top:12px;"
          :disabled="form.processing"
          @click.prevent="form.post('/app/stores/add', { preserveScroll: true })"
        >
          Add Store
        </button>
      </div>

      <h2 style="font-size: 16px; font-weight: 600; margin: 18px 0 8px;">My Stores</h2>

      <div v-if="stores.length === 0" style="opacity:0.8;">
        No stores added yet.
      </div>

      <div v-else style="border:1px solid #ddd; border-radius:8px; overflow:hidden;">
        <table border="1" cellspacing="0" cellpadding="10" style="border-collapse: collapse; width: 100%;">
          <thead>
            <tr>
              <th>Store</th>
              <th>Store ID</th>
              <th>Active</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in stores" :key="s.id">
              <td>{{ s.store_name || '-' }}</td>
              <td>{{ s.store_id }}</td>
              <td>{{ s.id === active_store_id ? '✅' : '' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
