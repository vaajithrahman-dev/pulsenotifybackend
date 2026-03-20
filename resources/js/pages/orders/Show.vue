<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { dashboard } from '@/routes'
import type { BreadcrumbItem } from '@/types'
import { useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard() },
]

type Address = {
  first_name?: string | null
  last_name?: string | null
  email?: string | null
  phone?: string | null
  address_1?: string | null
  address_2?: string | null
  city?: string | null
  state?: string | null
  postcode?: string | null
  country?: string | null
}

type LineItem = {
  product_id?: number | null
  name?: string | null
  quantity?: number | null
  subtotal?: string | number | null
  total?: string | number | null
}

type Order = {
  order_id: number
  order_number?: string | null
  status?: string | null
  currency?: string | null
  total?: string | number | null
  subtotal?: string | number | null
  discount_total?: string | number | null
  shipping_total?: string | number | null
  tax_total?: string | number | null
  payment_method_title?: string | null
  coupon_codes?: string | null
  created_at_gmt?: string | null
  paid_at_gmt?: string | null
  modified_at_gmt?: string | null
  billing_email?: string | null
  billing_first_name?: string | null
  billing_last_name?: string | null
}

type Snapshot = {
  billing?: Address
  shipping?: Address
  line_items?: LineItem[]
  coupon_codes?: string[] | string | null
  [key: string]: unknown
}

const props = defineProps<{
  order: Order
  snapshot: Snapshot
  notes: Array<{
    id: number
    note: string
    customer_note: boolean
    actor_user_id: number | null
    created_at: string

  }>
}>()

const storeRole = computed(() => {
  const tenant = (usePage().props as any).tenant
  return tenant?.active_store_role || null
})

const canUpdateStatus = computed(() => storeRole.value === 'owner')
const canAddNote = computed(() => storeRole.value === 'owner' || storeRole.value === 'staff')
const canSendCustomerNote = computed(() => storeRole.value === 'owner')


const page = usePage()
const flashSuccess = computed(() => (page.props as any).flash?.success ?? null)
const flashError = computed(() => (page.props as any).flash?.error ?? null)

const statusForm = useForm<{ status: string }>({
  status: props.order.status ?? 'processing',
})

const noteForm = useForm<{ note: string; customer_note: boolean }>({
  note: '',
  customer_note: false,
})
</script>


<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div style="padding: 24px">
      <a href="/app/orders" style="text-decoration: underline;">← Back to Orders</a>

      <h1 style="font-size: 20px; font-weight: 600; margin: 12px 0;">
        Order {{ order?.order_number || order?.order_id || '-' }}
      </h1>

      <div style="display:flex; gap:16px; flex-wrap:wrap; margin-bottom:16px;">
        <div style="border:1px solid #ddd; padding:12px; border-radius:8px; min-width:220px;">
          <div><b>Status:</b> {{ order?.status || '-' }}</div>
          <div><b>Total:</b> {{ order?.currency || '' }} {{ order?.total ?? '-' }}</div>
          <div><b>Payment:</b> {{ order?.payment_method_title || '-' }}</div>
          <div><b>Coupons:</b> {{ order?.coupon_codes || '-' }}</div>
        </div>

        <div style="border:1px solid #ddd; padding:12px; border-radius:8px; min-width:220px;">
          <div><b>Created:</b> {{ order?.created_at_gmt || '-' }}</div>
          <div><b>Paid:</b> {{ order?.paid_at_gmt || '-' }}</div>
          <div><b>Modified:</b> {{ order?.modified_at_gmt || '-' }}</div>
        </div>
      </div>

      <div style="margin: 16px 0;">
  <div v-if="flashSuccess" style="padding:10px;border:1px solid #2d7;border-radius:8px;margin-bottom:10px;">
    {{ flashSuccess }}
  </div>
  <div v-if="flashError" style="padding:10px;border:1px solid #d22;border-radius:8px;margin-bottom:10px;">
    {{ flashError }}
  </div>

  <div style="display:flex; gap:16px; flex-wrap:wrap;">
    <div style="border:1px solid #ddd; padding:12px; border-radius:8px; min-width:260px;">
      <h3 style="margin:0 0 8px;">Update Status</h3>

      <select v-model="statusForm.status">
        <option value="pending">pending</option>
        <option value="processing">processing</option>
        <option value="completed">completed</option>
        <option value="on-hold">on-hold</option>
        <option value="cancelled">cancelled</option>
        <option value="refunded">refunded</option>
        <option value="failed">failed</option>
      </select>

      <button
        style="margin-left:8px;"
        :disabled="statusForm.processing || !canUpdateStatus"
        @click.prevent="statusForm.post(`/app/orders/${order.order_id}/status`, { preserveScroll: true })"
      >
        Update
      </button>
      <div v-if="!canUpdateStatus" style="margin-top:8px;color:#999;font-size:12px;">
        Only owners can update order status.
      </div>

      <div v-if="statusForm.errors.status" style="margin-top:8px;color:#d22;">
        {{ statusForm.errors.status }}
      </div>
    </div>

    <div style="border:1px solid #ddd; padding:12px; border-radius:8px; flex:1; min-width:300px;">
      <h3 style="margin:0 0 8px;">Add Note</h3>

      <textarea v-model="noteForm.note" rows="3" style="width:100%;"></textarea>

      <label style="display:block;margin-top:8px;">
        <input type="checkbox" v-model="noteForm.customer_note" :disabled="!canSendCustomerNote" />
        Customer note
      </label>

      <button
        style="margin-top:8px;"
        :disabled="noteForm.processing || !canAddNote"
        @click.prevent="noteForm.post(`/app/orders/${order.order_id}/notes`, { preserveScroll: true, onSuccess: () => { noteForm.note = '' } })"
      >
        Add Note
      </button>
      <div v-if="!canAddNote" style="margin-top:8px;color:#999;font-size:12px;">
        You do not have permission to add notes for this store.
      </div>

      <div v-if="noteForm.errors.note" style="margin-top:8px;color:#d22;">
        {{ noteForm.errors.note }}
      </div>
    </div>
  </div>
