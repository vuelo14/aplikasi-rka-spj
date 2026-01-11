
<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/lib/Database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
Auth::logout();
header('Location: ' . APP_BASE_URL . 'login.php');
