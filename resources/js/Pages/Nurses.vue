<template>
    <Head title="Gestión de Personal" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h2 class="font-bold text-2xl text-gray-800 leading-tight flex items-center">
                        <span class="mr-3">👩‍⚕️</span> Gestión de Personal
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Administra el equipo de enfermeras y tutoras</p>
                </div>
                <button
                    @click="openCreateModal"
                    class="bg-gradient-to-r from-purple-600 to-indigo-700 text-white px-6 py-3 rounded-xl hover:shadow-lg font-bold transition-all hover:-translate-y-1 active:scale-95 flex items-center"
                >
                    <span class="mr-2">➕</span> Nueva Enfermera/Tutora
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Info Card Fluida -->
                <div class="bg-gradient-to-r from-purple-50 to-indigo-50 border-l-4 border-purple-500 p-6 rounded-2xl shadow-sm border border-purple-100">
                    <div class="flex items-center">
                        <div class="bg-white p-3 rounded-xl shadow-sm mr-4 text-2xl">👩‍⚕️</div>
                        <div>
                            <p class="font-bold text-purple-900 text-lg">Control de Personal</p>
                            <p class="text-sm text-purple-700">
                                Las personas registradas aquí aparecerán como opciones en el Registro Diario para asignar la responsabilidad de cada venta.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Personal -->
                <div class="card-base p-0 overflow-hidden">
                    <div class="px-6 py-5 bg-gradient-to-r from-gray-50 to-white border-b border-gray-100 flex justify-between items-center">
                        <div>
                            <h3 class="section-header !mb-0">
                                <span class="icon">📋</span> Personal Autorizado
                            </h3>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1">
                                Enfermeras y Tutoras registradas
                            </p>
                        </div>
                    </div>
                    
                    <!-- Loading State -->
                    <div v-if="loading" class="flex flex-col items-center justify-center py-12">
                        <div class="spinner w-12 h-12 border-purple-500"></div>
                        <p class="mt-4 text-gray-500">Cargando personal...</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Estado Active</th>
                                    <th>Estado Registro</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="nurse in nurses" :key="nurse.id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ nurse.name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span 
                                            :class="nurse.is_active ? 'badge-green' : 'badge-red'"
                                            class="inline-flex items-center"
                                        >
                                            {{ nurse.is_active ? 'Activa' : 'Inactiva' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span 
                                            :class="nurse.deleted_at ? 'badge-gray' : 'badge-blue'"
                                            class="inline-flex items-center"
                                        >
                                            {{ nurse.deleted_at ? 'Eliminada' : 'En Sistema' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end gap-3">
                                            <button 
                                                v-if="!nurse.deleted_at"
                                                @click="openEditModal(nurse)"
                                                class="text-blue-600 hover:text-blue-900 font-semibold"
                                            >
                                                ✏️ Editar
                                            </button>
                                            <button 
                                                v-if="!nurse.deleted_at"
                                                @click="toggleActive(nurse)"
                                                :class="nurse.is_active ? 'text-orange-600 hover:text-orange-900' : 'text-green-600 hover:text-green-900'"
                                                class="font-semibold"
                                            >
                                                {{ nurse.is_active ? '🚫 Desactivar' : '✅ Activar' }}
                                            </button>
                                            <button 
                                                v-if="nurse.deleted_at"
                                                @click="restoreNurse(nurse)"
                                                class="text-green-600 hover:text-green-900 font-semibold"
                                            >
                                                🔄 Restaurar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div v-if="nurses.length === 0" class="empty-state">
                            <div class="empty-state-icon text-4xl">👩‍⚕️</div>
                            <p class="text-lg font-medium text-gray-700">No hay personal registrado</p>
                            <p class="text-sm text-gray-500">Comienza agregando enfermeras o tutoras</p>
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
