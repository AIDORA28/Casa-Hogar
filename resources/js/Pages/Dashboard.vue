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
                Dashboard - Casa Hogar
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Loading State -->
                <div v-if="loading" class="text-center py-12">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-gray-900"></div>
                    <p class="mt-4 text-gray-600">Cargando estadísticas...</p>
                </div>

                <!-- Dashboard Content -->
                <div v-else>
                    <!-- Tarjetas de Resumen -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <!-- Ventas -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <div class="flex items-center">
                                    <div class="text-4xl mr-4">💰</div>
                                    <div>
                                        <p class="text-sm text-gray-600">Ventas del Mes</p>
                                        <p class="text-2xl font-bold text-green-600">
                                            S/ {{ stats.total_sales }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Gastos -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <div class="flex items-center">
                                    <div class="text-4xl mr-4">📊</div>
                                    <div>
                                        <p class="text-sm text-gray-600">Gastos del Mes</p>
                                        <p class="text-2xl font-bold text-red-600">
                                            S/ {{ stats.total_expenses }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Ganancia -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <div class="flex items-center">
                                    <div class="text-4xl mr-4">📈</div>
                                    <div>
                                        <p class="text-sm text-gray-600">Ganancia Neta</p>
                                        <p class="text-2xl font-bold text-blue-600">
                                            S/ {{ stats.net_profit }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico Top 5 Productos -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div v-if="stats.top_products.length > 0" style="height: 400px;">
                            <Bar :data="chartData" :options="chartOptions" />
                        </div>
                        <div v-else class="text-center py-12 text-gray-500">
                            No hay ventas registradas este mes
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
