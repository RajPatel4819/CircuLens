<?php
require_once __DIR__ . '/../config/auth.php';
startSession();
session_unset();
session_destroy();
header('Location: ' . APP_URL . '/user/login.php');
exit;
