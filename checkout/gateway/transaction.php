<?php
header('Content-Type: application/json');

// Permitir CORS se necessário
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

// Obter o corpo da requisição
$requestBody = file_get_contents('php://input');
$requestData = json_decode($requestBody, true);

// Validar dados recebidos
if (!$requestData || !isset($requestData['amount']) || !isset($requestData['customer'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Dados inválidos']);
    exit;
}

// Configurar a requisição para a API BlackCat
$apiUrl = 'https://api.blackcatpagamentos.com/v1/transactions';
$apiKey = 'c2tfN3NTVVQzZFN3c1hwdTJjMjcyVGJlVXUwWXdRejk2d2R4dmZjZFFsOWk5ckZocUNkOnBrX3N0b1IxVFZMV2VtY3doMVFULVVlYVhLd1RrWlJzU3hpdkp3Ykt0S3FLbFV6M0lCZA==';

// Preparar os dados para a API
$paymentData = [
    'paymentMethod' => 'pix',
    'amount' => $requestData['amount'],
    'customer' => $requestData['customer'],
    'items' => $requestData['items'] ?? [],
];

// Adicionar metadata se existir
if (isset($requestData['metadata'])) {
    $paymentData['metadata'] = $requestData['metadata'];
}

// Inicializar cURL
$ch = curl_init($apiUrl);

// Configurar opções do cURL
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paymentData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Basic ' . $apiKey
]);

// Executar a requisição
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Verificar erros
if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao conectar com a API: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

// Retornar a resposta da API com o mesmo código HTTP
http_response_code($httpCode);
echo $response;
?>
