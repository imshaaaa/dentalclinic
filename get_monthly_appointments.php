<?php

require_once __DIR__ . '/Config/bootstrap.php';
require_login(['admin']);

header('Content-Type: application/json; charset=UTF-8');

echo json_encode(monthly_appointments_data(), JSON_UNESCAPED_UNICODE);
