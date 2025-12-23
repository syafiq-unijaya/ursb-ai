<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';

const props = defineProps<{ user: any }>();
import { ref } from 'vue';

const showForm = ref(false);
const selectedCarId = ref(props.user.available_cars?.[0]?.id ?? null);
const newPlate = ref('');

const csrfToken = (typeof window !== 'undefined' && (window as any).csrfToken)
    ? (window as any).csrfToken
    : (document.querySelector('meta[name=csrf-token]')?.getAttribute('content') ?? '');
</script>

<template>
    <AppLayout>
        <Head :title="`User: ${props.user.name}`" />

        <div class="mb-6">
            <Heading :title="props.user.name" description="User details" />
        </div>

        <div class="bg-card border rounded-md p-6 shadow-sm">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-muted-foreground">Name</dt>
                    <dd class="mt-1 text-base text-card-foreground">{{ props.user.name }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted-foreground">Email</dt>
                    <dd class="mt-1 text-base text-card-foreground">{{ props.user.email }}</dd>
                </div>

                <div>
                    <dt class="text-sm text-muted-foreground">Phone</dt>
                    <dd class="mt-1 text-base text-card-foreground">{{ props.user.phone_no ?? '-' }}</dd>
                </div>

                <div>
                    <dt class="text-sm text-muted-foreground">Created</dt>
                    <dd class="mt-1 text-base text-card-foreground">{{ new Date(props.user.created_at).toLocaleString() }}</dd>
                </div>
            </dl>
        </div>

        <!-- Cars -->
        <div class="mt-6 bg-card border rounded-md p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-medium">Cars</h3>
                    <p class="text-sm text-muted-foreground">Cars owned by this user</p>
                </div>
                <div v-if="props.user.is_current_user">
                    <button @click="showForm = !showForm" class="btn btn-primary">Add Car</button>
                </div>
            </div>

            <div v-if="showForm" class="mb-4">
                <form :action="`/cars/${selectedCarId}/owners`" method="post" class="flex gap-2 items-end">
                    <input type="hidden" name="_token" :value="csrfToken" />

                    <div>
                        <label class="text-sm text-muted-foreground">Model</label>
                        <select v-model="selectedCarId" name="car_id" class="block mt-1">
                            <option v-for="c in props.user.available_cars" :key="c.id" :value="c.id">{{ c.brand }} {{ c.model }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm text-muted-foreground">Plate</label>
                        <input name="plate" v-model="newPlate" class="block mt-1" placeholder="Plate number" />
                    </div>

                    <div>
                        <button type="submit" class="btn btn-primary">Add</button>
                        <button type="button" @click="showForm = false" class="ml-2 btn">Cancel</button>
                    </div>
                </form>
            </div>

            <template v-if="props.user.cars && props.user.cars.length">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr>
                                <th class="px-4 py-2">Brand</th>
                                <th class="px-4 py-2">Model</th>
                                <th class="px-4 py-2">Variant</th>
                                <th class="px-4 py-2">Year</th>
                                <th class="px-4 py-2">Plate</th>
                                <th class="px-4 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="car in props.user.cars" :key="car.id">
                                <td class="px-4 py-2">{{ car.brand }}</td>
                                <td class="px-4 py-2">{{ car.model }}</td>
                                <td class="px-4 py-2">{{ car.variant ?? '-' }}</td>
                                <td class="px-4 py-2">{{ car.year ?? '-' }}</td>
                                <td class="px-4 py-2">{{ car.plate ?? '-' }}</td>
                                <td class="px-4 py-2">
                                    <Link :href="`/cars/${car.id}`" class="text-sm text-muted-foreground">View</Link>
                                    <Link v-if="props.user.is_current_user" :href="`/cars/${car.id}/edit`" class="ml-3 text-sm text-primary">Edit</Link>
                                    <form v-if="props.user.is_current_user" :action="`/cars/${car.id}/owners`" method="post" onsubmit="return confirm('Remove ownership?')" class="inline">
                                        <input type="hidden" name="_token" :value="csrfToken" />
                                        <input type="hidden" name="_method" value="DELETE" />
                                        <button type="submit" class="ml-3 text-sm text-destructive">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
            <div v-else class="text-muted-foreground">No cars found</div>
        </div>
    </AppLayout>
</template>
