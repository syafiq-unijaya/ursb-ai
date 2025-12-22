<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';

const { users } = defineProps<{ users: any }>();

const csrfToken = (typeof window !== 'undefined' && (window as any).csrfToken)
    ? (window as any).csrfToken
    : (document.querySelector('meta[name=csrf-token]')?.getAttribute('content') ?? '');
</script>

<template>
    <AppLayout>
        <Head title="Users" />

        <div class="flex items-center justify-between mb-6">
            <Heading title="Users" description="Manage users: add, edit or delete." />

            <Link href="/users/create">
                <Button>New user</Button>
            </Link>
        </div>

        <div class="bg-card border rounded-md shadow-sm overflow-x-auto">
            <table class="min-w-full divide-y divide-border">
                <thead class="bg-muted">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-muted-foreground">Name</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-muted-foreground">Email</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-muted-foreground">Created</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-muted-foreground">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <template v-if="users && users.data && users.data.length">
                        <tr v-for="user in users.data" :key="user.id">
                            <td class="px-6 py-3 text-card-foreground">{{ user.name }}</td>
                            <td class="px-6 py-3 text-card-foreground">{{ user.email }}</td>
                            <td class="px-6 py-3 text-card-foreground">{{ new Date(user.created_at).toLocaleString() }}</td>
                            <td class="px-6 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Link :href="`/users/${user.id}`" class="text-sm text-muted-foreground">View</Link>
                                    <Link :href="`/users/${user.id}/edit`" class="text-sm text-primary">Edit</Link>

                                    <form :action="`/users/${user.id}`" method="post" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                        <input type="hidden" name="_method" value="DELETE" />
                                        <input type="hidden" name="_token" :value="csrfToken" />
                                        <button type="submit" class="text-sm text-destructive">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <tr v-else>
                        <td colspan="4" class="px-6 py-3 text-center text-sm text-muted-foreground">No users found</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            <nav class="flex items-center justify-center sm:justify-end space-x-2 w-full">
                <Link v-if="users?.prev_page_url" :href="users.prev_page_url" class="px-3 py-1 border rounded">Prev</Link>
                <span class="px-3 py-1">Page {{ users?.current_page ?? 0 }} of {{ users?.last_page ?? 0 }}</span>
                <Link v-if="users?.next_page_url" :href="users.next_page_url" class="px-3 py-1 border rounded">Next</Link>
            </nav>
        </div>


    </AppLayout>
</template>
