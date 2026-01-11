<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const products = ref([]);
const loading = ref(true);
const editingProduct = ref(null);
const showModal = ref(false);

const form = ref({
    name: '',
    description: '',
    stock: 0,
    base_price: 0
});

const loadProducts = async () => {
    try {
        const token = localStorage.getItem('auth_token');
        const response = await fetch('/api/products', {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        const data = await response.json();
        products.value = data.data;
    } catch (error) {
        alert('Error al cargar productos');
    } finally {
        loading.value = false;
    }
};

const openCreateModal = () => {
    editingProduct.value = null;
    form.value = { name: '', description: '', stock: 0, base_price: 0 };
    showModal.value = true;
};

const openEditModal = (product) => {
    editingProduct.value = product;
    form.value = {
        name: product.name,
        description: product.description,
        stock: product.stock,
        base_price: parseFloat(product.base_price)
    };
    showModal.value = true;
};

const saveProduct = async () => {
    const token = localStorage.getItem('auth_token');
    const url = editingProduct.value 
        ? `/api/products/${editingProduct.value.id}`
        : '/api/products';
    const method = editingProduct.value ? 'PUT' : 'POST';

    try {
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify(form.value)
        });

        if (response.ok) {
            alert(editingProduct.value ? '✅ Producto actualizado' : '✅ Producto creado');
            showModal.value = false;
            loadProducts();
        } else {
            alert('❌ Error al guardar producto');
        }
    } catch (error) {
        alert('Error de conexión');
    }
};

const deleteProduct = async (id) => {
    if (!confirm('¿Eliminar este producto?')) return;

    try {
        const token = localStorage.getItem('auth_token');
        const response = await fetch(`/api/products/${id}`, {
            method: 'DELETE',
            headers: { 'Authorization': `Bearer ${token}` }
        });

        if (response.ok) {
            alert('✅ Producto eliminado');
            loadProducts();
        }
    } catch (error) {
        alert('Error al eliminar');
    }
};

onMounted(() => {
    loadProducts();
});
</script>

<template>
    <Head title="Gestión de Inventario" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    📦 Gestión de Inventario (Administrador)
                </h2>
                <button
                    @click="openCreateModal"
                    class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition"
                >
                    + Nuevo Producto
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Información -->
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 rounded">
                    <p class="text-sm text-yellow-800">
                        <strong>⚠️ Catálogo Maestro:</strong> Los precios aquí definidos se usarán automáticamente en el Registro Diario. 
                        Solo el Administrador puede modificarlos para evitar errores en la tesorería.
                    </p>
                </div>

                <!-- Loading -->
                <div v-if="loading" class="text-center py-12">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-gray-900"></div>
                </div>

                <!-- Tabla de Productos -->
                <div v-else class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descripción</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Precio Maestro</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="product in products" :key="product.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                                    {{ product.name }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ product.description }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full" 
                                          :class="product.stock > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                        {{ product.stock }} unidades
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-bold text-green-600">
                                    S/ {{ product.base_price }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm space-x-2">
                                    <button
                                        @click="openEditModal(product)"
                                        class="text-blue-600 hover:text-blue-900"
                                    >
                                        ✏️ Editar
                                    </button>
                                    <button
                                        @click="deleteProduct(product.id)"
                                        class="text-red-600 hover:text-red-900"
                                    >
                                        🗑️ Eliminar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-if="products.length === 0" class="text-center py-12 text-gray-500">
                        No hay productos registrados. Crea el catálogo maestro.
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
                <h3 class="text-lg font-bold mb-4">
                    {{ editingProduct ? 'Editar Producto' : 'Nuevo Producto' }}
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-green-500"
                            placeholder="Ej: Keke de Piña"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                        <input
                            v-model="form.description"
                            type="text"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-green-500"
                            placeholder="Opcional"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stock Inicial</label>
                        <input
                            v-model.number="form.stock"
                            type="number"
                            min="0"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-green-500"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Precio Maestro (Fijo)</label>
                        <input
                            v-model.number="form.base_price"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-green-500"
                            placeholder="0.00"
                        />
                    </div>
                </div>

                <div class="flex gap-2 mt-6">
                    <button
                        @click="showModal = false"
                        class="flex-1 px-4 py-2 border rounded-lg hover:bg-gray-50"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="saveProduct"
                        class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                    >
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
