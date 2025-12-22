<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';

const { cars } = defineProps<{ cars: any }>();
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const csrfToken = (typeof window !== 'undefined' && (window as any).csrfToken)
    ? (window as any).csrfToken
    : (document.querySelector('meta[name=csrf-token]')?.getAttribute('content') ?? '');
</script>

<template>
    <AppLayout>
        <Head title="Cars" />

        <div class="flex items-center justify-between mb-6">
            <Heading title="Cars" description="Manage cars: add, edit or delete." />

            <Link href="/cars/create">
                <Button>New car</Button>
            </Link>
        </div>

        <div class="bg-card border rounded-md shadow-sm overflow-x-auto">
            <table class="min-w-full divide-y divide-border">
                <thead class="bg-muted">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-muted-foreground">Brand</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-muted-foreground">Model</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-muted-foreground">Variant</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-muted-foreground">Year</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-muted-foreground">Owner</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-muted-foreground">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <template v-if="cars && cars.data && cars.data.length">
                        <tr v-for="car in cars.data" :key="car.id">
                            <td class="px-6 py-3 text-card-foreground">{{ car.brand }}</td>
                            <td class="px-6 py-3 text-card-foreground">{{ car.model }}</td>
                            <td class="px-6 py-3 text-card-foreground">{{ car.variant ?? '-' }}</td>
                            <td class="px-6 py-3 text-card-foreground">{{ car.year ?? '-' }}</td>
                            <td class="px-6 py-3 text-card-foreground">{{ car.user?.name ?? '-' }}</td>
                            <td class="px-6 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Link :href="`/cars/${car.id}`" class="text-sm text-muted-foreground">View</Link>
                                    <Link v-if="page.props.auth?.user?.id === car.user_id" :href="`/cars/${car.id}/edit`" class="text-sm text-primary">Edit</Link>

                                    <form v-if="page.props.auth?.user?.id === car.user_id" :action="`/cars/${car.id}`" method="post" onsubmit="return confirm('Are you sure you want to delete this car?')">
                                        <input type="hidden" name="_method" value="DELETE" />
                                        <input type="hidden" name="_token" :value="csrfToken" />
                                        <button type="submit" class="text-sm text-destructive">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <tr v-else>
                        <td colspan="6" class="px-6 py-3 text-center text-sm text-muted-foreground">No cars found</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            <nav class="flex items-center justify-center sm:justify-end space-x-2 w-full">
                <Link v-if="cars?.prev_page_url" :href="cars.prev_page_url" class="px-3 py-1 border rounded">Prev</Link>
                <span class="px-3 py-1">Page {{ cars?.current_page ?? 0 }} of {{ cars?.last_page ?? 0 }}</span>
                <Link v-if="cars?.next_page_url" :href="cars.next_page_url" class="px-3 py-1 border rounded">Next</Link>
            </nav>
        </div>

    </AppLayout>
</template>
