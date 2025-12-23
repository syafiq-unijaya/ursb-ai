<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';

const form = useForm({ name: '', email: '', phone_no: '', password: '', password_confirmation: '' });

function submit() {
    form.post('/users', {
        preserveScroll: true,
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Create user" />
        <div class="flex items-center justify-between mb-6">
            <Heading title="Create user" description="Add a new user to the system" />
        </div>

        <form @submit.prevent="submit" class="space-y-4 max-w-lg">
            <div>
                <Label for="name">Name</Label>
                <Input id="name" v-model="form.name" name="name" />
                <InputError :message="form.errors.name" />
            </div>

            <div>
                <Label for="email">Email</Label>
                <Input id="email" v-model="form.email" name="email" />
                <InputError :message="form.errors.email" />
            </div>

            <div>
                <Label for="phone_no">Phone</Label>
                <Input id="phone_no" v-model="form.phone_no" name="phone_no" />
                <InputError :message="form.errors.phone_no" />
            </div>

            <div>
                <Label for="password">Password</Label>
                <Input id="password" v-model="form.password" type="password" name="password" />
                <InputError :message="form.errors.password" />
            </div>

            <div>
                <Label for="password_confirmation">Confirm password</Label>
                <Input id="password_confirmation" v-model="form.password_confirmation" type="password" name="password_confirmation" />
            </div>

            <div>
                <Button type="submit" :disabled="form.processing">Create</Button>
            </div>
        </form>
    </AppLayout>
</template>
