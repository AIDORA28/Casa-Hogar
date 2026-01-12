<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const selectedDate = ref(new Date().toISOString().split('T')[0]);
const downloading = ref(false);
const showDetail = ref(false);
const detailData = ref(null);
const loadingDetail = ref(false);
const includeInjections = ref(true); // Checkbox para incluir inyecciones

const downloadPDF = async () => {
    downloading.value = true;
    try {
        const token = localStorage.getItem('auth_token');
        const url = `/api/reports/daily-closing-pdf/${selectedDate.value}?include_injections=${includeInjections.value ? '1' : '0'}`;
        const response = await fetch(url, {
            headers: { 'Authorization': `Bearer ${token}` }
        });

        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `cierre_${selectedDate.value}.pdf`;
            link.click();
            alert('✓ PDF descargado exitosamente');
        } else {
            alert('❌ Error al generar PDF');
        }
    } catch (error) {
        alert('Error al descargar PDF');
    } finally {
        downloading.value = false;
    }
};

const loadDailyDetail = async () => {
    loadingDetail.value = true;
    showDetail.value = true;
    
    try {
        const token = localStorage.getItem('auth_token');
        const response = await fetch(`/api/reports/daily-detail/${selectedDate.value}`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });

        if (response.ok) {
            detailData.value = await response.json();
        } else {
            alert('Error al cargar desglose');
            showDetail.value = false;
        }
    } catch (error) {
        alert('Error de conexión');
        showDetail.value = false;
    } finally {
        loadingDetail.value = false;
    }
};

const closeDetail = () => {
    showDetail.value = false;
    detailData.value = null;
};
</script>

