<script setup lang="ts">
import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select';
import type { Role, UserFormData } from '@/types';

defineOptions({ name: 'UserForm' });

const form = defineModel<UserFormData>('form', { required: true });

withDefaults(
  defineProps<{
    errors: Partial<Record<keyof UserFormData, string>>;
    processing: boolean;
    submitLabel: string;
    onCancel: () => void;
    /** Hidden when editing yourself: the API does not let anyone change their own role. */
    showRole?: boolean;
  }>(),
  { showRole: true }
);

const roles: { value: Role; label: string; description: string }[] = [
  {
    value: 'user',
    label: 'User',
    description: 'Can sign in and manage their own profile.'
  },
  {
    value: 'admin',
    label: 'Admin',
    description: 'Can also view, create, edit and delete users.'
  }
];

const emit = defineEmits<{
  submit: [];
}>();
</script>

<template>
  <form class="flex max-w-xl flex-col gap-4" @submit.prevent="emit('submit')">
    <div class="grid gap-4 sm:grid-cols-2">
      <FormField
        v-slot="{ id, describedBy, invalid }"
        label="First name"
        :error="errors.first_name"
        required
      >
        <Input
          :id="id"
          v-model="form.first_name"
          autocomplete="given-name"
          required
          :aria-describedby="describedBy"
          :aria-invalid="invalid"
        />
      </FormField>
      <FormField
        v-slot="{ id, describedBy, invalid }"
        label="Last name"
        :error="errors.last_name"
        required
      >
        <Input
          :id="id"
          v-model="form.last_name"
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
      :error="errors.email"
      required
    >
      <Input
        :id="id"
        v-model="form.email"
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
      :error="errors.mobile_phone"
    >
      <Input
        :id="id"
        v-model="form.mobile_phone"
        type="tel"
        autocomplete="tel"
        :aria-describedby="describedBy"
        :aria-invalid="invalid"
      />
    </FormField>
    <FormField
      v-if="showRole"
      v-slot="{ id, describedBy, invalid }"
      label="Role"
      :error="errors.role"
      required
    >
      <Select v-model="form.role">
        <SelectTrigger
          :id="id"
          class="w-full sm:w-64"
          :aria-describedby="describedBy"
          :aria-invalid="invalid"
        >
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          <SelectItem
            v-for="role in roles"
            :key="role.value"
            :value="role.value"
          >
            {{ role.label }}
            <span class="text-muted-foreground text-xs"
              >— {{ role.description }}</span
            >
          </SelectItem>
        </SelectContent>
      </Select>
    </FormField>
    <div class="flex items-center gap-2">
      <Button type="submit" :disabled="processing">
        {{ submitLabel }}
      </Button>
      <Button type="button" variant="outline" @click="onCancel()">
        Cancel
      </Button>
    </div>
  </form>
</template>
