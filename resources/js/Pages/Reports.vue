<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

// Helper para obtener fecha local de Lima (YYYY-MM-DD)
const getLimaDate = () => {
    return new Intl.DateTimeFormat('en-CA', {
        timeZone: 'America/Lima',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).format(new Date());
};

const selectedDate = ref(getLimaDate());
const downloading = ref(false);
const showDetail = ref(false);
const detailData = ref(null);
const loadingDetail = ref(false);

// Filtros para PDF (todos activos por defecto)
const pdfFilters = ref({
    includeSales: true,
    includeExpenses: true,
    includeInjections: true,
    includeWaste: true
});

const downloadPDF = async () => {
    // Validar que al menos un filtro esté activo
    const hasAtLeastOne = Object.values(pdfFilters.value).some(v => v);
    if (!hasAtLeastOne) {
        alert('⚠️ Debes seleccionar al menos una sección para el PDF');
        return;
    }

    downloading.value = true;
    try {
        const token = localStorage.getItem('auth_token');
        const params = new URLSearchParams({
            include_sales: pdfFilters.value.includeSales ? '1' : '0',
            include_expenses: pdfFilters.value.includeExpenses ? '1' : '0',
            include_injections: pdfFilters.value.includeInjections ? '1' : '0',
            include_waste: pdfFilters.value.includeWaste ? '1' : '0'
        });
        
        const url = `/api/reports/daily-closing-pdf/${selectedDate.value}?${params}`;
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
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h2 class="font-bold text-2xl text-gray-800 leading-tight flex items-center">
                        <span class="mr-3">📊</span> Reportes y Auditoría
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Consulta cierres diarios y descárgalos en PDF</p>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- Card Principal de Selección -->
                <div class="card-base border-l-4 border-blue-500 overflow-hidden">
                    <!-- Header con Degradado sutil -->
                    <div class="px-6 py-5 bg-gradient-to-r from-blue-50 to-white border-b border-blue-100">
                        <h3 class="section-header text-blue-900 !mb-0">
                            <span class="icon">📅</span> Generar Reporte de Cierre
                        </h3>
                        <p class="text-xs font-black text-blue-400 uppercase tracking-[0.2em] mt-2">Documentación Oficial</p>
                    </div>
                    
                    <div class="px-6 pb-6 space-y-8">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                            <div class="md:col-span-1">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Seleccionar Fecha
                                </label>
                                <div class="relative">
                                    <input
                                        v-model="selectedDate"
                                        type="date"
                                        class="input-base h-11 bg-gradient-to-r from-blue-50/50 to-white"
                                    />
                                </div>
                            </div>
                            
                            <div class="md:col-span-2 flex gap-4">
                                <button
                                    @click="loadDailyDetail"
                                    :disabled="loadingDetail"
                                    class="flex-1 h-11 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-all hover:shadow-lg disabled:bg-gray-400 font-bold flex items-center justify-center"
                                >
                                    <span v-if="loadingDetail" class="animate-spin mr-2">⏳</span>
                                    <span v-else class="mr-2">🔍</span>
                                    Ver Desglose en Pantalla
                                </button>
                                
                                <button
                                    @click="downloadPDF"
                                    :disabled="downloading"
                                    class="flex-1 h-11 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-all hover:shadow-lg disabled:bg-gray-400 font-bold flex items-center justify-center"
                                >
                                    <span v-if="downloading" class="animate-spin mr-2">⏳</span>
                                    <span v-else class="mr-2">📄</span>
                                    Descargar PDF
                                </button>
                            </div>
                        </div>

                        <!-- Filtros para PDF con diseño mejorado -->
                        <div class="p-6 bg-gray-50 rounded-2xl border border-gray-100">
                            <label class="block text-sm font-bold text-gray-700 mb-4 flex items-center">
                                <span class="mr-2">⚙️</span> Secciones a Incluir en el Documento:
                            </label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <label class="flex items-center p-3 bg-white rounded-xl border border-gray-200 cursor-pointer hover:border-green-400 transition-smooth group">
                                    <input
                                        v-model="pdfFilters.includeSales"
                                        type="checkbox"
                                        class="w-5 h-5 text-green-600 border-gray-300 rounded-lg focus:ring-green-500"
                                    />
                                    <span class="ml-3 text-sm font-medium group-hover:text-green-700">💰 Ventas</span>
                                </label>
                                <label class="flex items-center p-3 bg-white rounded-xl border border-gray-200 cursor-pointer hover:border-red-400 transition-smooth group">
                                    <input
                                        v-model="pdfFilters.includeExpenses"
                                        type="checkbox"
                                        class="w-5 h-5 text-red-600 border-gray-300 rounded-lg focus:ring-red-500"
                                    />
                                    <span class="ml-3 text-sm font-medium group-hover:text-red-700">📊 Gastos</span>
                                </label>
                                <label class="flex items-center p-3 bg-white rounded-xl border border-gray-200 cursor-pointer hover:border-yellow-400 transition-smooth group">
                                    <input
                                        v-model="pdfFilters.includeInjections"
                                        type="checkbox"
                                        class="w-5 h-5 text-yellow-600 border-gray-300 rounded-lg focus:ring-yellow-500"
                                    />
                                    <span class="ml-3 text-sm font-medium group-hover:text-yellow-700">💵 Inyecciones</span>
                                </label>
                                <label class="flex items-center p-3 bg-white rounded-xl border border-gray-200 cursor-pointer hover:border-orange-400 transition-smooth group">
                                    <input
                                        v-model="pdfFilters.includeWaste"
                                        type="checkbox"
                                        class="w-5 h-5 text-orange-600 border-gray-300 rounded-lg focus:ring-orange-500"
                                    />
                                    <span class="ml-3 text-sm font-medium group-hover:text-orange-700">📉 Mermas</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Desglose Detallado Mejorado -->
                <div v-if="showDetail && detailData" class="card-base border-2 border-blue-500 overflow-hidden animate-fade-in">
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-6 py-4 flex justify-between items-center">
                        <h3 class="font-bold text-lg flex items-center">
                            <span class="text-xl mr-2">🔍</span> Desglose del Día: {{ selectedDate }}
                        </h3>
                        <button @click="closeDetail" class="bg-white/10 hover:bg-white/20 p-2 rounded-lg transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="p-8 space-y-10">
                        <!-- Resumen Unificado con card-stat -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div class="card-stat-green">
                                <p class="text-xs text-green-700 font-bold uppercase tracking-wider mb-1">Ventas</p>
                                <p class="text-2xl font-black text-green-600">S/ {{ detailData.summary.total_sales }}</p>
                            </div>
                            <div class="card-stat-red">
                                <p class="text-xs text-red-700 font-bold uppercase tracking-wider mb-1">Gastos</p>
                                <p class="text-2xl font-black text-red-600">S/ {{ detailData.summary.total_expenses }}</p>
                            </div>
                            <div class="card-stat-yellow">
                                <p class="text-xs text-amber-700 font-bold uppercase tracking-wider mb-1">Inyecciones</p>
                                <p class="text-2xl font-black text-amber-600">S/ {{ detailData.summary.total_injections }}</p>
                            </div>
                            <div class="card-stat-blue">
                                <p class="text-xs text-blue-700 font-bold uppercase tracking-wider mb-1">Balance Neto</p>
                                <p class="text-2xl font-black text-blue-600">S/ {{ detailData.summary.net_balance }}</p>
                            </div>
                        </div>

                        <!-- Grid de Desglose (2 Columnas) -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-20">
                            
                            <!-- Columna Izquierda: INGRESOS -->
                            <div class="space-y-8">
                                <h4 class="text-lg font-bold text-gray-800 flex items-center border-b pb-2 border-green-100 uppercase tracking-widest text-xs">
                                    <span class="mr-2">📈</span> Ingresos y Captaciones
                                </h4>

                                <!-- Ventas -->
                                <div v-if="detailData.sales.length > 0" class="space-y-6">
                                    <h5 class="font-bold text-green-700 text-sm flex items-center px-1">
                                        💰 Detalle de Ventas ({{ detailData.sales.length }})
                                    </h5>
                                    <div class="space-y-6">
                                        <div v-for="sale in detailData.sales" :key="sale.id" class="card-base border-t-4 border-green-500 bg-white shadow-sm hover:shadow-md transition-smooth p-6">
                                            <!-- Ticket Header -->
                                            <div class="flex justify-between items-start border-b border-dashed border-gray-200 pb-4 mb-4">
                                                <div>
                                                    <p class="font-black text-gray-800 tracking-tight">TICKET #{{ sale.id }}</p>
                                                    <p class="text-[10px] text-gray-400 font-bold uppercase">{{ sale.user }} • {{ sale.created_at }}</p>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-[10px] font-black text-blue-600 uppercase tracking-tighter">Personal Responsable</p>
                                                    <p class="text-xs font-bold text-gray-700">{{ sale.nurse }}</p>
                                                </div>
                                            </div>

                                            <!-- Ticket Body (Items) -->
                                            <div class="space-y-3 mb-6">
                                                <div v-for="(item, idx) in sale.items" :key="idx" class="flex justify-between items-center text-sm">
                                                    <div class="flex-1">
                                                        <span class="text-gray-800 font-medium">{{ item.product }}</span>
                                                        <span class="text-gray-400 ml-2 font-bold text-xs uppercase italic">x{{ item.quantity }}</span>
                                                    </div>
                                                    <div class="text-gray-600 font-bold ml-4">
                                                        S/ {{ item.subtotal }}
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Ticket Footer (Total Price at the Bottom) -->
                                            <div class="border-t-2 border-gray-100 pt-4 flex flex-col items-end">
                                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total a Pagar</p>
                                                <div class="flex items-baseline">
                                                    <span class="text-lg font-bold text-green-600 mr-2">S/</span>
                                                    <span class="text-3xl font-black text-green-600 tracking-tighter">{{ sale.total }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Inyecciones de Capital (Tabla) -->
                                <div v-if="detailData.injections.length > 0" class="space-y-4">
                                    <h5 class="font-bold text-amber-700 text-sm flex items-center">
                                        💵 Inyecciones de Capital ({{ detailData.injections.length }})
                                    </h5>
                                    <div class="card-base p-0 overflow-hidden border-amber-200">
                                        <table class="table-modern">
                                            <thead class="bg-amber-50">
                                                <tr>
                                                    <th>Motivo / Razón</th>
                                                    <th class="text-right">Monto (S/)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="injection in detailData.injections" :key="injection.id">
                                                    <td>
                                                        <p class="font-medium text-gray-900">{{ injection.reason }}</p>
                                                        <p class="text-xs text-gray-500">{{ injection.user }}</p>
                                                    </td>
                                                    <td class="text-right font-bold text-amber-600">S/ {{ injection.amount }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div v-if="detailData.sales.length === 0 && detailData.injections.length === 0" class="text-center py-10 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                                    <p class="text-gray-400 text-sm">No se registraron ingresos</p>
                                </div>
                            </div>

                            <!-- Columna Derecha: SALIDAS Y AJUSTES -->
                            <div class="space-y-8">
                                <h4 class="text-lg font-bold text-gray-800 flex items-center border-b pb-2 border-red-100">
                                    <span class="mr-2">📉</span> Egresos y Ajustes
                                </h4>

                                <!-- Gastos (Tabla) -->
                                <div v-if="detailData.expenses.length > 0" class="space-y-4">
                                    <h5 class="font-bold text-red-700 text-sm flex items-center">
                                        📊 Gastos del Día ({{ detailData.expenses.length }})
                                    </h5>
                                    <div class="card-base p-0 overflow-hidden border-red-200">
                                        <table class="table-modern">
                                            <thead class="bg-red-50">
                                                <tr>
                                                    <th>Descripción</th>
                                                    <th class="text-right">Monto (S/)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="expense in detailData.expenses" :key="expense.id">
                                                    <td>
                                                        <p class="font-medium text-gray-900">{{ expense.description }}</p>
                                                        <p class="text-xs text-gray-500">{{ expense.user }}</p>
                                                    </td>
                                                    <td class="text-right font-bold text-red-600">S/ {{ expense.amount }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Mermas / Bajas de Inventario (Tabla) -->
                                <div v-if="detailData.waste_records && detailData.waste_records.length > 0" class="space-y-4">
                                    <h5 class="font-bold text-orange-700 text-sm flex items-center">
                                        📉 Bajas de Inventario / Mermas ({{ detailData.waste_records.length }})
                                    </h5>
                                    <div class="card-base p-0 overflow-hidden border-orange-200">
                                        <table class="table-modern">
                                            <thead class="bg-orange-50">
                                                <tr>
                                                    <th>Producto</th>
                                                    <th>Cantidad</th>
                                                    <th>Motivo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="waste in detailData.waste_records" :key="waste.id">
                                                    <td class="font-bold text-gray-900">{{ waste.product }}</td>
                                                    <td class="text-center font-bold text-orange-600">{{ waste.quantity }}</td>
                                                    <td class="italic text-gray-600 text-xs">{{ waste.reason }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div v-if="detailData.expenses.length === 0 && detailData.waste_records.length === 0" class="text-center py-10 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                                    <p class="text-gray-400 text-sm">No se registraron egresos o mermas</p>
                                </div>
                            </div>
                        </div>

                        <!-- Empty States Global -->
                        <div v-if="detailData.sales.length === 0 && detailData.expenses.length === 0 && detailData.injections.length === 0" class="text-center py-12 text-gray-400">
                            <span class="text-5xl block mb-4">📭</span>
                            Sin registros para esta fecha
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
