<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';

const props = defineProps<{ car: any }>();
</script>

<template>
    <AppLayout>
        <Head :title="`Car: ${props.car.brand} ${props.car.model}`" />

        <div class="mb-6">
            <Heading :title="`${props.car.brand} ${props.car.model}`" description="Car details" />
        </div>

        <div class="bg-card border rounded-md p-6 shadow-sm">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-muted-foreground">Brand</dt>
                    <dd class="mt-1 text-base text-card-foreground">{{ props.car.brand }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted-foreground">Model</dt>
                    <dd class="mt-1 text-base text-card-foreground">{{ props.car.model }}</dd>
                </div>

                <div>
                    <dt class="text-sm text-muted-foreground">Variant</dt>
                    <dd class="mt-1 text-base text-card-foreground">{{ props.car.variant ?? '-' }}</dd>
                </div>

                <div>
                    <dt class="text-sm text-muted-foreground">Year</dt>
                    <dd class="mt-1 text-base text-card-foreground">{{ props.car.year ?? '-' }}</dd>
                </div>

                <div>
                    <dt class="text-sm text-muted-foreground">Created</dt>
                    <dd class="mt-1 text-base text-card-foreground">{{ new Date(props.car.created_at).toLocaleString() }}</dd>
                </div>
            </dl>
        </div>
    </AppLayout>
</template>
