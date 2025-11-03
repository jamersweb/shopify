<?php
// Minimal working server for testing
header('Content-Type: application/json');

$response = [
    'status' => 'success',
    'message' => 'EcoFreight Shopify App is running!',
    'timestamp' => date('Y-m-d H:i:s'),
    'version' => 'Laravel 10.49.1',
    'php_version' => phpversion(),
    'server' => 'PHP Built-in Server'
];

echo json_encode($response, JSON_PRETTY_PRINT);
