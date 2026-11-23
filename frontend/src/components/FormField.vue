<script setup lang="ts">
import { computed, useId } from 'vue';
import { Label } from '@/components/ui/label';

defineOptions({ name: 'FormField' });

const props = withDefaults(
  defineProps<{
    label: string;
    id?: string;
    error?: string;
    hint?: string;
    required?: boolean;
  }>(),
  { id: undefined, error: '', hint: '', required: false }
);

defineSlots<{
  default(props: {
    id: string;
    describedBy: string | undefined;
    invalid: boolean;
  }): unknown;
}>();

const generatedId = useId();
const fieldId = computed(() => props.id ?? `field-${generatedId}`);
const hintId = computed(() => `${fieldId.value}-hint`);
const errorId = computed(() => `${fieldId.value}-error`);
const invalid = computed(() => props.error !== '');

const describedBy = computed(() => {
  if (invalid.value) {
    return errorId.value;
  }

  return props.hint ? hintId.value : undefined;
});
</script>

<template>
  <div class="flex flex-col gap-2">
    <Label :for="fieldId">
      {{ label }}
      <span v-if="required" class="text-destructive" aria-hidden="true">*</span>
    </Label>
    <slot :id="fieldId" :described-by="describedBy" :invalid="invalid" />
    <p
      v-if="hint && !invalid"
      :id="hintId"
      class="text-muted-foreground text-sm"
    >
      {{ hint }}
    </p>
    <p
      v-if="invalid"
      :id="errorId"
      role="alert"
      class="text-destructive text-sm"
    >
      {{ error }}
    </p>
  </div>
</template>
