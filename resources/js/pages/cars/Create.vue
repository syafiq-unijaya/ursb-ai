<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';

const form = useForm({ brand: '', model: '', variant: '', year: '' });

function submit() {
    form.post('/cars', {
        preserveScroll: true,
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Create car" />
        <div class="flex items-center justify-between mb-6">
            <Heading title="Create car" description="Add a new car to the system" />
        </div>

        <form @submit.prevent="submit" class="space-y-4 max-w-lg">
            <div>
                <Label for="brand">Brand</Label>
                <Input id="brand" v-model="form.brand" name="brand" />
                <InputError :message="form.errors.brand" />
            </div>

            <div>
                <Label for="model">Model</Label>
                <Input id="model" v-model="form.model" name="model" />
                <InputError :message="form.errors.model" />
            </div>

            <div>
                <Label for="variant">Variant</Label>
                <Input id="variant" v-model="form.variant" name="variant" />
                <InputError :message="form.errors.variant" />
            </div>

            <div>
                <Label for="year">Year</Label>
                <Input id="year" v-model="form.year" name="year" type="number" />
                <InputError :message="form.errors.year" />
            </div>

            <div>
                <Button type="submit" :disabled="form.processing">Create</Button>
            </div>
        </form>
    </AppLayout>
</template>
