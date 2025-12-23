<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps<{ user: any }>();

const form = useForm({ name: props.user.name, email: props.user.email, phone_no: props.user.phone_no ?? '', password: '', password_confirmation: '' });
const page = usePage();

// Ownership form controls
const showForm = ref(false);
const selectedCarId = ref(props.user.available_cars?.[0]?.id ?? null);
const newPlate = ref('');

const csrfToken = (typeof window !== 'undefined' && (window as any).csrfToken)
    ? (window as any).csrfToken
    : (document.querySelector('meta[name=csrf-token]')?.getAttribute('content') ?? '');

function submit() {
    // Use the form.patch helper so we send a PATCH request to the update route
    form.patch(`/users/${props.user.id}`, {
        preserveScroll: true,
    });
}

// Multiple ownerships support
import { reactive } from 'vue';
const ownerRows = reactive([{ car_id: selectedCarId.value, plate: '' }]);

function addRow() {
    ownerRows.push({ car_id: props.user.available_cars?.[0]?.id ?? null, plate: '' });
}

function removeRow(index) {
    ownerRows.splice(index, 1);
}

function submitOwners() {
    const owners = ownerRows.filter(r => r.car_id && r.plate).map(r => ({ car_id: r.car_id, plate: r.plate }));
    if (!owners.length) return;

    const formEl = document.createElement('form');
    formEl.method = 'post';
    formEl.action = `/users/${props.user.id}/owners`;

    const token = document.createElement('input');
    token.type = 'hidden';
    token.name = '_token';
    token.value = csrfToken;
    formEl.appendChild(token);

    owners.forEach((o, idx) => {
        const carInput = document.createElement('input');
        carInput.type = 'hidden';
        carInput.name = `owners[${idx}][car_id]`;
        carInput.value = o.car_id;
        formEl.appendChild(carInput);

        const plateInput = document.createElement('input');
        plateInput.type = 'hidden';
        plateInput.name = `owners[${idx}][plate]`;
        plateInput.value = o.plate;
        formEl.appendChild(plateInput);
    });

    document.body.appendChild(formEl);
    formEl.submit();
}

</script>

<template>
    <AppLayout>
        <Head title="Edit user" />
        <div class="flex items-center justify-between mb-6">
            <Heading title="Edit user" description="Update user details" />
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
                <Label for="password">Password (leave blank to keep current)</Label>
                <Input id="password" v-model="form.password" type="password" name="password" />
                <InputError :message="form.errors.password" />
            </div>

            <div>
                <Label for="password_confirmation">Confirm password</Label>
                <Input id="password_confirmation" v-model="form.password_confirmation" type="password" name="password_confirmation" />
            </div>

            <div>
                <Button type="submit" :disabled="form.processing">Save</Button>
            </div>
        </form>

        <!-- Ownerships: add / list -->
        <div class="mt-6 bg-card border rounded-md p-6 shadow-sm max-w-lg">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-medium">Owned Cars</h3>
                    <p class="text-sm text-muted-foreground">Attach reference cars to this user with a plate number</p>
                </div>
                <div v-if="page.props.auth?.user?.id === props.user.id">
                    <button @click="showForm = !showForm" class="btn btn-primary">Add Ownerships</button>
                </div>
            </div>

            <div v-if="showForm" class="mb-4">
                <div class="space-y-2">
                    <template v-for="(row, idx) in ownerRows" :key="idx">
                        <div class="flex gap-2 items-end">
                            <div>
                                <label class="text-sm text-muted-foreground">Model</label>
                                <select v-model="row.car_id" class="block mt-1">
                                    <option v-for="c in props.user.available_cars" :key="c.id" :value="c.id">{{ c.brand }} {{ c.model }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="text-sm text-muted-foreground">Plate</label>
                                <input v-model="row.plate" class="block mt-1" placeholder="Plate number" />
                            </div>

                            <div>
                                <button @click.prevent="removeRow(idx)" class="btn">Remove</button>
                            </div>
                        </div>
                    </template>

                    <div class="flex gap-2">
                        <button @click.prevent="addRow" class="btn">Add another</button>
                        <button @click.prevent="submitOwners" class="btn btn-primary">Save Owners</button>
                        <button @click="showForm = false" class="ml-2 btn">Cancel</button>
                    </div>
                </div>
            </div>

            <template v-if="props.user.cars && props.user.cars.length">
                <div class="overflow-x-auto mt-4">
                    <table class="w-full text-left">
                        <thead>
                            <tr>
                                <th class="px-4 py-2">Brand</th>
                                <th class="px-4 py-2">Model</th>
                                <th class="px-4 py-2">Plate</th>
                                <th class="px-4 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="car in props.user.cars" :key="car.id">
                                <td class="px-4 py-2">{{ car.brand }}</td>
                                <td class="px-4 py-2">{{ car.model }}</td>
                                <td class="px-4 py-2">{{ car.plate ?? '-' }}</td>
                                <td class="px-4 py-2">
                                    <form v-if="page.props.auth?.user?.id === props.user.id" :action="`/cars/${car.id}/owners`" method="post" onsubmit="return confirm('Remove ownership?')" class="inline">
                                        <input type="hidden" name="_token" :value="csrfToken" />
                                        <input type="hidden" name="_method" value="DELETE" />
                                        <input type="hidden" name="user_id" :value="props.user.id" />
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
