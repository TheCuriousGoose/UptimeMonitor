<script setup lang="ts">
import { Form, Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import OauthButtons from '@/components/auth/OAuthButtons.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { trans } from '@/lib/i18n';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Shield, User } from 'lucide-vue-next';

defineOptions({
    layout: {
        title: trans('auth.login.title'),
        description: trans('auth.login.description'),
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
    isLocal: boolean;
}>();

const devLoginLoading = ref<'admin' | 'user' | null>(null);

const devLoginAdminForm = useForm({});
const devLoginUserForm = useForm({});

const loginAsAdmin = () => {
    devLoginLoading.value = 'admin';
    devLoginAdminForm.post('/dev-login/admin');
};

const loginAsUser = () => {
    devLoginLoading.value = 'user';
    devLoginUserForm.post('/dev-login/user');
};
</script>

<template>

    <Head :title="$t('auth.login.page_title')" />

    <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
        {{ status }}
    </div>

    <Form v-bind="store.form()" :reset-on-success="['password']" v-slot="{ errors, processing }"
        class="flex flex-col gap-6">

        <!-- Dev Quick Login Buttons -->
        <div v-if="isLocal" class="grid grid-cols-2 gap-2">
            <Button type="button" variant="outline" class="text-xs" @click="loginAsAdmin"
                :disabled="devLoginLoading !== null">
                <Spinner v-if="devLoginLoading === 'admin'" class="mr-1" />
                <span v-else class="flex gap-2">
                    <Shield />
                    Admin
                </span>
            </Button>
            <Button type="button" variant="outline" class="text-xs" @click="loginAsUser"
                :disabled="devLoginLoading !== null">
                <Spinner v-if="devLoginLoading === 'user'" class="mr-1" />
                <span v-else class="flex gap-2">
                    <User />
                    User</span>
            </Button>
        </div>

        <OauthButtons />

        <div class="grid gap-6">
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <Separator class="w-full" />
                </div>
                <div class="relative flex justify-center text-xs uppercase">
                    <span class="bg-background px-2 text-muted-foreground">{{ $t('auth.login.or') }}</span>
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="email">{{ $t('auth.login.email_label') }}</Label>
                <Input id="email" type="email" name="email" required autofocus :tabindex="1" autocomplete="email"
                    :placeholder="$t('auth.login.email_placeholder')" />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password">{{ $t('auth.login.password_label') }}</Label>
                    <TextLink v-if="canResetPassword" :href="request()" class="text-sm" :tabindex="5">
                        {{ $t('auth.login.forgot_password') }}
                    </TextLink>
                </div>
                <PasswordInput id="password" name="password" required :tabindex="2" autocomplete="current-password"
                    :placeholder="$t('auth.login.password_placeholder')" />
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <Label for="remember" class="flex items-center space-x-3">
                    <Checkbox id="remember" name="remember" :tabindex="3" />
                    <span>{{ $t('auth.login.remember_me') }}</span>
                </Label>
            </div>

            <Button type="submit" class="mt-4 w-full" :tabindex="4" :disabled="processing" data-test="login-button">
                <Spinner v-if="processing" />
                {{ $t('auth.login.submit') }}
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground" v-if="canRegister">
            {{ $t('auth.login.no_account') }}
            <TextLink :href="register()" :tabindex="5">{{ $t('auth.login.sign_up') }}</TextLink>
        </div>
    </Form>
</template>
