<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router';

import { api } from '@/api';
import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@/composables/useForm';
import { setLayoutProps } from '@/composables/usePage';
import { useAuthStore } from '@/stores/auth';
import type { CurrentUser, ResourceResponse } from '@/types';

defineOptions({ name: 'LoginPage' });

setLayoutProps({
  title: 'Log in',
  description: 'Enter your email and password to continue.'
});

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

const form = useForm<
  { email: string; password: string; remember: boolean },
  ResourceResponse<CurrentUser>
>({
  email: import.meta.env.VITE_DEFAULT_LOGIN ?? '',
  password: import.meta.env.VITE_DEFAULT_PASSWORD ?? '',
  remember: false
});

/** Only follow redirects to paths inside the app. */
function redirectTarget(): string {
  const redirect = route.query.redirect;

  return typeof redirect === 'string' &&
    redirect.startsWith('/') &&
    !redirect.startsWith('//')
    ? redirect
    : '/dashboard';
}

function submit(): void {
  form.post(api.login(), {
    resetOnSuccess: ['password'],
    onError: () => form.reset('password'),
    onSuccess: async (response) => {
      auth.setUser(response.data);
      await router.push(redirectTarget());
    }
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
        autofocus
        autocomplete="username"
        :aria-describedby="describedBy"
        :aria-invalid="invalid"
      />
    </FormField>
    <FormField
      v-slot="{ id, describedBy, invalid }"
      label="Password"
      :error="form.errors.password"
    >
      <Input
        :id="id"
        v-model="form.password"
        type="password"
        required
        autocomplete="current-password"
        :aria-describedby="describedBy"
        :aria-invalid="invalid"
      />
    </FormField>
    <div class="flex items-center gap-2">
      <Checkbox id="remember" v-model="form.remember" />
      <Label for="remember">Remember me</Label>
    </div>
    <Button type="submit" :disabled="form.processing">Log in</Button>
    <RouterLink
      :to="{ name: 'password.request' }"
      class="text-muted-foreground hover:text-foreground text-sm underline"
    >
      Forgot your password?
    </RouterLink>
  </form>
</template>
