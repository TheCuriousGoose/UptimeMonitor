<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Edit2Icon } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import type { Role } from '@/types/admin';
import type { User } from '@/types/auth';

type UserWithRoles = User & { roles: Role[] };

const props = defineProps<{
    user: UserWithRoles;
    allRoles?: Role[];
}>();

const editOpen = ref(false);
const activeTab = ref('details');

// ---- Details & roles ----
const editName = ref('');
const editEmail = ref('');
const editRoles = ref<number[]>([]);
const roleSearch = ref('');

const filteredRoles = computed(() => {
    const query = roleSearch.value.trim().toLowerCase();

    if (!query) {
        return props.allRoles ?? [];
    }

    return (props.allRoles ?? []).filter((role) =>
        role.name.toLowerCase().includes(query),
    );
});

function toggleRole(id: number) {
    const idx = editRoles.value.indexOf(id);

    if (idx === -1) {
        editRoles.value.push(id);
    } else {
        editRoles.value.splice(idx, 1);
    }
}

function submitEdit() {
    router.put(
        UserController.update(props.user).url,
        {
            name: editName.value,
            email: editEmail.value,
            roles: editRoles.value,
        },
        {
            onSuccess: () => {
                editOpen.value = false;
            },
        },
    );
}

// ---- Password ----
const passwordForm = useForm({
    password: '',
    password_confirmation: '',
});

function submitPasswordReset() {
    passwordForm.put(UserController.resetPassword(props.user).url, {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
}

const sendingResetLink = ref(false);

function sendResetLink() {
    sendingResetLink.value = true;

    router.post(
        UserController.sendPasswordResetLink(props.user).url,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                sendingResetLink.value = false;
            },
        },
    );
}

function openEdit(user: UserWithRoles) {
    activeTab.value = 'details';
    editName.value = user.name;
    editEmail.value = user.email;
    editRoles.value = user.roles.map((r) => r.id);
    roleSearch.value = '';
    passwordForm.reset();
    passwordForm.clearErrors();
    editOpen.value = true;
}
</script>

