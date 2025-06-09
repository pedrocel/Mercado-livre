<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar se é uma requisição GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

// Verificar se o CPF foi fornecido
if (!isset($_GET['cpf']) || empty($_GET['cpf'])) {
    http_response_code(400);
    echo json_encode(['error' => 'CPF é obrigatório']);
    exit;
}

$cpf = $_GET['cpf'];

// Validar CPF (apenas números)
$cpf = preg_replace('/\D/', '', $cpf);
if (strlen($cpf) !== 11) {
    http_response_code(400);
    echo json_encode(['error' => 'CPF deve ter 11 dígitos']);
    exit;
}

// URL da nova API
$apiUrl = 'https://api.zapmensagem.com/api/info/cpf=' . urlencode($cpf);

// Log para debug (remover em produção)
error_log('Fazendo requisição para: ' . $apiUrl);

// Inicializar cURL
$ch = curl_init();

// Configurar opções do cURL
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Apenas se necessário
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

// Headers para simular um navegador
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json, text/plain, */*',
    'Accept-Language: pt-BR,pt;q=0.9,en;q=0.8',
    'Cache-Control: no-cache',
    'Pragma: no-cache',
    'Referer: https://api.zapmensagem.com/',
    'Origin: https://api.zapmensagem.com'
]);

// Executar a requisição
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

// Log da resposta (remover em produção)
error_log('Resposta da API: ' . $response);
error_log('HTTP Code: ' . $httpCode);

// Verificar erros do cURL
if ($error) {
    error_log('Erro cURL: ' . $error);
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao conectar com a API externa: ' . $error]);
    curl_close($ch);
    exit;
}

curl_close($ch);

// Verificar se a requisição foi bem-sucedida
if ($httpCode !== 200) {
    error_log('Erro da API externa - HTTP ' . $httpCode . ' - Resposta: ' . $response);
    http_response_code($httpCode);
    echo json_encode(['error' => 'Erro na API externa', 'status' => $httpCode, 'response' => $response]);
    exit;
}

// Verificar se a resposta não está vazia
if (empty($response)) {
    error_log('Resposta vazia da API');
    http_response_code(500);
    echo json_encode(['error' => 'Resposta vazia da API externa']);
    exit;
}

// Verificar se a resposta é um JSON válido
$decodedResponse = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log('Resposta não é um JSON válido: ' . $response);
    error_log('Erro JSON: ' . json_last_error_msg());
    
    // Se não for JSON, retornar a resposta como texto
    echo json_encode([
        'error' => 'Resposta não é um JSON válido',
        'raw_response' => $response,
        'json_error' => json_last_error_msg()
    ]);
    exit;
}

// Log dos dados decodificados para debug
error_log('Dados decodificados: ' . print_r($decodedResponse, true));

// Retornar a resposta
echo $response;
?>
