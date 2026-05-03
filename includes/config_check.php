<?php

declare(strict_types=1);

function appConfigFail(string $message): void
{
    http_response_code(500);
    error_log('[config] ' . $message);
    exit('Erro de configuracao: verifique o arquivo config/config.php.');
}

function appConfigLoad(): void
{
    static $loaded = false;

    if ($loaded) {
        return;
    }

    $configPath = __DIR__ . '/../config/config.php';

    if (!is_file($configPath) || !is_readable($configPath)) {
        appConfigFail('Arquivo config/config.php ausente ou sem permissao de leitura.');
    }

    require_once $configPath;

    $required = ['DB_HOST', 'DB_USER', 'DB_PASS', 'DB_NAME', 'BASE_URL'];
    $missing = [];

    foreach ($required as $constant) {
        if (!defined($constant)) {
            $missing[] = $constant;
        }
    }

    if ($missing) {
        appConfigFail('Constantes obrigatorias ausentes: ' . implode(', ', $missing) . '.');
    }

    foreach (['DB_HOST', 'DB_USER', 'DB_NAME', 'BASE_URL'] as $constant) {
        if (trim((string) constant($constant)) === '') {
            appConfigFail('Constante obrigatoria vazia: ' . $constant . '.');
        }
    }

    $baseUrl = (string) BASE_URL;
    $baseParts = parse_url($baseUrl);

    if (
        !is_array($baseParts)
        || empty($baseParts['scheme'])
        || empty($baseParts['host'])
        || !in_array(strtolower($baseParts['scheme']), ['http', 'https'], true)
    ) {
        appConfigFail('BASE_URL invalida. Use uma URL completa com http:// ou https://.');
    }

    $loaded = true;
}