</div>

      <div
        style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:16px; margin-bottom:16px;">
        <div style="border:1px solid #ddd; padding:12px; border-radius:8px;">
          <h3 style="margin:0 0 8px;">Billing</h3>
          <div v-if="snapshot?.billing">
            <div>
              {{ snapshot.billing?.first_name || '' }}
              {{ snapshot.billing?.last_name || '' }}
            </div>
            <div>{{ snapshot.billing?.email || '-' }}</div>
            <div>{{ snapshot.billing?.phone || '-' }}</div>
            <div>{{ snapshot.billing?.address_1 || '-' }}</div>
            <div v-if="snapshot.billing?.address_2">
              {{ snapshot.billing.address_2 }}
            </div>
            <div>
              {{ snapshot.billing?.city || '-' }},
              {{ snapshot.billing?.state || '-' }}
              {{ snapshot.billing?.postcode || '' }}
            </div>
            <div>{{ snapshot.billing?.country || '-' }}</div>
          </div>
          <div v-else>-</div>
        </div>

        <div style="border:1px solid #ddd; padding:12px; border-radius:8px;">
          <h3 style="margin:0 0 8px;">Shipping</h3>
          <div v-if="snapshot?.shipping">
            <div>
              {{ snapshot.shipping?.first_name || '' }}
              {{ snapshot.shipping?.last_name || '' }}
            </div>
            <div>{{ snapshot.shipping?.address_1 || '-' }}</div>
            <div v-if="snapshot.shipping?.address_2">
              {{ snapshot.shipping.address_2 }}
            </div>
            <div>
              {{ snapshot.shipping?.city || '-' }},
              {{ snapshot.shipping?.state || '-' }}
              {{ snapshot.shipping?.postcode || '' }}
            </div>
            <div>{{ snapshot.shipping?.country || '-' }}</div>
          </div>
          <div v-else>-</div>
        </div>
      </div>

      <div style="border:1px solid #ddd; padding:12px; border-radius:8px;">
        <h3 style="margin:0 0 8px;">Line Items</h3>

        <div v-if="Array.isArray(snapshot?.line_items) && snapshot.line_items.length">
          <table border="1" cellspacing="0" cellpadding="10" style="border-collapse: collapse; width: 100%;">
            <thead>
              <tr>
                <th>Product</th>
                <th>Qty</th>
                <th>Subtotal</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(li, idx) in snapshot.line_items" :key="li?.product_id || idx">
                <td>{{ li?.name || '-' }}</td>
                <td>{{ li?.quantity ?? '-' }}</td>
                <td>{{ li?.subtotal ?? '-' }}</td>
                <td>{{ li?.total ?? '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else>-</div>
      </div>


      <div style="margin-top:16px; border:1px solid #ddd; padding:12px; border-radius:8px;">
  <h3 style="margin:0 0 8px;">Notes</h3>
  <div v-if="notes.length === 0">-</div>
  <div v-else>
    <div v-for="n in notes" :key="n.id" style="padding:10px;border:1px solid #eee;border-radius:8px;margin-bottom:8px;">
      <div style="font-size:12px;opacity:0.8;">
        {{ n.created_at }} • <b>{{ n.customer_note ? 'Customer' : 'Internal' }}</b>
      </div>
      <div style="margin-top:6px; white-space: pre-wrap;">{{ n.note }}</div>
    </div>
  </div>
</div>
    </div>
  </AppLayout>
</template>
