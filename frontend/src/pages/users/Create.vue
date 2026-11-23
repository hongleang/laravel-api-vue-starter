<script setup lang="ts">
import { useRouter } from 'vue-router';

import { api } from '@/api';
import PageHeader from '@/components/PageHeader.vue';
import UserForm from '@/components/UserForm.vue';
import { Card, CardContent } from '@/components/ui/card';
import { useForm } from '@/composables/useForm';
import { setLayoutProps } from '@/composables/usePage';
import type { ResourceResponse, UserFormData, UserRecord } from '@/types';

defineOptions({ name: 'UsersCreatePage' });

const router = useRouter();

setLayoutProps({
  breadcrumbs: [
    { title: 'Dashboard', href: { name: 'dashboard' } },
    { title: 'Users', href: { name: 'users.index' } },
    { title: 'New user' }
  ]
});

const form = useForm<UserFormData, ResourceResponse<UserRecord>>({
  first_name: '',
  last_name: '',
  mobile_phone: '',
  email: '',
  role: 'user'
});

function submit(): void {
  form.post(api.users.store(), {
    onSuccess: (response) =>
      router.push({ name: 'users.show', params: { id: response.data.id } })
  });
}

function cancel(): void {
  void router.push({ name: 'users.index' });
}
</script>

<template>
  <PageHeader
    title="New user"
    description="We will email them a link to set their password."
  />
  <Card>
    <CardContent class="pt-6">
      <UserForm
        v-model:form="form"
        :errors="form.errors"
        :processing="form.processing"
        submit-label="Create user"
        :on-cancel="cancel"
        @submit="submit"
      />
    </CardContent>
  </Card>
</template>
