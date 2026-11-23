<script setup lang="ts">
import { useRouter } from 'vue-router';

import { api } from '@/api';
import { Button } from '@/components/ui/button';
import { useForm } from '@/composables/useForm';
import { setLayoutProps } from '@/composables/usePage';
import { useAuthStore } from '@/stores/auth';

defineOptions({ name: 'VerifyEmailPage' });

setLayoutProps({
  title: 'Verify your email',
  description:
    'Click the link we emailed you to verify your address. If you did not receive it, we can send another.'
});

const auth = useAuthStore();
const router = useRouter();
const resend = useForm({});

function resendLink(): void {
  resend.post(api.verificationNotification());
}

async function logout(): Promise<void> {
  await auth.logout();
  await router.push({ name: 'login' });
}
</script>

<template>
  <div class="flex flex-col gap-3">
    <Button class="w-full" :disabled="resend.processing" @click="resendLink">
      Resend verification email
    </Button>
    <Button variant="link" class="px-0" @click="logout">Log out</Button>
  </div>
</template>
