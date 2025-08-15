<?php
/**
 * Simple test endpoint without GLPI authentication
 */

// Set content type
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido. Use POST.']);
    exit;
}

// Simple response
echo json_encode([
    'success' => true, 
    'message' => 'Endpoint funcionando!',
    'timestamp' => date('Y-m-d H:i:s'),
    'post_data' => $_POST
]);
?>