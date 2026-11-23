<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router';

import { api } from '@/api';
import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useForm } from '@/composables/useForm';
import { setLayoutProps } from '@/composables/usePage';
import type { MessageResponse } from '@/types';

defineOptions({ name: 'ResetPasswordPage' });

setLayoutProps({
  title: 'Reset password',
  description: 'Choose a new password for your account.'
});

const route = useRoute();
const router = useRouter();

const form = useForm<
  { email: string; password: string; password_confirmation: string },
  MessageResponse
>({
  email: typeof route.query.email === 'string' ? route.query.email : '',
  password: '',
  password_confirmation: ''
});

function submit(): void {
  form
    .transform((data) => ({ ...data, token: String(route.params.token) }))
    .post(api.resetPassword(), {
      resetOnSuccess: ['password', 'password_confirmation'],
      onSuccess: () => router.push({ name: 'login' })
    });
}
</script>

<template>
  <form class="flex flex-col gap-4" @submit.prevent="submit">
    <FormField
      v-slot="{ id, describedBy, invalid }"
      label="Email"
      :error="form.errors.email"
    >
      <Input
        :id="id"
        v-model="form.email"
        type="email"
        required
        autocomplete="username"
        :aria-describedby="describedBy"
        :aria-invalid="invalid"
      />
    </FormField>
    <FormField
      v-slot="{ id, describedBy, invalid }"
      label="New password"
      :error="form.errors.password"
    >
      <Input
        :id="id"
        v-model="form.password"
        type="password"
        required
        autofocus
        autocomplete="new-password"
        :aria-describedby="describedBy"
        :aria-invalid="invalid"
      />
    </FormField>
    <FormField
      v-slot="{ id }"
      label="Confirm password"
      :error="form.errors.password_confirmation"
    >
      <Input
        :id="id"
        v-model="form.password_confirmation"
        type="password"
        required
        autocomplete="new-password"
      />
    </FormField>
    <Button type="submit" :disabled="form.processing">Reset password</Button>
  </form>
</template>
