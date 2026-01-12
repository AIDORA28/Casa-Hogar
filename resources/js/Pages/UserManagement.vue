<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const users = ref([]);
const permissions = ref([]);
const loading = ref(false);
const showModal = ref(false);
const editMode = ref(false);
const formData = ref({
    id: null,
    name: '',
    email: '',
    password: '',
    permissions: []
});

// Cargar usuarios
const loadUsers = async () => {
    loading.value = true;
    try {
        const token = localStorage.getItem('auth_token');
        const response = await fetch('/api/users', {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        if (response.ok) {
            users.value = await response.json();
        }
    } catch (error) {
        console.error('Error al cargar usuarios:', error);
    } finally {
        loading.value = false;
    }
};

// Cargar permisos disponibles
const loadPermissions = async () => {
    try {
        const token = localStorage.getItem('auth_token');
        const response = await fetch('/api/permissions', {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        if (response.ok) {
            permissions.value = await response.json();
        }
    } catch (error) {
        console.error('Error al cargar permisos:', error);
    }
};

// Abrir modal para crear
const openCreateModal = () => {
    editMode.value = false;
    formData.value = {
        id: null,
        name: '',
        email: '',
        password: '',
        permissions: []
    };
    showModal.value = true;
};

// Abrir modal para editar
const openEditModal = (user) => {
    editMode.value = true;
    formData.value = {
        id: user.id,
        name: user.name,
        email: user.email,
        password: '',
        permissions: [...user.permissions]
    };
    showModal.value = true;
};

// Cerrar modal
const closeModal = () => {
    showModal.value = false;
    formData.value = {
        id: null,
        name: '',
        email: '',
        password: '',
        permissions: []
    };
};

// Toggle permiso
const togglePermission = (permissionName) => {
    const index = formData.value.permissions.indexOf(permissionName);
    if (index > -1) {
        formData.value.permissions.splice(index, 1);
    } else {
        formData.value.permissions.push(permissionName);
    }
};

// Guardar usuario (crear o editar)
const saveUser = async () => {
    try {
        const token = localStorage.getItem('auth_token');
        const url = editMode.value ? `/api/users/${formData.value.id}` : '/api/users';
        const method = editMode.value ? 'PUT' : 'POST';

        const payload = {
            name: formData.value.name,
            email: formData.value.email,
            permissions: formData.value.permissions
        };

        // Solo incluir contraseña si se proporciona
        if (formData.value.password) {
            payload.password = formData.value.password;
        }

        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify(payload)
        });

        if (response.ok) {
            alert(editMode.value ? '✅ Usuario actualizado' : '✅ Usuario creado');
            closeModal();
            loadUsers();
        } else {
            const error = await response.json();
            alert('❌ Error: ' + (error.message || 'No se pudo guardar el usuario'));
        }
    } catch (error) {
        alert('❌ Error de conexión');
    }
};

// Eliminar usuario
const deleteUser = async (user) => {
    if (!confirm(`¿Eliminar usuario ${user.name}?`)) return;

    try {
        const token = localStorage.getItem('auth_token');
        const response = await fetch(`/api/users/${user.id}`, {
            method: 'DELETE',
            headers: { 'Authorization': `Bearer ${token}` }
        });

        if (response.ok) {
            alert('✅ Usuario eliminado');
            loadUsers();
        } else {
            const error = await response.json();
            alert('❌ ' + error.message);
        }
    } catch (error) {
        alert('❌ Error de conexión');
    }
};

// Traducir permisos al español
const getPermissionLabel = (name) => {
    const labels = {
        'manage_inventory': 'Gestionar Inventario',
        'download_reports': 'Descargar Reportes',
        'inject_capital': 'Inyectar Capital'
    };
    return labels[name] || name;
};

onMounted(() => {
    loadUsers();
    loadPermissions();
});
</script>

<template>
    <Head title="Gestión de Usuarios" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                👥 Gestión de Usuarios
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Botón Crear Usuario -->
                <div class="mb-6">
                    <button
                        @click="openCreateModal"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold shadow-lg transition"
                    >
                        ➕ Crear Usuario
                    </button>
                </div>

                <!-- Tabla de Usuarios -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="font-bold text-lg mb-4">Usuarios del Sistema</h3>
                        
                        <div v-if="loading" class="text-center py-8 text-gray-500">
                            Cargando usuarios...
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rol</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Permisos</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="user in users" :key="user.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-600">{{ user.email }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span 
                                                :class="user.role === 'admin' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'"
                                                class="px-2 py-1 text-xs rounded-full font-semibold"
                                            >
                                                {{ user.role === 'admin' ? 'Administrador' : 'Tesorero' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap gap-1">
                                                <span 
                                                    v-for="perm in user.permissions" 
                                                    :key="perm"
                                                    class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full"
                                                >
                                                    {{ getPermissionLabel(perm) }}
                                                </span>
                                                <span v-if="user.permissions.length === 0" class="text-xs text-gray-400">
                                                    Solo permisos básicos
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                            <template v-if="user.role !== 'admin'">
                                                <button
                                                    @click="openEditModal(user)"
                                                    class="text-blue-600 hover:text-blue-900 mr-3"
                                                >
                                                    ✏️ Editar
                                                </button>
                                                <button
                                                    @click="deleteUser(user)"
                                                    class="text-red-600 hover:text-red-900"
                                                >
                                                    🗑️ Eliminar
                                                </button>
                                            </template>
                                            <span v-else class="text-gray-400 text-xs">
                                                No editable
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Modal Crear/Editar -->
                <div
                    v-if="showModal"
                    class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
                    @click.self="closeModal"
                >
                    <div class="relative top-20 mx-auto p-6 border w-full max-w-md shadow-lg rounded-lg bg-white">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-bold">
                                {{ editMode ? 'Editar Usuario' : 'Crear Usuario' }}
                            </h3>
                            <button @click="closeModal" class="text-gray-500 hover:text-gray-700 text-2xl">
                                ✕
                            </button>
                        </div>

                        <form @submit.prevent="saveUser" class="space-y-4">
                            <!-- Nombre -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                                <input
                                    v-model="formData.name"
                                    type="text"
                                    required
                                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                    placeholder="Nombre completo"
                                />
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico</label>
                                <input
                                    v-model="formData.email"
                                    type="email"
                                    required
                                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                    placeholder="correo@example.com"
                                />
                            </div>

                            <!-- Contraseña -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Contraseña {{ editMode ? '(dejar en blanco para no cambiar)' : '' }}
                                </label>
                                <input
                                    v-model="formData.password"
                                    type="password"
                                    :required="!editMode"
                                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                    placeholder="Mínimo 8 caracteres"
                                />
                            </div>

                            <!-- Permisos -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Permisos Adicionales</label>
                                <div class="space-y-2">
                                    <label
                                        v-for="permission in permissions"
                                        :key="permission.name"
                                        class="flex items-center space-x-2 cursor-pointer"
                                    >
                                        <input
                                            type="checkbox"
                                            :checked="formData.permissions.includes(permission.name)"
                                            @change="togglePermission(permission.name)"
                                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                        />
                                        <span class="text-sm text-gray-700">
                                            {{ getPermissionLabel(permission.name) }}
                                        </span>
                                    </label>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">
                                    Los permisos básicos (Dashboard y Registro Diario) están incluidos por defecto.
                                </p>
                            </div>

                            <!-- Botones -->
                            <div class="flex justify-end gap-3 mt-6">
                                <button
                                    type="button"
                                    @click="closeModal"
                                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold"
                                >
                                    {{ editMode ? 'Actualizar' : 'Crear' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
