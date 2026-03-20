<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div style="padding: 24px; max-width: 820px;">
      <h1 style="font-size: 20px; font-weight: 600; margin-bottom: 12px;">Switch Store</h1>

      <div v-if="flashSuccess" style="padding:10px;border:1px solid #2d7;border-radius:8px;margin-bottom:10px;">
        {{ flashSuccess }}
      </div>
      <div v-if="flashError" style="padding:10px;border:1px solid #d22;border-radius:8px;margin-bottom:10px;">
        {{ flashError }}
      </div>

      <div v-if="stores.length === 0" style="opacity:0.8;">
        No stores available for your account.
      </div>

      <div v-else style="border:1px solid #ddd; border-radius:10px; overflow:hidden;">
        <table border="1" cellspacing="0" cellpadding="10" style="border-collapse: collapse; width: 100%;">
          <thead>
            <tr>
              <th>Store</th>
              <th>Store ID</th>
              <th>Active</th>
              <th>Action</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="s in stores" :key="s.id">
              <td>{{ s.store_name || '-' }}</td>
              <td>{{ s.store_id }}</td>
              <td>{{ s.id === active_store_id ? '✅' : '' }}</td>
              <td>
                <button
                  :disabled="s.id === active_store_id || form.processing"
                  @click.prevent="switchTo(s.id)"
                >
                  Switch
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div style="margin-top: 12px; font-size: 12px; opacity: 0.8;">
        Tip: If you don’t see a store here, go to <b>Add Store</b> and claim it using the Store ID from WordPress pairing.
      </div>

      <div style="margin-top: 12px;">
        <a href="/app/stores/add" style="text-decoration: underline;">+ Add Store</a>
      </div>
    </div>
  </AppLayout>
</template>

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
  { title: 'Switch Store', href: '/app/stores/switch' },
]

const page = usePage()
const flashSuccess = computed(() => (page.props as any).flash?.success ?? null)
const flashError = computed(() => (page.props as any).flash?.error ?? null)

const form = useForm<{ store_pk: number }>({
  store_pk: props.active_store_id || 0,
})

function switchTo(storePk: number) {
  form.store_pk = storePk
  form.post('/app/stores/switch', { preserveScroll: true })
}
</script>