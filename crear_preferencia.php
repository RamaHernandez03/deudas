<?php
// 1. Reemplazá este token con el tuyo real (lo encontrás en "Credenciales")
$access_token = "APP_USR-2565689734868500-042919-5082275e46f0854ccba08b1e873248b0-2412223195";

// 2. Creamos la preferencia
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api.mercadopago.com/checkout/preferences',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => json_encode([
    'items' => [[
      'title' => 'Consulta Deuda BCRA',
      'quantity' => 1,
      'currency_id' => 'ARS',
      'unit_price' => 10.00
    ]],
    'back_urls' => [
      'success' => 'deudores-testing.netlify.app/consulta.html',
      'failure' => 'deudores-testing.netlify.app/',
      'pending' => 'deudores-testing.netlify.app/pending.html'
    ],
    'auto_return' => 'approved'
  ]),
  CURLOPT_HTTPHEADER => [
    "Authorization: Bearer $access_token",
    "Content-Type: application/json"
  ],
));

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

// 3. Validamos la respuesta
$data = json_decode($response, true);

if ($http_code != 201 || !isset($data['init_point'])) {
  // Mostrar mensaje de error más detallado
  echo "<h2>Error al generar preferencia de pago</h2>";
  echo "<pre>HTTP $http_code\n";
  print_r($data);
  echo "</pre>";
  exit;
}

// 4. Redireccionar al Checkout
header('Location: ' . $data['init_point']);
exit;
