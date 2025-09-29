<?php
header('Content-Type: application/json');

$config_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php';

echo json_encode([
    'current_dir' => __DIR__,
    'config_path' => $config_path,
    'config_exists' => file_exists($config_path),
    'status' => 'test'
]);
?>






