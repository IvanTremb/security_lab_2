<?php
require_once 'config.php';

app_log('INFO', 'Выход из системы');
logout_user();

header('Location: login.php');
exit;
