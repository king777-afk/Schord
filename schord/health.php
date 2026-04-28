<?php
header('Content-Type: application/json; charset=utf-8');

$response = [
    'status' => 'ok',
    'service' => 'schord',
    'time' => gmdate('c')
];

echo json_encode($response);
