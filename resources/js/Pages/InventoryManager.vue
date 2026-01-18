<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';

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

const searchQuery = ref('');

// Estadísticas para los summary cards
const stats = ref({
    totalProducts: 0,
    criticalStockCount: 0,
    totalValue: 0
});

const calculateStats = () => {
    stats.value.totalProducts = products.value.length;
    stats.value.criticalStockCount = products.value.filter(p => p.stock < 5).length;
    stats.value.totalValue = products.value.reduce((sum, p) => sum + (p.stock * p.base_price), 0);
};

// Productos filtrados por búsqueda
const filteredProducts = computed(() => {
    if (!searchQuery.value) return products.value;
    const query = searchQuery.value.toLowerCase();
    return products.value.filter(p => 
        p.name.toLowerCase().includes(query) || 
        (p.description && p.description.toLowerCase().includes(query))
    );
});

const loadProducts = async () => {
    try {
        const token = localStorage.getItem('auth_token');
        const response = await fetch('/api/products', {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        const data = await response.json();
        products.value = data.data;
        calculateStats();
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
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h2 class="font-bold text-2xl text-gray-800 leading-tight flex items-center">
                        <span class="mr-3">📦</span> Gestión de Inventario
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Control maestro de productos y existencias</p>
                </div>
                <button
                    @click="openCreateModal"
                    class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-6 py-3 rounded-xl hover:shadow-lg font-black transition-all hover:-translate-y-1 active:scale-95 flex items-center uppercase tracking-wider text-sm"
                >
                    <span class="mr-2">➕</span> Nuevo Producto
                </button>
            </div>
        </template>

        <div class="py-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- Summary Stats (Nuevos Cards Fluidos) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="card-stat-blue hover:shadow-md transition-smooth">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 font-medium mb-1">Total Productos</p>
                                <p class="text-3xl font-bold text-blue-600">{{ stats.totalProducts }}</p>
                            </div>
                            <div class="text-4xl">📋</div>
                        </div>
                    </div>
                    <div :class="stats.criticalStockCount > 0 ? 'card-stat-red animate-pulse' : 'card-stat-green'" class="hover:shadow-md transition-smooth">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 font-medium mb-1">Stock Crítico (&lt;5)</p>
                                <p class="text-3xl font-bold" :class="stats.criticalStockCount > 0 ? 'text-red-600' : 'text-green-600'">
                                    {{ stats.criticalStockCount }}
                                </p>
                            </div>
                            <div class="text-4xl">{{ stats.criticalStockCount > 0 ? '⚠️' : '✅' }}</div>
                        </div>
                    </div>
                    <div class="card-stat-yellow hover:shadow-md transition-smooth">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 font-medium mb-1">Valor Inventario</p>
                                <p class="text-3xl font-bold text-amber-600">S/ {{ stats.totalValue.toFixed(2) }}</p>
                            </div>
                            <div class="text-4xl">💰</div>
                        </div>
                    </div>
                </div>

                <!-- Buscador y Filtros -->
                <div class="flex flex-col md:flex-row gap-4 items-center justify-between bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                    <div class="relative w-full md:w-96">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            🔍
                        </span>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Buscar por nombre o descripción..."
                            class="input-base pl-10 h-11 border-gray-200 focus:border-blue-500 focus:ring-blue-500 rounded-xl"
                        />
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <span>Mostrando {{ filteredProducts.length }} de {{ products.length }} productos</span>
                    </div>
                </div>

                <!-- Loading -->
                <div v-if="loading" class="flex flex-col items-center justify-center py-20">
                    <div class="spinner w-16 h-16 border-blue-500"></div>
                    <p class="mt-6 text-gray-600 text-lg">Cargando inventario...</p>
                </div>

                <!-- Tabla de Productos -->
                <div v-else class="card-base p-0 overflow-hidden">
                    <!-- Header de tabla -->
                    <div class="px-6 py-5 bg-gradient-to-r from-gray-50 to-white border-b flex justify-between items-center">
                        <div>
                            <h3 class="section-header !mb-0">
                                <span class="icon">📋</span> Catálogo de Productos
                            </h3>
                            <p class="text-sm font-medium text-gray-400 uppercase tracking-widest mt-1">
                                Total Registrados: {{ products.length }}
                            </p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Descripción</th>
                                    <th>Estado de Stock</th>
                                    <th>Precio Base</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="product in filteredProducts" :key="product.id">
                                    <td class="font-medium text-gray-900">
                                        <div class="flex items-center">
                                            <span class="text-2xl mr-2">📦</span>
                                            {{ product.name }}
                                        </div>
                                    </td>
                                    <td class="text-gray-600">
                                        {{ product.description || '-' }}
                                    </td>
                                    <td>
                                        <!-- Stock Badge Mejorado con 3 niveles -->
                                        <span 
                                            :class="{
                                                'badge-red': product.stock < 5,
                                                'badge-yellow': product.stock >= 5 && product.stock < 15,
                                                'badge-green': product.stock >= 15
                                            }"
                                            class="inline-flex items-center"
                                        >
                                            <span v-if="product.stock < 5" class="mr-1">🔴</span>
                                            <span v-else-if="product.stock < 15" class="mr-1">🟡</span>
                                            <span v-else class="mr-1">🟢</span>
                                            {{ product.stock }} unidades
                                        </span>
                                        <p v-if="product.stock < 5" class="text-xs text-red-600 mt-1">
                                            ⚠️ Stock bajo
                                        </p>
                                    </td>
                                    <td class="font-bold text-green-600">
                                        S/ {{ Number(product.base_price).toFixed(2) }}
                                    </td>
                                    <td class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <button
                                                @click="openEditModal(product)"
                                                class="px-3 py-1.5 text-sm bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-smooth font-medium"
                                            >
                                                ✏️ Editar
                                            </button>
                                            <button
                                                @click="deleteProduct(product.id)"
                                                class="px-3 py-1.5 text-sm bg-red-50 text-red-700 rounded-lg hover:bg-red-100 transition-smooth font-medium"
                                            >
                                                🗑️ Eliminar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Empty State -->
                        <div v-if="products.length === 0" class="empty-state">
                            <div class="empty-state-icon">📦</div>
                            <p class="text-lg font-medium text-gray-700 mb-2">No hay productos registrados</p>
                            <p class="text-sm text-gray-500 mb-4">Comienza creando el catálogo maestro de productos</p>
                            <button @click="openCreateModal" class="btn-primary">
                                + Crear Primer Producto
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Mejorado -->
        <div v-if="showModal" 
             class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
             @click.self="showModal = false"
        >
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md transform transition-all">
                <!-- Header del Modal -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 rounded-t-xl">
                    <h3 class="text-xl font-bold text-white flex items-center">
                        <span class="text-2xl mr-2">{{ editingProduct ? '✏️' : '➕' }}</span>
                        {{ editingProduct ? 'Editar Producto' : 'Nuevo Producto' }}
                    </h3>
                </div>

                <!-- Form -->
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Nombre del Producto <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="input-base"
                            placeholder="Ej: Keke de Piña"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Descripción (Opcional)
                        </label>
                        <input
                            v-model="form.description"
                            type="text"
                            class="input-base"
                            placeholder="Detalles adicionales"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Stock Inicial <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model.number="form.stock"
                                type="number"
                                min="0"
                                class="input-base"
                                placeholder="0"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Precio (S/) <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model.number="form.base_price"
                                type="number"
                                step="0.01"
                                min="0"
                                class="input-base"
                                placeholder="0.00"
                            />
                        </div>
                    </div>

                    <!-- Info adicional -->
                    <div class="bg-blue-50 border-l-4 border-blue-400 p-3 rounded">
                        <p class="text-xs text-blue-800">
                            💡 <strong>Tip:</strong> El precio base se usará automáticamente en todas las ventas.
                        </p>
                    </div>
                </div>

                <!-- Footer del Modal -->
                <div class="px-6 py-4 bg-gray-50 rounded-b-xl flex gap-3">
                    <button
                        @click="showModal = false"
                        class="btn-secondary flex-1"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="saveProduct"
                        class="btn-primary flex-1"
                    >
                        {{ editingProduct ? '💾 Actualizar' : '✅ Crear' }}
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
