<script setup lang="ts">
import { computed } from 'vue';

import PageHeader from '@/components/PageHeader.vue';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle
} from '@/components/ui/card';
import { setLayoutProps, usePage } from '@/composables/usePage';

defineOptions({ name: 'DashboardPage' });

setLayoutProps({ breadcrumbs: [{ title: 'Dashboard' }] });

const page = usePage();

const firstName = computed(() => page.props.auth.user?.first_name ?? '');
</script>

<template>
  <PageHeader title="Dashboard" :description="`Welcome back, ${firstName}.`" />
  <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <Card>
      <CardHeader>
        <CardTitle>Getting started</CardTitle>
        <CardDescription>
          Your workspace is ready. Build new modules on top of this starter.
        </CardDescription>
      </CardHeader>
      <CardContent class="text-muted-foreground text-sm">
        Use the sidebar to navigate between sections.
      </CardContent>
    </Card>
  </div>
</template>
