<?php
/**
 * Точка входа в приложение
 * Загружает конфигурацию (bootstrap.php), создаёт Slim App
 */
$app = require __DIR__ . '/../config/bootstrap.php';
$app->run();
