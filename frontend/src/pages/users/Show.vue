<script setup lang="ts">
import { computed, watchEffect } from 'vue';

import LoadState from '@/components/LoadState.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { setLayoutProps } from '@/composables/usePage';
import { useUser } from '@/composables/useUser';

defineOptions({ name: 'UsersShowPage' });

const { user, request, error, load } = useUser();

const fullName = computed(() =>
  user.value ? `${user.value.first_name} ${user.value.last_name}` : 'User'
);

watchEffect(() => {
  setLayoutProps({
    breadcrumbs: [
      { title: 'Dashboard', href: { name: 'dashboard' } },
      { title: 'Users', href: { name: 'users.index' } },
      { title: fullName.value }
    ]
  });
});

const details = computed(() =>
  user.value
    ? [
        { label: 'Email', value: user.value.email },
        { label: 'Mobile phone', value: user.value.mobile_phone ?? '—' },
        { label: 'Role', value: (user.value.roles ?? []).join(', ') || '—' },
        {
          label: 'Created',
          value: user.value.created_at
            ? new Date(user.value.created_at).toLocaleDateString()
            : '—'
        }
      ]
    : []
);
</script>

<template>
  <LoadState
    :loading="request.processing && !user"
    :error="error"
    @retry="load"
  >
    <template v-if="user">
      <PageHeader :title="fullName">
        <template #actions>
          <Badge
            v-if="user.email_verified_at"
            class="bg-success text-success-foreground"
          >
            Verified
          </Badge>
          <Badge v-else class="bg-warning text-warning-foreground">
            Unverified
          </Badge>
          <Button v-if="user.can.update" as-child>
            <RouterLink :to="{ name: 'users.edit', params: { id: user.id } }">
              Edit
            </RouterLink>
          </Button>
        </template>
      </PageHeader>
      <Card>
        <CardContent class="pt-6">
          <dl class="grid max-w-xl gap-4 sm:grid-cols-[10rem_1fr]">
            <template v-for="item in details" :key="item.label">
              <dt class="text-muted-foreground text-sm">{{ item.label }}</dt>
              <dd class="text-sm font-medium">{{ item.value }}</dd>
            </template>
          </dl>
        </CardContent>
      </Card>
    </template>
  </LoadState>
</template>
