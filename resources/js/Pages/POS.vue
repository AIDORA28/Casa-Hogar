<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

const products = ref([]);
const cart = ref([]);
const searchQuery = ref('');
const loading = ref(true);

const filteredProducts = computed(() => {
    return products.value.filter(p => 
        p.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const cartTotal = computed(() => {
    return cart.value.reduce((sum, item) => sum + (item.price * item.quantity), 0).toFixed(2);
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

const addToCart = (product) => {
    if (product.stock < 1) {
        alert('Producto sin stock');
        return;
    }
    
    const existing = cart.value.find(item => item.id === product.id);
    if (existing) {
        if (existing.quantity < product.stock) {
            existing.quantity++;
        } else {
            alert('Stock insuficiente');
        }
    } else {
        cart.value.push({
            id: product.id,
            name: product.name,
            price: parseFloat(product.base_price),
            quantity: 1,
            max_stock: product.stock
        });
    }
};

const removeFromCart = (index) => {
    cart.value.splice(index, 1);
};

const finalizeSale = async () => {
    if (cart.value.length === 0) {
        alert('El carrito está vacío');
        return;
    }

    if (!confirm(`¿Confirmar venta de S/ ${cartTotal.value}?`)) {
        return;
    }

    const saleData = {
        sale_date: new Date().toISOString().split('T')[0],
        items: cart.value.map(item => ({
            product_id: item.id,
            quantity: item.quantity
        }))
    };

    try {
        const token = localStorage.getItem('auth_token');
        const response = await fetch('/api/sales', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify(saleData)
        });

        if (response.ok) {
            alert('✅ Venta registrada exitosamente');
            cart.value = [];
            loadProducts(); // Recargar stock
        } else {
            const error = await response.json();
            alert('❌ ' + (error.message || 'Error al registrar venta'));
        }
    } catch (error) {
        alert('Error de conexión');
    }
};

onMounted(() => {
    loadProducts();
});
</script>

<template>
    <Head title="Punto de Venta" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                🛒 Punto de Venta
            </h2>
        </template>

        <div class="py-6 md:py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Loading -->
                <div v-if="loading" class="text-center py-12">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-gray-900"></div>
                </div>

                <!-- Content -->
                <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Productos -->
                    <div class="lg:col-span-2">
                        <div class="bg-white shadow-sm sm:rounded-lg p-4">
                            <!-- Búsqueda -->
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="🔍 Buscar producto..."
                                class="w-full mb-4 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                            />

                            <!-- Grid de Productos -->
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                <div
                                    v-for="product in filteredProducts"
                                    :key="product.id"
                                    @click="addToCart(product)"
                                    class="bg-gray-50 p-4 rounded-lg cursor-pointer hover:bg-blue-50 transition border"
                                    :class="product.stock < 1 ? 'opacity-50 cursor-not-allowed' : ''"
                                >
                                    <h3 class="font-bold text-sm mb-2 truncate">{{ product.name }}</h3>
                                    <p class="text-xs text-gray-600 mb-2 line-clamp-2">{{ product.description }}</p>
                                    <p class="text-lg font-bold text-green-600">S/ {{ product.base_price }}</p>
                                    <p class="text-xs mt-1" :class="product.stock > 0 ? 'text-blue-600' : 'text-red-600'">
                                        Stock: {{ product.stock }}
                                    </p>
                                </div>
                            </div>

                            <!-- Empty State -->
                            <div v-if="filteredProducts.length === 0" class="text-center py-12 text-gray-500">
                                No se encontraron productos
                            </div>
                        </div>
                    </div>

                    <!-- Carrito -->
                    <div>
                        <div class="bg-white shadow-sm sm:rounded-lg p-4 sticky top-4">
                            <h3 class="font-bold text-lg mb-4 flex items-center">
                                <span class="text-2xl mr-2">🛒</span> Carrito
                            </h3>
                            
                            <div class="mb-4 max-h-96 overflow-y-auto">
                                <div v-if="cart.length === 0" class="text-gray-500 text-center py-8">
                                    Carrito vacío
                                </div>
                                <div
                                    v-for="(item, index) in cart"
                                    :key="index"
                                    class="flex justify-between items-center mb-3 pb-3 border-b"
                                >
                                    <div class="flex-1">
                                        <p class="font-semibold text-sm">{{ item.name }}</p>
                                        <p class="text-xs text-gray-600">S/ {{ item.price }} x {{ item.quantity }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <p class="font-bold">S/ {{ (item.price * item.quantity).toFixed(2) }}</p>
                                        <button
                                            @click="removeFromCart(index)"
                                            class="text-red-600 hover:text-red-800 text-xl"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t pt-4">
                                <div class="flex justify-between mb-4">
                                    <span class="font-bold text-lg">TOTAL:</span>
                                    <span class="font-bold text-lg text-green-600">S/ {{ cartTotal }}</span>
                                </div>
                                <button
                                    @click="finalizeSale"
                                    class="w-full bg-blue-600 text-white py-3 rounded-lg font-bold hover:bg-blue-700 transition disabled:bg-gray-400"
                                    :disabled="cart.length === 0"
                                >
                                    ✓ Finalizar Venta
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
