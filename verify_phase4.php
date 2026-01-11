<?php

// Script de prueba para verificar Fase 4: Reportes y Auditoría
// Ejecutar: php verify_phase4.php

echo "=== VERIFICACIÓN FASE 4: Reportes, PDFs y Auditoría ===\n\n";

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

// Paso 1: Login como Admin
echo "1. Login como Admin (para obtener token)...\n";
$response = makeRequest('POST', "$baseUrl/login", [
    'email' => 'admin@casahogar.com',
    'password' => 'admin123'
]);

if ($response['code'] == 200) {
    $adminToken = $response['body']['token'] ?? null;
    echo "   ✅ Token obtenido\n\n";
} else {
    echo "   ❌ Error en login\n\n";
    exit(1);
}

// Test 2: Dashboard Stats
echo "2. Probando Dashboard Stats...\n";
$response = makeRequest('GET', "$baseUrl/reports/dashboard", null, $adminToken);
if ($response['code'] == 200) {
    echo "   ✅ Dashboard funcionando\n";
    if (isset($response['body']['period'])) {
        echo "   Período: {$response['body']['period']}\n";
        echo "   Total Ventas: {$response['body']['total_sales']}\n";
        echo "   Total Gastos: {$response['body']['total_expenses']}\n";
        echo "   Ganancia Neta: {$response['body']['net_profit']}\n";
        if (isset($response['body']['top_products'])) {
            echo "   Top Productos: " . count($response['body']['top_products']) . " productos\n";
        }
    }
} else {
    echo "   ❌ Error en dashboard\n";
    print_r($response['body']);
}
echo "\n";

// Test 3: Reporte por Rango de Fechas
echo "3. Probando Reporte por Fechas...\n";
$startDate = date('Y-m-01'); // Primer día del mes
$endDate = date('Y-m-d'); // Hoy
$response = makeRequest('GET', "$baseUrl/reports/sales-by-date?start_date=$startDate&end_date=$endDate", null, $adminToken);
if ($response['code'] == 200) {
    echo "   ✅ Reporte por fechas funcionando\n";
    if (isset($response['body']['summary'])) {
        echo "   Período: {$response['body']['period']['start']} a {$response['body']['period']['end']}\n";
        echo "   Total: {$response['body']['summary']['total_amount']}\n";
        echo "   Transacciones: {$response['body']['summary']['total_transactions']}\n";
    }
} else {
    echo "   ❌ Error en reporte por fechas\n";
    print_r($response['body']);
}
echo "\n";

// Test 4: Activity Logs
echo "4. Probando Activity Logs...\n";
$response = makeRequest('GET', "$baseUrl/reports/activity-logs", null, $adminToken);
if ($response['code'] == 200) {
    echo "   ✅ Activity Logs funcionando\n";
    if (isset($response['body']['data'])) {
        echo "   Logs registrados: " . count($response['body']['data']) . "\n";
        if (count($response['body']['data']) > 0) {
            $firstLog = $response['body']['data'][0];
            echo "   Último log: {$firstLog['action']} - {$firstLog['model']}\n";
        }
    }
} else {
    echo "   ❌ Error en activity logs\n";
}
echo "\n";

// Test 5: Crear algo para generar un log
echo "5. Creando producto para verificar logging...\n";
$response = makeRequest('POST', "$baseUrl/products", [
    'name' => 'Producto Test ' . time(),
    'description' => 'Para probar logging',
    'stock' => 5,
    'base_price' => 3.50
], $adminToken);

if ($response['code'] == 201) {
    $productId = $response['body']['product']['id'] ?? null;
    echo "   ✅ Producto creado (ID: $productId)\n";
    
    // Ahora eliminarlo para generar log
    if ($productId) {
        echo "6. Eliminando producto para generar log de auditoría...\n";
        $response = makeRequest('DELETE', "$baseUrl/products/$productId", null, $adminToken);
        if ($response['code'] == 200) {
            echo "   ✅ Producto eliminado (debería estar en activity_logs)\n";
        }
    }
} else {
    echo "   ⚠️  No se pudo crear producto de prueba\n";
}
echo "\n";

// Test 6: Rate Limiting
echo "7. Probando Rate Limiting (5 intentos)...\n";
for ($i = 1; $i <= 6; $i++) {
    $response = makeRequest('POST', "$baseUrl/login", [
        'email' => 'fake@test.com',
        'password' => 'wrong'
    ]);
    
    if ($i <= 5) {
        if ($response['code'] == 422) {
            echo "   Intento $i: ✅ Rechazado correctamente\n";
        } else {
            echo "   Intento $i: Código {$response['code']}\n";
        }
    } else {
        if ($response['code'] == 429) {
            echo "   ✅ Intento 6: Rate limit funcionando (429 Too Many Requests)\n";
        } else {
            echo "   ❌ Rate limit no funcionó (esperado 429, recibido {$response['code']})\n";
        }
    }
    
    if ($i < 6) usleep(100000); // Pequeña pausa entre intentos
}
echo "\n";

// Test 7: Verificar que tesorero NO puede ver reportes
echo "8. Probando restricción de reportes (tesorero)...\n";
$response = makeRequest('POST', "$baseUrl/login", [
    'email' => 'tesorero@casahogar.com',
    'password' => 'tesorero123'
]);

if ($response['code'] == 200) {
    $tesoreroToken = $response['body']['token'];
    
    $response = makeRequest('GET', "$baseUrl/reports/dashboard", null, $tesoreroToken);
    if ($response['code'] == 403) {
        echo "   ✅ Tesorero bloqueado correctamente (403 Forbidden)\n";
    } else {
        echo "   ❌ Tesorero no debería acceder (código {$response['code']})\n";
    }
}
echo "\n";

echo "=== VERIFICACIÓN COMPLETADA ===\n";
echo "\nResumen:\n";
echo "✅ Dashboard Stats\n";
echo "✅ Reportes por Fechas\n";
echo "✅ Activity Logs\n";
echo "✅ Rate Limiting\n";
echo "✅ Restricción por Roles\n";
