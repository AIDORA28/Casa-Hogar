<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const stats = ref({
    total_sales: '0.00',
    total_expenses: '0.00',
    net_profit: '0.00',
    top_products: []
});

const loading = ref(true);

const chartData = ref({
    labels: [],
    datasets: [{
        label: 'Unidades Vendidas',
        data: [],
        backgroundColor: 'rgba(34, 197, 94, 0.7)',
        borderColor: 'rgb(34, 197, 94)',
        borderWidth: 1
    }]
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        title: { display: true, text: 'Top 5 Productos Más Vendidos' }
    }
};

const loadDashboard = async () => {
    try {
        const token = localStorage.getItem('auth_token');
        const response = await fetch('/api/reports/dashboard', {
            headers: { 'Authorization': `Bearer ${token}` }
        });

        if (response.ok) {
            const data = await response.json();
            stats.value = data;
            
            // Actualizar datos del gráfico
            chartData.value.labels = data.top_products.map(p => p.name);
            chartData.value.datasets[0].data = data.top_products.map(p => p.total_sold);
        }
    } catch (error) {
        console.error('Error loading dashboard:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    loadDashboard();
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                🏠 Dashboard - Casa Hogar
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Loading State -->
                <div v-if="loading" class="flex flex-col items-center justify-center py-20">
                    <div class="spinner w-16 h-16 border-green-500"></div>
                    <p class="mt-6 text-gray-600 text-lg">Cargando estadísticas...</p>
                </div>

                <!-- Dashboard Content -->
                <div v-else class="space-y-8">
                    <!-- Header con bienvenida -->
                    <div class="card-base">
                        <h3 class="section-header text-gray-900 !mb-2">
                            <span class="icon">📊</span> Resumen Mensual de Tesorería
                        </h3>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-widest">
                            Visualización de métricas clave del periodo
                        </p>
                    </div>

                    <!-- Tarjetas de Resumen Mejoradas -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Ventas -->
                        <div class="card-stat-green hover:shadow-lg transition-smooth">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="text-sm text-gray-600 font-medium mb-1">Ventas del Mes</p>
                                    <p class="text-3xl font-bold text-green-600 mb-2">
                                        S/ {{ stats.total_sales }}
                                    </p>
                                    <div class="flex items-center text-xs text-green-700">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>Ingresos totales</span>
                                    </div>
                                </div>
                                <div class="text-5xl">💰</div>
                            </div>
                        </div>

                        <!-- Gastos -->
                        <div class="card-stat-red hover:shadow-lg transition-smooth">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="text-sm text-gray-600 font-medium mb-1">Gastos del Mes</p>
                                    <p class="text-3xl font-bold text-red-600 mb-2">
                                        S/ {{ stats.total_expenses }}
                                    </p>
                                    <div class="flex items-center text-xs text-red-700">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M12 13a1 1 0 100 2h5a1 1 0 001-1V9a1 1 0 10-2 0v2.586l-4.293-4.293a1 1 0 00-1.414 0L8 9.586 3.707 5.293a1 1 0 00-1.414 1.414l5 5a1 1 0 001.414 0L11 9.414 14.586 13H12z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>Egresos totales</span>
                                    </div>
                                </div>
                                <div class="text-5xl">📊</div>
                            </div>
                        </div>

                        <!-- Ganancia Neta -->
                        <div class="card-stat-blue hover:shadow-lg transition-smooth md:col-span-2 lg:col-span-1">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="text-sm text-gray-600 font-medium mb-1">Ganancia Neta</p>
                                    <p class="text-3xl font-bold text-blue-600 mb-2">
                                        S/ {{ stats.net_profit }}
                                    </p>
                                    <div class="flex items-center text-xs text-blue-700">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>Ventas - Gastos</span>
                                    </div>
                                </div>
                                <div class="text-5xl">📈</div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico Top 5 Productos -->
                    <div class="card-base">
                        <h3 class="section-header">
                            <span class="icon">🏆</span>
                            Top 5 Productos Más Vendidos
                        </h3>
                        
                        <div v-if="stats.top_products.length > 0" class="mt-6" style="height: 400px;">
                            <Bar :data="chartData" :options="chartOptions" />
                        </div>
                        <div v-else class="empty-state">
                            <div class="empty-state-icon">📦</div>
                            <p class="text-lg font-medium text-gray-700 mb-2">No hay ventas registradas</p>
                            <p class="text-sm text-gray-500">Las ventas del mes aparecerán en este gráfico</p>
                        </div>
                    </div>

                    <!-- Información Adicional -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Acciones Rápidas -->
                        <div class="card-base">
                            <h3 class="section-header">
                                <span class="icon">⚡</span>
                                Acciones Rápidas
                            </h3>
                            <div class="space-y-3 mt-4">
                                <a href="/daily-registry" class="block p-3 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition-smooth">
                                    <div class="flex items-center">
                                        <span class="text-2xl mr-3">📝</span>
                                        <div>
                                            <p class="font-medium text-green-900">Registro Diario</p>
                                            <p class="text-sm text-green-700">Registrar ventas del día</p>
                                        </div>
                                    </div>
                                </a>
                                <a href="/inventory" class="block p-3 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-smooth">
                                    <div class="flex items-center">
                                        <span class="text-2xl mr-3">📦</span>
                                        <div>
                                            <p class="font-medium text-blue-900">Inventario</p>
                                            <p class="text-sm text-blue-700">Gestionar productos</p>
                                        </div>
                                    </div>
                                </a>
                                <a href="/reports" class="block p-3 bg-purple-50 border border-purple-200 rounded-lg hover:bg-purple-100 transition-smooth">
                                    <div class="flex items-center">
                                        <span class="text-2xl mr-3">📊</span>
                                        <div>
                                            <p class="font-medium text-purple-900">Reportes</p>
                                            <p class="text-sm text-purple-700">Descargar PDF de cierre</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <!-- Tips -->
                        <div class="card-base bg-gradient-to-br from-yellow-50 to-white border-yellow-200">
                            <h3 class="section-header">
                                <span class="icon">💡</span>
                                Tips del Sistema
                            </h3>
                            <div class="space-y-3 mt-4">
                                <div class="flex items-start">
                                    <span class="text-xl mr-2">✅</span>
                                    <p class="text-sm text-gray-700">Registra las ventas diarias antes del cierre de caja</p>
                                </div>
                                <div class="flex items-start">
                                    <span class="text-xl mr-2">✅</span>
                                    <p class="text-sm text-gray-700">Revisa el inventario regularmente para evitar faltantes</p>
                                </div>
                                <div class="flex items-start">
                                    <span class="text-xl mr-2">✅</span>
                                    <p class="text-sm text-gray-700">Descarga el PDF de cierre diario para tus archivos</p>
                                </div>
                                <div class="flex items-start">
                                    <span class="text-xl mr-2">✅</span>
                                    <p class="text-sm text-gray-700">Registra mermas para mantener el inventario actualizado</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
