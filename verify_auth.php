<?php

// Script de prueba para verificar autenticación Fase 3
// Ejecutar: php verify_auth.php

echo "=== VERIFICACIÓN FASE 3: Autenticación y Roles ===\n\n";

$baseUrl = 'http://127.0.0.1:8000/api';

function makeRequest($method, $url, $data = null, $token = null) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

// Test 1: Login como Admin
echo "1. Login como Admin...\n";
$response = makeRequest('POST', "$baseUrl/login", [
    'email' => 'admin@casahogar.com',
    'password' => 'admin123'
]);

if ($response['code'] == 200) {
    echo "   ✅ Login exitoso\n";
    $adminToken = $response['body']['token'] ?? null;
    echo "   Token generado: " . substr($adminToken, 0, 30) . "...\n";
    echo "   Rol: {$response['body']['user']['role']}\n";
} else {
    echo "   ❌ Login fallido\n";
    print_r($response);
}
echo "\n";

// Test 2: Login como Tesorero
echo "2. Login como Tesorero...\n";
$response = makeRequest('POST', "$baseUrl/login", [
    'email' => 'tesorero@casahogar.com',
    'password' => 'tesorero123'
]);

if ($response['code'] == 200) {
    echo "   ✅ Login exitoso\n";
    $tesoreroToken = $response['body']['token'] ?? null;
    echo "   Token generado: " . substr($tesoreroToken, 0, 30) . "...\n";
    echo "   Rol: {$response['body']['user']['role']}\n";
} else {
    echo "   ❌ Login fallido\n";
}
echo "\n";

// Test 3: Intentar acceder sin token
echo "3. Intentar acceder a /products sin token...\n";
$response = makeRequest('GET', "$baseUrl/products");
if ($response['code'] == 401) {
    echo "   ✅ Rechazado correctamente (401 Unauthorized)\n";
} else {
    echo "   ❌ Debería rechazar sin token\n";
}
echo "\n";

// Test 4: Acceder con token de admin
if (isset($adminToken)) {
    echo "4. Acceder a /products con token de admin...\n";
    $response = makeRequest('GET', "$baseUrl/products", null, $adminToken);
    if ($response['code'] == 200) {
        echo "   ✅ Acceso permitido\n";
        if (isset($response['body']['data'])) {
            echo "   Productos: " . count($response['body']['data']) . "\n";
        }
    } else {
        echo "   ❌ Error al acceder\n";
    }
    echo "\n";
}

// Test 5: Crear producto como admin (debe funcionar)
if (isset($adminToken)) {
    echo "5. Crear producto como admin...\n";
    $response = makeRequest('POST', "$baseUrl/products", [
        'name' => 'Producto de Prueba',
        'description' => 'Creado en verificación',
        'stock' => 10,
        'base_price' => 5.50
    ], $adminToken);
    
    if ($response['code'] == 201) {
        echo "   ✅ Producto creado exitosamente\n";
    } else {
        echo "   ❌ Error al crear producto\n";
        print_r($response['body']);
    }
    echo "\n";
}

// Test 6: Intentar crear producto como tesorero (debe fallar)
if (isset($tesoreroToken)) {
    echo "6. Intentar crear producto como tesorero (debe fallar)...\n";
    $response = makeRequest('POST', "$baseUrl/products", [
        'name' => 'Producto No Permitido',
        'stock' => 5,
        'base_price' => 3.00
    ], $tesoreroToken);
    
    if ($response['code'] == 403) {
        echo "   ✅ Rechazado correctamente (403 Forbidden)\n";
        echo "   Mensaje: {$response['body']['message']}\n";
    } else {
        echo "   ❌ Debería rechazar (403)\n";
    }
    echo "\n";
}

// Test 7: Crear venta como tesorero (debe funcionar)
if (isset($tesoreroToken)) {
    echo "7. Crear venta como tesorero...\n";
    $response = makeRequest('POST', "$baseUrl/sales", [
        'sale_date' => date('Y-m-d'),
        'items' => [
            ['product_id' => 1, 'quantity' => 1]
        ]
    ], $tesoreroToken);
    
    if ($response['code'] == 201) {
        echo "   ✅ Venta creada exitosamente\n";
    } else {
        echo "   ⚠️  Error al crear venta\n";
        if  (isset($response['body']['message'])) {
            echo "   Mensaje: {$response['body']['message']}\n";
        }
    }
    echo "\n";
}

echo "=== VERIFICACIÓN COMPLETADA ===\n";
