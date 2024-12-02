<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Leer los datos enviados desde la solicitud
$request = json_decode(file_get_contents("php://input"), true);
$newPhone = $request['phone'] ?? null;

// Validar el número de teléfono
if (!$newPhone || !preg_match('/^\d{3} \d{3} \d{3}$/', $newPhone)) {
    echo json_encode(['success' => false, 'message' => 'Número de teléfono inválido.']);
    exit;
}

// Configuración para la API de WordPress
$wpApiUrl = 'http://www.wordpress.lan/wp-json/wp/v2/pages?slug=prese-contacto';
$wpAuthHeader = 'Authorization: Basic YWxiZXJ0bzpxWEJLIG5ncUsgdVdzMiB1N0JlIFVtZjkgSlFyRQo=';

// Obtener la página de WordPress utilizando el slug
$ch = curl_init($wpApiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    $wpAuthHeader
]);
$pageData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo json_encode(['success' => false, 'message' => 'No se pudo obtener la página de WordPress.']);
    exit;
}

$pageData = json_decode($pageData, true);
if (empty($pageData)) {
    echo json_encode(['success' => false, 'message' => 'La página no fue encontrada en WordPress.']);
    exit;
}

$page = $pageData[0]; // Primera página coincidente
$pageId = $page['id']; // Usamos el ID obtenido para la actualización
$currentContent = $page['content']['rendered'];

// Reemplazar el número de teléfono en el contenido
$updatedContent = preg_replace('/\d{3} \d{3} \d{3}/', $newPhone, $currentContent);

// Preparar los datos para la actualización
$updateData = [
    'content' => $updatedContent
];

// Actualizar la página en WordPress
$ch = curl_init("http://www.wordpress.lan/wp-json/wp/v2/pages/$pageId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updateData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    $wpAuthHeader
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo json_encode(['success' => true, 'message' => 'Número de teléfono actualizado correctamente.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar el número de teléfono en WordPress.']);
}
