<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';

const page = usePage();
const products = ref([]);
const nurses = ref([]);
const loading = ref(true);

// Helper para obtener fecha local de Lima (YYYY-MM-DD)
const getLimaDate = () => {
    return new Intl.DateTimeFormat('en-CA', {
        timeZone: 'America/Lima',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).format(new Date());
};

// Estado de la caja del día
const cashBox = ref({
    totalIncome: 0,
    totalExpenses: 0,
    initialBalance: 0,
    finalBalance: 0
});

// Tabla de ventas del día
const salesTable = ref([]);

// Tabla de gastos del día
const expensesTable = ref([]);

// Tabla de inyecciones de capital
const injectionsTable = ref([]);

// Tabla de mermas
const wasteTable = ref([]);

// Enfermera responsable del turno
const selectedNurse = ref('');

// Formulario de inyección de capital
const injectionForm = ref({
    amount: 0,
    reason: ''
});

// Formulario de nueva venta
const saleForm = ref({
    product_id: '',
    quantity: 1
});

// Formulario de nuevo gasto
const expenseForm = ref({
    amount: 0,
    description: ''
});

// Formulario de merma
const wasteForm = ref({
    product_id: '',
    quantity: 1,
    reason: ''
});

const selectedProduct = computed(() => {
    // Convertir a número para comparación estricta
    const productId = Number(saleForm.value.product_id);
    return products.value.find(p => p.id === productId);
});

const saleSubtotal = computed(() => {
    if (!selectedProduct.value) return 0;
    return selectedProduct.value.base_price * saleForm.value.quantity;
});

// Productos disponibles (con stock > 0)
const availableProducts = computed(() => {
    return products.value.filter(p => p.stock > 0);
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

const loadNurses = async () => {
    try {
        const token = localStorage.getItem('auth_token');
        const response = await fetch('/api/nurses/active', {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        nurses.value = await response.json();
    } catch (error) {
        console.error('Error al cargar enfermeras:', error);
    }
};

const loadInitialBalance = async () => {
    try {
        const token = localStorage.getItem('auth_token');
        
        if (!token) {
            console.warn('⚠️ No hay token de autenticación');
            return;
        }
        
        const response = await fetch('/api/daily-closings/last-balance', {
            headers: { 
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            console.error('❌ Error HTTP:', response.status);
            return;
        }
        
        const data = await response.json();
        console.log('📊 Respuesta del servidor:', data);
        
        const balance = parseFloat(data.last_balance || 0);
        cashBox.value.initialBalance = balance;
        cashBox.value.finalBalance = balance;
        
        console.log(`✅ Saldo inicial cargado: S/ ${balance.toFixed(2)}`);
    } catch (error) {
        console.error('❌ Error al cargar saldo:', error);
        // En caso de error, mantener en 0
        cashBox.value.initialBalance = 0;
        cashBox.value.finalBalance = 0;
    }
};

const addSaleRow = () => {
    if (!selectedProduct.value) {
        alert('Selecciona un producto');
        return;
    }

    if (saleForm.value.quantity < 1) {
        alert('La cantidad debe ser mayor a 0');
        return;
    }

    // Validar stock disponible
    if (saleForm.value.quantity > selectedProduct.value.stock) {
        alert(`Stock insuficiente. Disponible: ${selectedProduct.value.stock} unidades`);
        return;
    }

    salesTable.value.push({
        product_id: selectedProduct.value.id,
        product_name: selectedProduct.value.name,
        quantity: saleForm.value.quantity,
        unit_price: Number(selectedProduct.value.base_price),
        subtotal: saleForm.value.quantity * Number(selectedProduct.value.base_price)
    });

    // Actualizar stock localmente (solo en frontend)
    selectedProduct.value.stock -= saleForm.value.quantity;

    // Actualizar totales
    cashBox.value.totalIncome += saleForm.value.quantity * selectedProduct.value.base_price;
    const totalInjections = injectionsTable.value.reduce((sum, inj) => sum + inj.amount, 0);
    cashBox.value.finalBalance = (cashBox.value.totalIncome - cashBox.value.totalExpenses + totalInjections) + cashBox.value.initialBalance;

    // Resetear formulario
    saleForm.value = { product_id: '', quantity: 1 };
};

// Validar cantidad de venta en tiempo real
const validateSaleQuantity = () => {
    if (!selectedProduct.value) return;
    if (saleForm.value.quantity > selectedProduct.value.stock) {
        saleForm.value.quantity = selectedProduct.value.stock;
    }
    if (saleForm.value.quantity < 1) {
        saleForm.value.quantity = 1;
    }
};

const removeSaleRow = (index) => {
    const row = salesTable.value[index];
    
    // Restaurar stock localmente
    const product = products.value.find(p => p.id === row.product_id);
    if (product) {
        product.stock += row.quantity;
    }
    
    cashBox.value.totalIncome -= row.subtotal;
    const totalInjections = injectionsTable.value.reduce((sum, inj) => sum + inj.amount, 0);
    cashBox.value.finalBalance = (cashBox.value.totalIncome - cashBox.value.totalExpenses + totalInjections) + cashBox.value.initialBalance;
    salesTable.value.splice(index, 1);
};

const addExpenseRow = () => {
    if (expenseForm.value.amount <= 0) {
        alert('El monto debe ser mayor a 0');
        return;
    }

    if (!expenseForm.value.description.trim()) {
        alert('Ingresa una descripción');
        return;
    }

    expensesTable.value.push({
        amount: parseFloat(expenseForm.value.amount),
        description: expenseForm.value.description
    });

    // Actualizar totales
    cashBox.value.totalExpenses += parseFloat(expenseForm.value.amount);
    const totalInjections = injectionsTable.value.reduce((sum, inj) => sum + inj.amount, 0);
    cashBox.value.finalBalance = (cashBox.value.totalIncome - cashBox.value.totalExpenses + totalInjections) + cashBox.value.initialBalance;

    // Resetear formul
ario
    expenseForm.value = { amount: 0, description: '' };
};

const removeExpenseRow = (index) => {
    const row = expensesTable.value[index];
    cashBox.value.totalExpenses -= row.amount;
   const totalInjections = injectionsTable.value.reduce((sum, inj) => sum + inj.amount, 0);
    cashBox.value.finalBalance = (cashBox.value.totalIncome - cashBox.value.totalExpenses + totalInjections) + cashBox.value.initialBalance;
    expensesTable.value.splice(index, 1);
};

const addInjectionRow = () => {
    if (injectionForm.value.amount <= 0) {
        alert('El monto debe ser mayor a 0');
        return;
    }

    if (!injectionForm.value.reason.trim()) {
        alert('Ingresa el motivo');
        return;
    }

    injectionsTable.value.push({
        amount: parseFloat(injectionForm.value.amount),
        reason: injectionForm.value.reason
    });

    // Actualizar totales
    const totalInjections = injectionsTable.value.reduce((sum, inj) => sum + inj.amount, 0);
    cashBox.value.finalBalance = (cashBox.value.totalIncome - cashBox.value.totalExpenses + totalInjections) + cashBox.value.initialBalance;

    // Resetear formulario
    injectionForm.value = { amount: 0, reason: '' };
};

const removeInjectionRow = (index) => {
    injectionsTable.value.splice(index, 1);
    const totalInjections = injectionsTable.value.reduce((sum, inj) => sum + inj.amount, 0);
    cashBox.value.finalBalance = (cashBox.value.totalIncome - cashBox.value.totalExpenses + totalInjections) + cashBox.value.initialBalance;
};

// Funciones de Mermas
const addWasteRow = () => {
    const product = products.value.find(p => p.id == wasteForm.value.product_id);
    if (!product) {
        alert('Selecciona un producto');
        return;
    }

    if (wasteForm.value.quantity < 1) {
        alert('La cantidad debe ser mayor a 0');
        return;
    }

    if (!wasteForm.value.reason.trim()) {
        alert('Ingresa el motivo de la merma');
        return;
    }

    if (product.stock < wasteForm.value.quantity) {
        alert(`Stock insuficiente. Disponible: ${product.stock} unidades`);
        return;
    }

    wasteTable.value.push({
        product_id: product.id,
        product_name: product.name,
        quantity: wasteForm.value.quantity,
        reason: wasteForm.value.reason
    });

    // Actualizar stock localmente (solo en frontend)
    product.stock -= wasteForm.value.quantity;

    // Resetear formulario
    wasteForm.value = { product_id: '', quantity: 1, reason: '' };
};

const removeWasteRow = (index) => {
    const row = wasteTable.value[index];
    
    // Restaurar stock localmente
    const product = products.value.find(p => p.id === row.product_id);
    if (product) {
        product.stock += row.quantity;
    }
    
    wasteTable.value.splice(index, 1);
};

// Validar cantidad de merma en tiempo real
const validateWasteQuantity = () => {
    const product = products.value.find(p => p.id == wasteForm.value.product_id);
    if (!product) return;
    if (wasteForm.value.quantity > product.stock) {
        wasteForm.value.quantity = product.stock;
    }
    if (wasteForm.value.quantity < 1) {
        wasteForm.value.quantity = 1;
    }
};

const saveDailyRegistry = async () => {
    if (salesTable.value.length === 0 && expensesTable.value.length === 0 && injectionsTable.value.length === 0 && wasteTable.value.length === 0) {
        alert('⚠️ No hay datos para guardar');
        return;
    }

    // Validar que se haya seleccionado una enfermera si hay ventas
    if (salesTable.value.length > 0 && !selectedNurse.value) {
        alert('⚠️ Debe seleccionar la enfermera/tutora responsable del turno');
        return;
    }

    if (!confirm(`¿Guardar registro del día?\n\nIngresos: S/ ${cashBox.value.totalIncome.toFixed(2)}\nEgresos: S/ ${cashBox.value.totalExpenses.toFixed(2)}\nSaldo: S/ ${cashBox.value.finalBalance.toFixed(2)}`)) {
        return;
    }

    const token = localStorage.getItem('auth_token');

    try {
        // Guardar ventas (con enfermera responsable)
        if (salesTable.value.length > 0) {
            const saleData = {
                sale_date: getLimaDate(),
                nurse_id: selectedNurse.value,
                items: salesTable.value.map(row => ({
                    product_id: row.product_id,
                    quantity: row.quantity
                }))
            };

            const saleResponse = await fetch('/api/sales', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify(saleData)
            });

            if (!saleResponse.ok) {
                throw new Error('Error al guardar ventas');
            }
        }

        // Guardar gastos
        for (const expense of expensesTable.value) {
            const expenseData = {
                expense_date: getLimaDate(),
                amount: expense.amount,
                description: expense.description
            };

            const expenseResponse = await fetch('/api/expenses', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify(expenseData)
            });

            if (!expenseResponse.ok) {
                throw new Error('Error al guardar gastos');
            }
        }

        // Guardar inyecciones de capital
        for (const injection of injectionsTable.value) {
            const injectionData = {
                injection_date: getLimaDate(),
                amount: injection.amount,
                reason: injection.reason
            };

            try {
                const injectionResponse = await fetch('/api/capital-injections', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify(injectionData)
                });

                if (!injectionResponse.ok) {
                    // Log pero no fallar - la inyección puede haberse guardado
                    console.warn('⚠️ Respuesta no OK para inyección, pero puede haberse guardado');
                }
            } catch (error) {
                // Log pero continuar - no detener el guardado por este error
                console.warn('⚠️ Error al guardar inyección (puede haberse guardado):', error);
            }
        }

        // Guardar mermas
        for (const waste of wasteTable.value) {
            const wasteData = {
                product_id: waste.product_id,
                quantity: waste.quantity,
                reason: waste.reason,
                waste_date: getLimaDate()
            };

            try {
                const wasteResponse = await fetch('/api/waste-records', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify(wasteData)
                });

                if (!wasteResponse.ok) {
                    console.warn('⚠️ Error al guardar merma');
                }
            } catch (error) {
                console.warn('⚠️ Error al guardar merma:', error);
            }
        }

        alert('✅ Registro diario guardado exitosamente');
        
        // CREAR CIERRE DIARIO AUTOMÁTICAMENTE
        const today = getLimaDate();
        const totalInjections = injectionsTable.value.reduce((sum, inj) => sum + inj.amount, 0);
        const newFinalBalance = (cashBox.value.totalIncome - cashBox.value.totalExpenses + totalInjections) + cashBox.value.initialBalance;
        
        try {
            const closingResponse = await fetch('/api/daily-closings/calculate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({ closing_date: today })
            });

            if (closingResponse.ok) {
                console.log('✓ Cierre diario creado');
            }
        } catch (error) {
            console.warn('Error al crear cierre:', error);
        }
        
        // Limpiar tablas
        salesTable.value = [];
        expensesTable.value = [];
        injectionsTable.value = [];
        wasteTable.value = [];
        selectedNurse.value = '';
        
        // RECARGAR SALDO INICIAL desde el cierre actualizado
        await loadInitialBalance();
        
        // Resetear contadores visuales
        cashBox.value.totalIncome = 0;
        cashBox.value.totalExpenses = 0;
        cashBox.value.finalBalance = cashBox.value.initialBalance;

    } catch (error) {
        alert('❌ Error al guardar: ' + error.message);
    }
};

onMounted(() => {
    loadProducts();
    loadNurses();
    loadInitialBalance();
});

// Observar cambios en el producto seleccionado para ventas
watch(() => saleForm.value.product_id, () => {
    validateSaleQuantity();
});

// Observar cambios en el producto seleccionado para mermas
watch(() => wasteForm.value.product_id, () => {
    validateWasteQuantity();
});
</script>

<template>
    <Head title="Registro Diario" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                📖 Registro Diario de Tesorería
            </h2>
        </template>

        <div class="py-6 md:py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Indicadores de Caja Mejorados -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="card-stat-blue hover:shadow-md transition-smooth">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 font-medium mb-1">Saldo Inicial</p>
                                <p class="text-2xl font-bold text-blue-600">S/ {{ cashBox.initialBalance.toFixed(2) }}</p>
                            </div>
                            <div class="text-4xl">💼</div>
                        </div>
                    </div>
                    <div class="card-stat-green hover:shadow-md transition-smooth">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 font-medium mb-1">Total Ingresos</p>
                                <p class="text-3xl font-bold text-green-600">S/ {{ cashBox.totalIncome.toFixed(2) }}</p>
                            </div>
                            <div class="text-4xl">💰</div>
                        </div>
                    </div>
                    <div class="card-stat-red hover:shadow-md transition-smooth">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 font-medium mb-1">Total Egresos</p>
                                <p class="text-3xl font-bold text-red-600">S/ {{ cashBox.totalExpenses.toFixed(2) }}</p>
                            </div>
                            <div class="text-4xl">📊</div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-green-500 to-emerald-600 p-6 rounded-lg shadow-lg hover:shadow-xl transition-smooth">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-white/90 font-medium mb-1">Saldo Final</p>
                                <p class="text-3xl font-bold text-white">S/ {{ cashBox.finalBalance.toFixed(2) }}</p>
                            </div>
                            <div class="text-5xl">💸</div>
                        </div>
                    </div>
                </div>

                <!-- Selector de Enfermera Responsable -->
                <div class="card-base bg-gradient-to-r from-purple-50 to-indigo-50 border-l-4 border-purple-500">
                    <h3 class="section-header text-purple-800">
                        <span class="icon">👩‍⚕️</span> Responsable del Turno
                    </h3>
                    <div class="max-w-md">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Seleccionar Enfermera/Tutora *
                        </label>
                        <select 
                            v-model="selectedNurse"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            :required="salesTable.length > 0"
                        >
                            <option value="">-- Seleccionar responsable --</option>
                            <option 
                                v-for="nurse in nurses" 
                                :key="nurse.id" 
                                :value="nurse.id"
                            >
                                {{ nurse.name }}
                            </option>
                        </select>
                        <p class="text-xs text-gray-500 mt-2">
                            ℹ️ Obligatorio si registra ventas. Indica quién entregó la hoja de ventas.
                        </p>
                    </div>
                </div>

                <!-- Sección de Ventas (Ingresos) -->
                <div class="card-base border-l-4 border-green-500">
                    <h3 class="section-header text-green-800">
                        <span class="icon">💰</span> Ingresos (Ventas del Día)
                    </h3>

                    <!-- Formulario de Carga Rápida -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 p-5 bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl border border-green-200">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Producto</label>
                            <select
                                v-model="saleForm.product_id"
                                class="input-base h-11"
                            >
                                <option value="">Seleccionar producto...</option>
                                <option v-for="product in availableProducts" :key="product.id" :value="product.id">
                                    {{ product.name }} - S/ {{ product.base_price }} (Stock: {{ product.stock }})
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Cantidad</label>
                            <input
                                v-model.number="saleForm.quantity"
                                @input="validateSaleQuantity"
                                type="number"
                                min="1"
                                :max="selectedProduct ? selectedProduct.stock : 999"
                                class="input-base h-11"
                                placeholder="1"
                            />
                        </div>
                        <div class="flex items-end">
                            <button
                                @click="addSaleRow"
                                class="btn-primary w-full h-11 text-base font-semibold shadow-sm"
                            >
                                ➕ S/ {{ saleSubtotal.toFixed(2) }}
                            </button>
                        </div>
                    </div>

                    <!-- Tabla de Ventas -->
                    <div class="overflow-x-auto">
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>P. Unitario</th>
                                    <th>Subtotal</th>
                                    <th class="text-right">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="(row, index) in salesTable" :key="index" class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-sm">{{ row.product_name }}</td>
                                    <td class="px-4 py-2 text-sm">{{ row.quantity }}</td>
                                    <td class="px-4 py-2 text-sm font-medium text-gray-600">S/ {{ row.unit_price.toFixed(2) }}</td>
                                    <td class="px-4 py-2 text-sm font-bold text-green-600">S/ {{ row.subtotal.toFixed(2) }}</td>
                                    <td class="px-4 py-2 text-right">
                                        <button @click="removeSaleRow(index)" class="text-red-600 hover:text-red-800">
                                            🗑️
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="salesTable.length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500 text-sm">
                                        No hay ventas registradas aún
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Sección de Gastos (Egresos) -->
                <div class="card-base border-l-4 border-red-500">
                    <h3 class="section-header text-red-800">
                        <span class="icon">📉</span> Egresos (Gastos Operativos)
                    </h3>

                    <!-- Formulario de Gasto -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 p-5 bg-gradient-to-r from-red-50 to-rose-50 rounded-xl border border-red-200">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Monto (S/)</label>
                            <input
                                v-model.number="expenseForm.amount"
                                type="number"
                                step="0.01"
                                min="0"
                                class="input-base h-11"
                                placeholder="0.00"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Descripción</label>
                            <input
                                v-model="expenseForm.description"
                                type="text"
                                class="input-base h-11"
                                placeholder="Ej: Compra de harina"
                            />
                        </div>
                        <div class="flex items-end">
                            <button 
                                @click="addExpenseRow" 
                                class="btn-danger w-full h-11 text-base font-semibold shadow-sm"
                            >
                                ➕ Agregar
                            </button>
                        </div>
                    </div>

                    <!-- Tabla de Gastos -->
                    <div class="overflow-x-auto">
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th>Descripción</th>
                                    <th>Monto</th>
                                    <th class="text-right">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="(row, index) in expensesTable" :key="index" class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-sm">{{ row.description }}</td>
                                    <td class="px-4 py-2 text-sm font-bold text-red-600">S/ {{ row.amount.toFixed(2) }}</td>
                                    <td class="px-4 py-2 text-right">
                                        <button @click="removeExpenseRow(index)" class="text-red-600 hover:text-red-800">
                                            🗑️
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="expensesTable.length === 0">
                                    <td colspan="3" class="px-4 py-8 text-center text-gray-500 text-sm">
                                        No hay gastos registrados aún
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Sección de Mermas (Bajas de Stock) -->
                <div class="card-base border-l-4 border-orange-500">
                    <h3 class="section-header text-orange-800">
                        <span class="icon">📩</span> Mermas / Bajas de Inventario
                    </h3>
                    <p class="text-sm text-gray-600 mb-6 bg-orange-50 p-3 rounded-lg border border-orange-100">
                        📌 Registra consumo de residentes, productos dañados o perdidos. Descuenta stock pero NO afecta el dinero de caja.
                    </p>

                    <!-- Formulario de Merma -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 p-5 bg-gradient-to-r from-orange-50 to-amber-50 rounded-xl border border-orange-200">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Producto</label>
                            <select
                                v-model="wasteForm.product_id"
                                class="input-base h-11"
                            >
                                <option value="">Seleccionar...</option>
                                <option v-for="product in products" :key="product.id" :value="product.id">
                                    {{ product.name }} (Stock: {{ product.stock }})
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Cantidad</label>
                            <input
                                v-model.number="wasteForm.quantity"
                                @input="validateWasteQuantity"
                                type="number"
                                min="1"
                                :max="products.find(p => p.id == wasteForm.product_id)?.stock || 999"
                                class="input-base h-11"
                                placeholder="1"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Motivo</label>
                            <input
                                v-model="wasteForm.reason"
                                type="text"
                                class="input-base h-11"
                                placeholder="Ej: Consumo de residentes"
                            />
                        </div>
                        <div class="flex items-end">
                            <button
                                @click="addWasteRow"
                                class="w-full h-11 bg-orange-600 text-white rounded-lg hover:bg-orange-700 font-bold transition-smooth shadow-sm"
                            >
                                ➕ Agregar
                            </button>
                        </div>
                    </div>

                    <!-- Tabla de Mermas -->
                    <div class="overflow-x-auto">
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Motivo</th>
                                    <th class="text-right">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="(row, index) in wasteTable" :key="index" class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-sm">{{ row.product_name }}</td>
                                    <td class="px-4 py-2 text-sm font-bold text-orange-600">{{ row.quantity }} unidades</td>
                                    <td class="px-4 py-2 text-sm text-gray-600">{{ row.reason }}</td>
                                    <td class="px-4 py-2 text-right">
                                        <button @click="removeWasteRow(index)" class="text-red-600 hover:text-red-800">
                                            🗑️
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="wasteTable.length === 0">
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-500 text-sm">
                                        No hay mermas registradas aún
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Sección de Inyección de Capital (Requiere Permiso) -->
                <div v-if="$page.props.auth.permissions && $page.props.auth.permissions.includes('inject_capital')" class="card-base border-l-4 border-yellow-500 bg-yellow-50/30">
                    <h3 class="section-header text-yellow-800">
                        <span class="icon">💵</span> Inyección de Capital
                    </h3>

                    <div class="bg-yellow-50 p-4 rounded-lg mb-6 text-sm text-yellow-800 border border-yellow-200">
                        <strong>⚠️ Solo Administrador:</strong> Usa esta función para registrar ingresos extraordinarios como donaciones o aportes externos que incrementan el saldo inicial.
                    </div>

                    <!-- Formulario de Inyección -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 p-5 bg-gradient-to-r from-yellow-50 to-amber-50 rounded-xl border border-yellow-200">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Monto (S/)</label>
                            <input
                                v-model.number="injectionForm.amount"
                                type="number"
                                step="0.01"
                                min="0"
                                class="input-base h-11"
                                placeholder="0.00"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Motivo / Nota</label>
                            <input
                                v-model="injectionForm.reason"
                                type="text"
                                class="input-base h-11"
                                placeholder="Ej: Donación externa"
                            />
                        </div>
                        <div class="flex items-end">
                            <button
                                @click="addInjectionRow"
                                class="w-full h-11 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 font-bold transition-smooth shadow-sm"
                            >
                                ➕ Agregar
                            </button>
                        </div>
                    </div>

                    <!-- Tabla de Inyecciones -->
                    <div class="overflow-x-auto">
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th>Motivo</th>
                                    <th>Monto</th>
                                    <th class="text-right">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="(row, index) in injectionsTable" :key="index" class="hover:bg-gray-50 bg-yellow-50">
                                    <td class="px-4 py-2 text-sm">{{ row.reason }}</td>
                                    <td class="px-4 py-2 text-sm font-bold text-yellow-600">S/ {{ row.amount.toFixed(2) }}</td>
                                    <td class="px-4 py-2 text-right">
                                        <button @click="removeInjectionRow(index)" class="text-red-600 hover:text-red-800">
                                            🗑️
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="injectionsTable.length === 0">
                                    <td colspan="3" class="px-4 py-8 text-center text-gray-500 text-sm">
                                        No hay inyecciones de capital aún
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Botón Guardar Registro -->
                <div class="flex justify-end pt-6">
                    <button
                        @click="saveDailyRegistry"
                        class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white px-10 py-4 rounded-xl hover:shadow-2xl font-bold text-xl transition-all hover:-translate-y-1 active:scale-95 shadow-lg flex items-center"
                    >
                        <span class="mr-2">💾</span> Guardar Registro del Día
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
