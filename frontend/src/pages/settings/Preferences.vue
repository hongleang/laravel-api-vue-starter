<script setup lang="ts">
import FormField from '@/components/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import SettingsNav from '@/components/SettingsNav.vue';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle
} from '@/components/ui/card';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select';
import { useAppearance } from '@/composables/useAppearance';
import type { Appearance } from '@/composables/useAppearance';
import { setLayoutProps } from '@/composables/usePage';

defineOptions({ name: 'SettingsPreferencesPage' });

setLayoutProps({
  breadcrumbs: [{ title: 'Settings' }, { title: 'Preferences' }]
});

const { appearance, updateAppearance } = useAppearance();

const options: { value: Appearance; label: string }[] = [
  { value: 'system', label: 'System' },
  { value: 'light', label: 'Light' },
  { value: 'dark', label: 'Dark' }
];

function onChange(value: unknown): void {
  if (value === 'system' || value === 'light' || value === 'dark') {
    updateAppearance(value);
  }
}
</script>

<template>
  <PageHeader title="Settings" description="Manage your account." />
  <SettingsNav current="preferences" />

  <Card>
    <CardHeader>
      <CardTitle>Appearance</CardTitle>
      <CardDescription>
        Saved in this browser only. It does not follow you to other devices.
      </CardDescription>
    </CardHeader>
    <CardContent class="max-w-xl">
      <FormField
        v-slot="{ id }"
        label="Theme"
        hint="System follows your device setting."
      >
        <Select :model-value="appearance" @update:model-value="onChange">
          <SelectTrigger :id="id" class="w-full sm:w-64">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem
              v-for="option in options"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </SelectItem>
          </SelectContent>
        </Select>
      </FormField>
    </CardContent>
  </Card>
</template>
