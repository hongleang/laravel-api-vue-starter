<script setup lang="ts">
import { computed, watch, watchEffect } from 'vue';
import { useRouter } from 'vue-router';

import { api } from '@/api';
import LoadState from '@/components/LoadState.vue';
import PageHeader from '@/components/PageHeader.vue';
import UserForm from '@/components/UserForm.vue';
import { Card, CardContent } from '@/components/ui/card';
import { useForm } from '@/composables/useForm';
import { setLayoutProps } from '@/composables/usePage';
import { useUser } from '@/composables/useUser';
import { useAuthStore } from '@/stores/auth';
import type { ResourceResponse, UserFormData, UserRecord } from '@/types';

defineOptions({ name: 'UsersEditPage' });

const router = useRouter();
const auth = useAuthStore();
const { user, request, error, load } = useUser();

const form = useForm<UserFormData, ResourceResponse<UserRecord>>({
  first_name: '',
  last_name: '',
  mobile_phone: '',
  email: '',
  role: 'user'
});

/** People cannot change their own role; the API rejects it, so the field is hidden. */
const isSelf = computed(() => user.value?.id === auth.user?.id);

watch(user, (value) => {
  if (!value) {
    return;
  }

  form.defaults({
    first_name: value.first_name,
    last_name: value.last_name,
    mobile_phone: value.mobile_phone ?? '',
    email: value.email,
    role: value.roles?.[0] ?? 'user'
  });
  form.reset();
});

watchEffect(() => {
  setLayoutProps({
    breadcrumbs: [
      { title: 'Dashboard', href: { name: 'dashboard' } },
      { title: 'Users', href: { name: 'users.index' } },
      {
        title: user.value
          ? `${user.value.first_name} ${user.value.last_name}`
          : 'User',
        href: user.value
          ? { name: 'users.show', params: { id: user.value.id } }
          : undefined
      },
      { title: 'Edit' }
    ]
  });
});

function submit(): void {
  if (!user.value) {
    return;
  }

  const id = user.value.id;

  form
    .transform((data) => {
      const payload: Record<string, unknown> = { ...data };

      if (isSelf.value) {
        delete payload.role;
      }

      return payload;
    })
    .put(api.users.update(id), {
      onSuccess: async (response) => {
        if (isSelf.value) {
          await auth.load(true);
        }

        await router.push({
          name: 'users.show',
          params: { id: response.data.id }
        });
      }
    });
}

function cancel(): void {
  if (user.value) {
    void router.push({ name: 'users.show', params: { id: user.value.id } });
  }
}
</script>

<template>
  <LoadState
    :loading="request.processing && !user"
    :error="error"
    @retry="load"
  >
    <template v-if="user">
      <PageHeader title="Edit user" />
      <Card>
        <CardContent class="pt-6">
          <UserForm
            v-model:form="form"
            :errors="form.errors"
            :processing="form.processing"
            :show-role="!isSelf"
            submit-label="Save changes"
            :on-cancel="cancel"
            @submit="submit"
          />
        </CardContent>
      </Card>
    </template>
  </LoadState>
</template>
