<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { dashboard } from '@/routes'
import type { BreadcrumbItem } from '@/types'

const props = defineProps<{
  token_url: string
  expires_at: string
  store: {
    id: number
    store_id: string
    store_name: string | null
  }
}>()

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard() },
  { title: 'QR Login', href: '/qr-login' },
]

const qrSrc = computed(() =>
  `https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=${encodeURIComponent(props.token_url)}`
)

const page = usePage()
const flashSuccess = computed(() => (page.props as any).flash?.success ?? null)
const flashError = computed(() => (page.props as any).flash?.error ?? null)
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div style="padding: 24px; max-width: 640px;">
      <h1 style="font-size: 20px; font-weight: 600; margin-bottom: 12px;">QR Login</h1>
      <p style="margin-bottom: 12px;">
        Scan this QR code with another device to sign in to <b>{{ store.store_name || 'this store' }}</b>.
        The code expires at {{ new Date(expires_at).toLocaleTimeString() }}.
      </p>

      <div v-if="flashSuccess" style="padding:10px;border:1px solid #2d7;border-radius:8px;margin-bottom:10px;">
        {{ flashSuccess }}
      </div>
      <div v-if="flashError" style="padding:10px;border:1px solid #d22;border-radius:8px;margin-bottom:10px;">
        {{ flashError }}
      </div>

      <div style="border:1px solid #ddd; border-radius:12px; padding:16px; display:flex; gap:16px; align-items:center; flex-wrap:wrap;">
        <img :src="qrSrc" alt="QR code for login" width="240" height="240" style="border-radius:8px;" />

        <div style="flex:1; min-width: 200px;">
          <div style="font-size: 12px; opacity:0.8; margin-bottom:6px;">Direct link</div>
          <code style="display:block; padding:8px; background:#f7f7f7; border:1px solid #eee; border-radius:6px; word-break:break-all;">
            {{ token_url }}
          </code>
          <p style="margin-top:12px; font-size:13px; opacity:0.9;">
            If the QR fails to scan, open the link directly on the device you want to sign in.
          </p>
        </div>
      </div>

      <div style="margin-top: 16px; font-size: 12px; opacity: 0.7;">
        Tip: refresh the page to generate a new QR code. Each code is single-use and valid for 5 minutes.
      </div>
    </div>
  </AppLayout>
</template>
