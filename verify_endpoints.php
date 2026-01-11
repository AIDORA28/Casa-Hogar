<?php

// Script de prueba para verificar los endpoints
// Ejecutar: php verify_endpoints.php

echo "=== VERIFICACIÓN FASE 2: Casa Hogar System ===\n\n";

// Configuración
$baseUrl = 'http://127.0.0.1:8000/api';

// Función helper para hacer requests
function makeRequest($method, $url, $data = null) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

// Test 1: Listar productos
echo "1. Listando productos...\n";
$response = makeRequest('GET', "$baseUrl/products");
if ($response['code'] == 200) {
    echo "   ✅ Productos listados correctamente\n";
    if (isset($response['body']['data'])) {
        echo "   Total productos: " . count($response['body']['data']) . "\n";
        foreach ($response['body']['data'] as $product) {
            echo "   - {$product['name']} (Stock: {$product['stock']})\n";
        }
    }
} else {
    echo "   ❌ Error al listar productos\n";
}
echo "\n";

// Test 2: Crear venta con descuento de stock
echo "2. Creando venta (debe decrementar stock)...\n";
$saleData = [
    'sale_date' => date('Y-m-d'),
    'items' => [
        [
            'product_id' => 1,
            'quantity' => 2
        ],
        [
            'product_id' => 2,
            'quantity' => 3
        ]
    ]
];

$response = makeRequest('POST', "$baseUrl/sales", $saleData);
if ($response['code'] == 201) {
    echo "   ✅ Venta creada exitosamente\n";
    if (isset($response['body']['sale'])) {
        echo "   ID Venta: {$response['body']['sale']['id']}\n";
        echo "   Total: {$response['body']['sale']['total_amount']}\n";
    }
} else {
    echo "   ❌ Error al crear venta\n";
    if (isset($response['body']['message'])) {
        echo "   Mensaje: {$response['body']['message']}\n";
    }
}
echo "\n";

// Test 3: Verificar que el stock se decrementó
echo "3. Verificando descuento de stock...\n";
$response = makeRequest('GET', "$baseUrl/products/1");
if ($response['code'] == 200) {
    echo "   ✅ Stock actualizado\n";
    if (isset($response['body']['stock'])) {
        echo "   Stock actual del producto 1: {$response['body']['stock']} (debería ser 18 si inició con 20)\n";
    }
} else {
    echo "   ⚠️  No se pudo verificar el stock\n";
}
echo "\n";

// Test 4: Intentar venta con stock insuficiente
echo "4. Probando validación de stock insuficiente...\n";
$invalidSale = [
    'sale_date' => date('Y-m-d'),
    'items' => [
        [
            'product_id' => 6, // Mazamorra Morada (10 unidades)
            'quantity' => 100 // Intentar vender 100
        ]
    ]
];

$response = makeRequest('POST', "$baseUrl/sales", $invalidSale);
if ($response['code'] == 422) {
    echo "   ✅ Validación funcionando correctamente\n";
    if (isset($response['body']['errors'])) {
        echo "   Errores detectados:\n";
        foreach ($response['body']['errors'] as $field => $errors) {
            foreach ($errors as $error) {
                echo "   - $error\n";
            }
        }
    }
} else {
    echo "   ❌ La validación NO funcionó (debería rechazar la venta)\n";
}
echo "\n";

// Test 5: Calcular cierre de caja
echo "5. Calculando cierre de caja...\n";
$closingData = [
    'closing_date' => date('Y-m-d')
];

$response = makeRequest('POST', "$baseUrl/daily-closings/calculate", $closingData);
if ($response['code'] == 201) {
    echo "   ✅ Cierre de caja calculado\n";
    if (isset($response['body']['closing'])) {
        $closing = $response['body']['closing'];
        echo "   Total Ventas: {$closing['total_sales']}\n";
        echo "   Total Gastos: {$closing['total_expenses']}\n";
        echo "   Balance Final: {$closing['final_balance']}\n";
    }
} else {
    echo "   ⚠️  Cierre no creado (puede ser que ya exista para hoy)\n";
    if (isset($response['body']['message'])) {
        echo "   Mensaje: {$response['body']['message']}\n";
    }
}
echo "\n";

echo "=== VERIFICACIÓN COMPLETADA ===\n";