<template>
    <Button
        variant="ghost"
        size="sm"
        @click="openEdit(user)"
        class="size-8 p-0"
    >
        <Edit2Icon class="size-4" />
    </Button>

    <Dialog v-model:open="editOpen">
        <DialogContent class="sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>{{
                    $t('admin.users.edit', { name: user.name })
                }}</DialogTitle>
            </DialogHeader>

            <Tabs v-model="activeTab" class="w-full">
                <TabsList class="grid w-full grid-cols-3">
                    <TabsTrigger value="details">{{
                        $t('admin.users.tabs.details')
                    }}</TabsTrigger>
                    <TabsTrigger value="roles">{{
                        $t('admin.users.tabs.roles')
                    }}</TabsTrigger>
                    <TabsTrigger value="password">{{
                        $t('admin.users.tabs.password')
                    }}</TabsTrigger>
                </TabsList>

                <TabsContent value="details" class="space-y-4 py-1">
                    <div class="grid gap-1.5">
                        <Label :for="`user-${user.id}-name`">{{
                            $t('admin.users.name')
                        }}</Label>
                        <Input
                            :id="`user-${user.id}-name`"
                            v-model="editName"
                        />
                    </div>
                    <div class="grid gap-1.5">
                        <Label :for="`user-${user.id}-email`">{{
                            $t('admin.users.email')
                        }}</Label>
                        <Input
                            :id="`user-${user.id}-email`"
                            v-model="editEmail"
                            type="email"
                        />
                    </div>
                    <div class="flex flex-wrap gap-2 pt-1">
                        <span class="text-xs text-muted-foreground">{{
                            $t('admin.users.current_roles')
                        }}</span>
                        <template v-if="user.roles.length === 0">
                            <span class="text-xs text-muted-foreground">{{
                                $t('admin.users.no_roles')
                            }}</span>
                        </template>
                        <Badge
                            v-for="role in user.roles"
                            :key="role.id"
                            :variant="
                                role.name === 'Super Admin'
                                    ? 'default'
                                    : 'secondary'
                            "
                            class="text-xs"
                        >
                            {{ role.name }}
                        </Badge>
                    </div>
                </TabsContent>

                <TabsContent value="roles" class="space-y-3">
                    <Input
                        v-model="roleSearch"
                        type="search"
                        :placeholder="$t('admin.users.search_roles')"
                        class="sm:max-w-xs"
                    />

                    <p
                        v-if="!filteredRoles.length"
                        class="text-sm text-muted-foreground"
                    >
                        {{
                            $t('admin.users.no_roles_match', {
                                search: roleSearch,
                            })
                        }}
                    </p>

                    <div
                        v-else
                        class="grid max-h-72 gap-2 overflow-y-auto pr-1"
                    >
                        <div
                            v-for="role in filteredRoles"
                            :key="role.id"
                            class="flex items-center gap-2"
                        >
                            <Checkbox
                                :id="`user-${user.id}-role-${role.id}`"
                                :checked="editRoles.includes(role.id)"
                                @update:checked="toggleRole(role.id)"
                            />
                            <Label
                                :for="`user-${user.id}-role-${role.id}`"
                                class="cursor-pointer text-sm font-normal"
                            >
                                {{ role.name }}
                            </Label>
                        </div>
                    </div>
                </TabsContent>

                <TabsContent value="password" class="space-y-6 py-1">
                    <div class="space-y-3">
                        <div>
                            <h4 class="text-sm font-semibold">
                                {{ $t('admin.users.password.heading') }}
                            </h4>
                            <p class="text-xs text-muted-foreground">
                                {{ $t('admin.users.password.description') }}
                            </p>
                        </div>
                        <div class="grid gap-1.5">
                            <Label :for="`user-${user.id}-password`">{{
                                $t('admin.users.password.new')
                            }}</Label>
                            <PasswordInput
                                :id="`user-${user.id}-password`"
                                v-model="passwordForm.password"
                                autocomplete="new-password"
                            />
                            <InputError
                                :message="passwordForm.errors.password"
                            />
                        </div>
                        <div class="grid gap-1.5">
                            <Label
                                :for="`user-${user.id}-password-confirmation`"
                                >{{
                                    $t('admin.users.password.confirmation')
                                }}</Label
                            >
                            <PasswordInput
                                :id="`user-${user.id}-password-confirmation`"
                                v-model="passwordForm.password_confirmation"
                                autocomplete="new-password"
                            />
                            <InputError
                                :message="
                                    passwordForm.errors.password_confirmation
                                "
                            />
                        </div>
                        <Button
                            size="sm"
                            :disabled="passwordForm.processing"
                            @click="submitPasswordReset"
                        >
                            {{ $t('admin.users.password.submit') }}
                        </Button>
                    </div>

                    <Separator />

                    <div class="space-y-3">
                        <div>
                            <h4 class="text-sm font-semibold">
                                {{ $t('admin.users.password.reset_heading') }}
                            </h4>
                            <p class="text-xs text-muted-foreground">
                                {{
                                    $t(
                                        'admin.users.password.reset_description',
                                        { email: user.email },
                                    )
                                }}
                            </p>
                        </div>
                        <Button
                            size="sm"
                            variant="outline"
                            :disabled="sendingResetLink"
                            @click="sendResetLink"
                        >
                            {{ $t('admin.users.password.reset_submit') }}
                        </Button>
                    </div>
                </TabsContent>
            </Tabs>

            <DialogFooter v-if="activeTab !== 'password'">
                <Button variant="ghost" @click="editOpen = false">{{
                    $t('base.cancel')
                }}</Button>
                <Button @click="submitEdit">{{
                    $t('admin.users.save')
                }}</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
