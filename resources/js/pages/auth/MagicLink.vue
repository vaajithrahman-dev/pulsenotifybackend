<script setup lang="ts">
import { computed } from 'vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import AuthBase from '@/layouts/AuthLayout.vue'
import InputError from '@/components/InputError.vue'
import TextLink from '@/components/TextLink.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Spinner } from '@/components/ui/spinner'
import { login } from '@/routes'

defineProps<{
  status?: string | null
}>()

const form = useForm<{ email: string }>({
  email: '',
})

const page = usePage()
const flashSuccess = computed(() => (page.props as any).flash?.success ?? null)
const flashError = computed(() => (page.props as any).flash?.error ?? null)

function submit() {
  form.post('/magic-link', {
    preserveScroll: true,
    onSuccess: () => form.reset('email'),
  })
}
</script>

<template>
  <AuthBase
    title="Email me a magic link"
    description="We will send a one-time login link that expires in 15 minutes."
  >
    <Head title="Magic link login" />

    <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
      {{ status }}
    </div>
    <div v-if="flashSuccess" class="mb-4 rounded-md border border-green-500/70 px-3 py-2 text-sm text-green-700">
      {{ flashSuccess }}
    </div>
    <div v-if="flashError" class="mb-4 rounded-md border border-red-500/70 px-3 py-2 text-sm text-red-700">
      {{ flashError }}
    </div>

    <form class="flex flex-col gap-6" @submit.prevent="submit">
      <div class="grid gap-2">
        <Label for="email">Work email</Label>
        <Input
          id="email"
          type="email"
          name="email"
          v-model="form.email"
          required
          autofocus
          autocomplete="email"
          placeholder="you@example.com"
        />
        <InputError :message="form.errors.email" />
      </div>

      <Button type="submit" class="mt-2 w-full" :disabled="form.processing">
        <Spinner v-if="form.processing" />
        Send magic link
      </Button>

      <div class="text-center text-sm text-muted-foreground">
        Prefer your password?
        <TextLink :href="login()">Use password login</TextLink>
      </div>
    </form>
  </AuthBase>
</template>
