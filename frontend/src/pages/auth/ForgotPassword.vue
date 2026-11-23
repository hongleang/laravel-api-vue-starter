<script setup lang="ts">
import { api } from '@/api';
import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useForm } from '@/composables/useForm';
import { setLayoutProps } from '@/composables/usePage';
import type { MessageResponse } from '@/types';

defineOptions({ name: 'ForgotPasswordPage' });

setLayoutProps({
  title: 'Forgot your password?',
  description: 'Enter your email and we will send you a reset link.'
});

const form = useForm<{ email: string }, MessageResponse>({ email: '' });

function submit(): void {
  form.post(api.forgotPassword());
}
</script>

<template>
  <p
    v-if="form.wasSuccessful && form.response"
    class="text-success mb-4 text-sm"
    role="status"
  >
    {{ form.response.message }}
  </p>
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
        autofocus
        autocomplete="username"
        :aria-describedby="describedBy"
        :aria-invalid="invalid"
      />
    </FormField>
    <Button type="submit" :disabled="form.processing">Email reset link</Button>
    <RouterLink
      :to="{ name: 'login' }"
      class="text-muted-foreground hover:text-foreground text-sm underline"
    >
      Back to log in
    </RouterLink>
  </form>
</template>
