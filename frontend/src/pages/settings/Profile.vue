<script setup lang="ts">
import { api } from '@/api';
import FormField from '@/components/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import SettingsNav from '@/components/SettingsNav.vue';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useForm } from '@/composables/useForm';
import { setLayoutProps } from '@/composables/usePage';
import { useAuthStore } from '@/stores/auth';
import type { CurrentUser, ResourceResponse } from '@/types';

defineOptions({ name: 'SettingsProfilePage' });

const auth = useAuthStore();

setLayoutProps({
  breadcrumbs: [{ title: 'Settings' }, { title: 'Profile' }]
});

const profileForm = useForm<
  {
    first_name: string;
    last_name: string;
    mobile_phone: string;
    email: string;
  },
  ResourceResponse<CurrentUser>
>({
  first_name: auth.user?.first_name ?? '',
  last_name: auth.user?.last_name ?? '',
  mobile_phone: auth.user?.mobile_phone ?? '',
  email: auth.user?.email ?? ''
});

const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: ''
});

function saveProfile(): void {
  profileForm.patch(api.profile(), {
    onSuccess: (response) => {
      auth.setUser(response.data);
      profileForm.defaults();
    }
  });
}

function savePassword(): void {
  passwordForm.put(api.profilePassword(), {
    onSuccess: () => {
      passwordForm.reset();
    },
    onError: () => passwordForm.reset('password', 'password_confirmation')
  });
}
</script>

<template>
  <PageHeader title="Settings" description="Manage your account." />
  <SettingsNav current="profile" />

  <Card>
    <CardHeader>
      <CardTitle>Profile</CardTitle>
      <CardDescription>
        Changing your email address requires you to verify it again.
      </CardDescription>
    </CardHeader>
    <CardContent>
      <form class="flex max-w-xl flex-col gap-4" @submit.prevent="saveProfile">
        <div class="grid gap-4 sm:grid-cols-2">
          <FormField
            v-slot="{ id, describedBy, invalid }"
            label="First name"
            :error="profileForm.errors.first_name"
            required
          >
            <Input
              :id="id"
              v-model="profileForm.first_name"
              autocomplete="given-name"
              required
              :aria-describedby="describedBy"
              :aria-invalid="invalid"
            />
          </FormField>
          <FormField
            v-slot="{ id, describedBy, invalid }"
            label="Last name"
            :error="profileForm.errors.last_name"
            required
          >
            <Input
              :id="id"
              v-model="profileForm.last_name"
              autocomplete="family-name"
              required
              :aria-describedby="describedBy"
              :aria-invalid="invalid"
            />
          </FormField>
        </div>
        <FormField
          v-slot="{ id, describedBy, invalid }"
          label="Email"
          :error="profileForm.errors.email"
          required
        >
          <Input
            :id="id"
            v-model="profileForm.email"
            type="email"
            autocomplete="email"
            required
            :aria-describedby="describedBy"
            :aria-invalid="invalid"
          />
        </FormField>
        <FormField
          v-slot="{ id, describedBy, invalid }"
          label="Mobile phone"
          :error="profileForm.errors.mobile_phone"
        >
          <Input
            :id="id"
            v-model="profileForm.mobile_phone"
            type="tel"
            autocomplete="tel"
            :aria-describedby="describedBy"
            :aria-invalid="invalid"
          />
        </FormField>
        <div>
          <Button type="submit" :disabled="profileForm.processing">
            Save profile
          </Button>
        </div>
      </form>
    </CardContent>
  </Card>

  <Card>
    <CardHeader>
      <CardTitle>Password</CardTitle>
      <CardDescription>Use a long, unique password.</CardDescription>
    </CardHeader>
    <CardContent>
      <form class="flex max-w-xl flex-col gap-4" @submit.prevent="savePassword">
        <FormField
          v-slot="{ id, describedBy, invalid }"
          label="Current password"
          :error="passwordForm.errors.current_password"
          required
        >
          <Input
            :id="id"
            v-model="passwordForm.current_password"
            type="password"
            autocomplete="current-password"
            required
            :aria-describedby="describedBy"
            :aria-invalid="invalid"
          />
        </FormField>
        <FormField
          v-slot="{ id, describedBy, invalid }"
          label="New password"
          :error="passwordForm.errors.password"
          required
        >
          <Input
            :id="id"
            v-model="passwordForm.password"
            type="password"
            autocomplete="new-password"
            required
            :aria-describedby="describedBy"
            :aria-invalid="invalid"
          />
        </FormField>
        <FormField v-slot="{ id }" label="Confirm new password" required>
          <Input
            :id="id"
            v-model="passwordForm.password_confirmation"
            type="password"
            autocomplete="new-password"
            required
          />
        </FormField>
        <div>
          <Button type="submit" :disabled="passwordForm.processing">
            Update password
          </Button>
        </div>
      </form>
    </CardContent>
  </Card>
</template>
