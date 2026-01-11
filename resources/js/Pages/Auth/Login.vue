<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = async () => {
    // Primero intentar login en la API para obtener el token
    try {
        const response = await fetch('/api/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                email: form.email,
                password: form.password,
            }),
        });

        if (response.ok) {
            const data = await response.json();
            // Guardar token en localStorage
            localStorage.setItem('auth_token', data.token);
            console.log('✅ Token guardado en localStorage');
        }
    } catch (error) {
        console.error('Error al obtener token:', error);
    }

    // Luego hacer el login normal de Breeze
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Iniciar Sesión" />

        <!-- Logo Casa Hogar -->
        <div class="text-center mb-6">
            <h1 class="text-3xl font-bold text-green-600 flex items-center justify-center">
                <span class="text-4xl mr-2">🏠</span> Casa Hogar
            </h1>
            <p class="text-gray-600 mt-2">Sistema de Gestión</p>
        </div>

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Correo Electrónico" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="admin@casahogar.com"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Contraseña" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600">Recordarme</span>
                </label>
            </div>

            <div class="mt-6">
                <PrimaryButton
                    class="w-full justify-center bg-green-600 hover:bg-green-700 focus:bg-green-700 active:bg-green-900"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">Iniciando sesión...</span>
                    <span v-else>🔐 Iniciar Sesión</span>
                </PrimaryButton>
            </div>

            <!-- Usuarios de prueba -->
            <div class="mt-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                <p class="text-xs font-semibold text-gray-700 mb-2">Usuarios de prueba:</p>
                <p class="text-xs text-gray-600">
                    <strong>Admin:</strong> admin@casahogar.com / admin123
                </p>
                <p class="text-xs text-gray-600">
                    <strong>Tesorero:</strong> tesorero@casahogar.com / tesorero123
                </p>
            </div>
        </form>
    </GuestLayout>
</template>
