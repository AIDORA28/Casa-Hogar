<template>
    <Head title="Gestión de Personal" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                👥 Gestión de Personal (Enfermeras/Tutoras)
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Botón Agregar -->
                <div class="mb-6">
                    <button
                        @click="openCreateModal"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold transition"
                    >
                        ➕ Agregar Enfermera/Tutora
                    </button>
                </div>

                <!-- Tabla de Personal -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="font-bold text-lg mb-4">Lista de Personal</h3>
                        
                        <div v-if="loading" class="text-center py-8 text-gray-500">
                            Cargando...
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado Active</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado Registro</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="nurse in nurses" :key="nurse.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ nurse.name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span 
                                                :class="nurse.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                                class="px-2 py-1 text-xs rounded-full font-semibold"
                                            >
                                                {{ nurse.is_active ? 'Activa' : 'Inactiva' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span 
                                                :class="nurse.deleted_at ? 'bg-gray-100 text-gray-800' : 'bg-blue-100 text-blue-800'"
                                                class="px-2 py-1 text-xs rounded-full font-semibold"
                                            >
                                                {{ nurse.deleted_at ? 'Eliminada' : 'En Sistema' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button 
                                                v-if="!nurse.deleted_at"
                                                @click="openEditModal(nurse)"
                                                class="text-blue-600 hover:text-blue-900 mr-4"
                                            >
                                                ✏️ Editar
                                            </button>
                                            <button 
                                                v-if="!nurse.deleted_at"
                                                @click="toggleActive(nurse)"
                                                :class="nurse.is_active ? 'text-orange-600 hover:text-orange-900' : 'text-green-600 hover:text-green-900'"
                                            >
                                                {{ nurse.is_active ? '🚫 Desactivar' : '✅ Activar' }}
                                            </button>
                                            <button 
                                                v-if="nurse.deleted_at"
                                                @click="restoreNurse(nurse)"
                                                class="text-green-600 hover:text-green-900"
                                            >
                                                🔄 Restaurar
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <div v-if="nurses.length === 0" class="text-center py-8 text-gray-500">
                                No hay personal registrado
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Crear/Editar -->
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="closeModal">
            <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
                <h3 class="text-2xl font-bold mb-6">{{ modalTitle }}</h3>
                
                <form @submit.prevent="saveNurse">
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">
                            Nombre Completo *
                        </label>
                        <input 
                            v-model="form.name"
                            type="text"
                            required
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                            placeholder="Ejemplo: María García"
                        >
                    </div>

                    <div class="flex gap-4">
                        <button 
                            type="button"
                            @click="closeModal"
                            class="flex-1 px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-semibold transition"
                        >
                            Cancelar
                        </button>
                        <button 
                            type="submit"
                            class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition"
                        >
                            {{ isEditing ? 'Actualizar' : 'Crear' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';

const nurses = ref([]);
const loading = ref(false);
const showModal = ref(false);
const isEditing = ref(false);
const form = ref({
    id: null,
    name: ''
});

const modalTitle = computed(() => {
    return isEditing.value ? 'Editar Enfermera/Tutora' : 'Agregar Enfermera/Tutora';
});

const loadNurses = async () => {
    loading.value = true;
    try {
        const token = localStorage.getItem('auth_token');
        const response = await axios.get('/api/nurses', {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        nurses.value = response.data;
    } catch (error) {
        alert('Error al cargar el personal');
        console.error(error);
    } finally {
        loading.value = false;
    }
};

const openCreateModal = () => {
    isEditing.value = false;
    form.value = { id: null, name: '' };
    showModal.value = true;
};

const openEditModal = (nurse) => {
    isEditing.value = true;
    form.value = { id: nurse.id, name: nurse.name };
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.value = { id: null, name: '' };
};

const saveNurse = async () => {
    try {
        const token = localStorage.getItem('auth_token');
        const headers = {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        };

        if (isEditing.value) {
            await axios.put(`/api/nurses/${form.value.id}`, { name: form.value.name }, { headers });
            alert('Enfermera actualizada exitosamente');
        } else {
            await axios.post('/api/nurses', { name: form.value.name }, { headers });
            alert('Enfermera creada exitosamente');
        }
        
        await loadNurses();
        closeModal();
    } catch (error) {
        const message = error.response?.data?.message || 'Error al guardar';
        alert(message);
        console.error(error);
    }
};

const toggleActive = async (nurse) => {
    const action = nurse.is_active ? 'desactivar' : 'activar';
    if (!confirm(`¿Está seguro de ${action} a ${nurse.name}?`)) return;

    try {
        const token = localStorage.getItem('auth_token');
        await axios.put(`/api/nurses/${nurse.id}/toggle`, {}, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        await loadNurses();
        alert(`Enfermera ${nurse.is_active ? 'desactivada' : 'activada'} exitosamente`);
    } catch (error) {
        alert('Error al cambiar el estado');
        console.error(error);
    }
};

const restoreNurse = async (nurse) => {
    if (!confirm(`¿Restaurar a ${nurse.name}?`)) return;

    try {
        const token = localStorage.getItem('auth_token');
        await axios.put(`/api/nurses/${nurse.id}/restore`, {}, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        await loadNurses();
        alert('Enfermera restaurada exitosamente');
    } catch (error) {
        alert('Error al restaurar');
        console.error(error);
    }
};

onMounted(() => {
    loadNurses();
});
</script>