<template>
    <Head title="Reportes" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                📊 Reportes
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Descarga de PDF y Desglose Diario -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-bold text-lg mb-4 flex items-center">
                        <span class="text-2xl mr-2">📄</span> Reportes por Fecha
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                        <div class="md:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Seleccionar Fecha
                            </label>
                            <input
                                v-model="selectedDate"
                                type="date"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        
                        <!-- NUEVO: Checkbox para incluir inyecciones -->
                        <div class="md:col-span-1">
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input
                                    v-model="includeInjections"
                                    type="checkbox"
                                    class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500"
                                />
                                <span class="text-sm font-medium text-gray-700">
                                    💰 Incluir Inyecciones de Capital en PDF
                                </span>
                            </label>
                        </div>
                        
                        <div>
                            <button
                                @click="downloadPDF"
                                :disabled="downloading"
                                class="w-full px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition disabled:bg-gray-400"
                            >
                                <span v-if="downloading">Generando...</span>
                                <span v-else>📄 Descargar PDF</span>
                            </button>
                        </div>
                        
                        <div>
                            <button
                                @click="loadDailyDetail"
                                :disabled="loadingDetail"
                                class="w-full px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:bg-gray-400"
                            >
                                <span v-if="loadingDetail">Cargando...</span>
                                <span v-else>🔍 Ver Desglose</span>
                            </button>
                        </div>
                    </div>

                    <p class="text-sm text-gray-600 mt-4">
                        <strong>PDF:</strong> Genera el cierre de caja completo del día.
                        <strong class="ml-4">Desglose:</strong> Muestra el detalle de cada transacción.
                    </p>
                </div>

                <!-- Desglose Detallado (Modal-like) -->
                <div v-if="showDetail && detailData" class="bg-white overflow-hidden shadow-lg sm:rounded-lg border-2 border-blue-500">
                    <div class="bg-blue-600 text-white p-4 flex justify-between items-center">
                        <h3 class="font-bold text-lg">
                            📋 Desglose Diario - {{ selectedDate }}
                        </h3>
                        <button @click="closeDetail" class="text-white hover:text-gray-200 text-2xl">
                            ✕
                        </button>
                    </div>

                    <div class="p-6 space-y-6">
                        <!-- Resumen -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="bg-green-50 p-3 rounded border-l-4 border-green-500">
                                <p class="text-xs text-gray-600">Ventas</p>
                                <p class="text-lg font-bold text-green-600">S/ {{ detailData.summary.total_sales }}</p>
                            </div>
                            <div class="bg-red-50 p-3 rounded border-l-4 border-red-500">
                                <p class="text-xs text-gray-600">Gastos</p>
                                <p class="text-lg font-bold text-red-600">S/ {{ detailData.summary.total_expenses }}</p>
                            </div>
                            <div class="bg-yellow-50 p-3 rounded border-l-4 border-yellow-500">
                                <p class="text-xs text-gray-600">Inyecciones</p>
                                <p class="text-lg font-bold text-yellow-600">S/ {{ detailData.summary.total_injections }}</p>
                            </div>
                            <div class="bg-blue-50 p-3 rounded border-l-4 border-blue-500">
                                <p class="text-xs text-gray-600">Balance Neto</p>
                                <p class="text-lg font-bold text-blue-600">S/ {{ detailData.summary.net_balance }}</p>
                            </div>
                        </div>

                        <!-- Ventas -->
                        <div v-if="detailData.sales.length > 0">
                            <h4 class="font-bold text-green-700 mb-3">💰 Ventas ({{ detailData.sales.length }})</h4>
                            <div class="space-y-3">
                                <div v-for="sale in detailData.sales" :key="sale.id" class="bg-green-50 p-4 rounded border-l-4 border-green-500">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <p class="font-semibold">Venta #{{ sale.id }}</p>
                                            <p class="text-sm text-gray-600">{{ sale.user }} - {{ sale.created_at }}</p>
                                        </div>
                                        <p class="text-lg font-bold text-green-600">S/ {{ sale.total }}</p>
                                    </div>
                                    <div class="mt-2 space-y-1">
                                        <div v-for="(item, idx) in sale.items" :key="idx" class="text-sm flex justify-between">
                                            <span>{{ item.product }} x{{ item.quantity }}</span>
                                            <span class="font-medium">S/ {{ item.subtotal }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Gastos -->
                        <div v-if="detailData.expenses.length > 0">
                            <h4 class="font-bold text-red-700 mb-3">📊 Gastos ({{ detailData.expenses.length }})</h4>
                            <div class="space-y-2">
                                <div v-for="expense in detailData.expenses" :key="expense.id" class="bg-red-50 p-3 rounded border-l-4 border-red-500 flex justify-between items-center">
                                    <div>
                                        <p class="font-semibold">{{ expense.description }}</p>
                                        <p class="text-sm text-gray-600">{{ expense.user }} - {{ expense.created_at }}</p>
                                    </div>
                                    <p class="text-lg font-bold text-red-600">S/ {{ expense.amount }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Inyecciones de Capital -->
                        <div v-if="detailData.injections.length > 0">
                            <h4 class="font-bold text-yellow-700 mb-3">💵 Inyecciones de Capital ({{ detailData.injections.length }})</h4>
                            <div class="space-y-2">
                                <div v-for="injection in detailData.injections" :key="injection.id" class="bg-yellow-50 p-3 rounded border-l-4 border-yellow-500 flex justify-between items-center">
                                    <div>
                                        <p class="font-semibold">{{ injection.reason }}</p>
                                        <p class="text-sm text-gray-600">{{ injection.user }} - {{ injection.created_at }}</p>
                                    </div>
                                    <p class="text-lg font-bold text-yellow-600">S/ {{ injection.amount }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Empty States -->
                        <div v-if="detailData.sales.length === 0 && detailData.expenses.length === 0 && detailData.injections.length === 0" class="text-center py-12 text-gray-500">
                            Sin transacciones para esta fecha
                        </div>
                    </div>
                </div>

                <!-- Información -->
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <span class="text-2xl">ℹ️</span>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">
                                Acceso a Reportes
                            </h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p>Solo los administradores pueden acceder a esta sección.</p>
                                <p class="mt-1">Los PDFs se generan automáticamente aunque no exista un cierre manual.</p>
                                <p class="mt-1">El desglose muestra cada transacción individual del día seleccionado.</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
