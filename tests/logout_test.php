<?php

declare(strict_types=1);

// Executar: php tests/logout_test.php. Nao usa contas nem dados do banco.
if (!in_array(PHP_SAPI, ['cli', 'cli-server'], true)) {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../includes/auth.php';

// Roteador exclusivo do servidor temporario criado pelo teste abaixo.
if (PHP_SAPI === 'cli-server') {
    $base = parse_url((string) BASE_URL);
    $_SERVER['HTTP_HOST'] = $base['host'] . (isset($base['port']) ? ':' . $base['port'] : '');
    $_SERVER['SERVER_PORT'] = $base['port'] ?? ($base['scheme'] === 'https' ? 443 : 80);
    $_SERVER['HTTPS'] = $base['scheme'] === 'https' ? 'on' : 'off';
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $basePath = rtrim($base['path'] ?? '', '/');

    if ($path === $basePath . '/actions/logout.php') {
        require __DIR__ . '/../actions/logout.php';
        exit;
    }

    sessionStart();

    if ($path === $basePath . '/__test/seed' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $_SESSION = [
            'usuario_id' => 999999,
            'usuario_nome' => 'Usuario de teste isolado',
            'eh_admin' => ($_POST['role'] ?? '') === 'admin' ? 1 : 0,
            'usuario' => ['id' => 999999],
        ];
        header('Content-Type: application/json');
        echo json_encode(['csrf_token' => csrfToken()]);
        exit;
    }

    if ($path === $basePath . '/__test/state') {
        header('Content-Type: application/json');
        echo json_encode([
            'logged_in' => isLoggedIn(),
            'admin' => isAdmin(),
            'session_empty' => $_SESSION === [],
        ]);
        exit;
    }

    if ($path === $basePath . '/__test/button') {
        $_SERVER['PHP_SELF'] = (string) ($_GET['page'] ?? '/index.php');
        require __DIR__ . '/../includes/logout_btn.php';
        exit;
    }

    http_response_code(404);
    exit;
}

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function request(string $url, string $method = 'GET', string $cookie = '', array $data = []): array
{
    $headers = ['Content-Type: application/x-www-form-urlencoded'];
    if ($cookie !== '') {
        $headers[] = 'Cookie: ' . $cookie;
    }
    $context = stream_context_create(['http' => [
        'method' => $method,
        'header' => implode("\r\n", $headers),
        'content' => http_build_query($data),
        'ignore_errors' => true,
        'follow_location' => 0,
        'timeout' => 5,
    ]]);
    $body = file_get_contents($url, false, $context);
    check($body !== false, 'Falha na requisicao de teste.');
    preg_match('/\s(\d{3})\s/', $http_response_header[0], $status);
    $result = ['status' => (int) ($status[1] ?? 0), 'body' => $body, 'headers' => []];
    foreach (array_slice($http_response_header, 1) as $header) {
        if (str_contains($header, ':')) {
            [$name, $value] = explode(':', $header, 2);
            $result['headers'][strtolower($name)][] = trim($value);
        }
    }
    return $result;
}

$temporaryDirectory = sys_get_temp_dir() . '/salgadaria-logout-' . bin2hex(random_bytes(8));
check(mkdir($temporaryDirectory, 0700), 'Nao foi possivel criar a pasta temporaria.');
$process = null;
$failure = null;

try {
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
    check($socket !== false, 'Nao foi possivel reservar uma porta local.');
    $address = stream_socket_get_name($socket, false);
    fclose($socket);
    $port = (int) substr(strrchr($address, ':'), 1);
    $process = proc_open([
        PHP_BINARY, '-d', 'session.save_handler=files', '-d', 'session.save_path=' . $temporaryDirectory,
        '-S', '127.0.0.1:' . $port, '-t', $temporaryDirectory, __FILE__,
    ], [
        0 => ['pipe', 'r'],
        1 => ['file', $temporaryDirectory . '/server.log', 'a'],
        2 => ['file', $temporaryDirectory . '/server.log', 'a'],
    ], $pipes, __DIR__);
    check(is_resource($process), 'Nao foi possivel iniciar o servidor de teste.');
    fclose($pipes[0]);

    $ready = false;
    for ($attempt = 0; $attempt < 50; $attempt++) {
        $probe = @fsockopen('127.0.0.1', $port, $errorCode, $errorMessage, 0.1);
        if ($probe !== false) {
            fclose($probe);
            $ready = true;
            break;
        }
        usleep(100000);
    }
    check($ready, 'O servidor de teste nao iniciou.');

    $basePath = rtrim(parse_url((string) BASE_URL, PHP_URL_PATH) ?: '', '/');
    $testBase = 'http://127.0.0.1:' . $port . $basePath;

    foreach (['cliente', 'admin'] as $role) {
        $seed = request($testBase . '/__test/seed', 'POST', '', ['role' => $role]);
        check($seed['status'] === 200, 'Falha ao preparar sessao de teste.');
        $cookie = explode(';', $seed['headers']['set-cookie'][0])[0];
        $token = json_decode($seed['body'], true, 512, JSON_THROW_ON_ERROR)['csrf_token'];

        $state = json_decode(request($testBase . '/__test/state', 'GET', $cookie)['body'], true);
        check($state['logged_in'] && $state['admin'] === ($role === 'admin'), 'Sessao de teste incorreta.');

        $get = request($testBase . '/actions/logout.php', 'GET', $cookie);
        check($get['status'] === 405 && ($get['headers']['allow'][0] ?? '') === 'POST', 'GET deve ser rejeitado.');

        foreach ([[], ['csrf_token' => 'invalido']] as $invalidData) {
            $invalid = request($testBase . '/actions/logout.php', 'POST', $cookie, $invalidData);
            check($invalid['status'] === 403, 'CSRF ausente ou invalido deve ser rejeitado.');
            $state = json_decode(request($testBase . '/__test/state', 'GET', $cookie)['body'], true);
            check($state['logged_in'], 'Requisicao rejeitada nao pode encerrar a sessao.');
        }

        $logout = request($testBase . '/actions/logout.php', 'POST', $cookie, ['csrf_token' => $token]);
        check($logout['status'] === 303, 'Logout valido deve redirecionar com 303.');
        check(($logout['headers']['location'][0] ?? '') === appUrl('index.php'), 'Destino do logout incorreto.');
        $expiredCookie = implode('; ', $logout['headers']['set-cookie'] ?? []);
        check(str_contains(strtolower($expiredCookie), 'max-age=0'), 'O cookie deve ser expirado.');
        check(str_contains($expiredCookie, 'path=' . sessionCookiePath()), 'O caminho do cookie deve ser preservado.');
        check(str_contains(strtolower($expiredCookie), 'httponly'), 'O cookie deve preservar HttpOnly.');
        check(str_contains($expiredCookie, 'SameSite=Lax'), 'O cookie deve preservar SameSite.');
        check(!str_contains($logout['body'], 'Pacote'), 'Logout nao pode executar logica de pacotes.');

        // Reenvia deliberadamente o cookie antigo: nao pode recuperar a conta.
        $state = json_decode(request($testBase . '/__test/state', 'GET', $cookie)['body'], true);
        check(!$state['logged_in'] && !$state['admin'] && $state['session_empty'], 'A sessao antiga ainda tem dados.');
        echo "OK: $role - saida, redirecionamento, cookie, sessao antiga, GET e CSRF.\n";
    }

    foreach (['/index.php', $basePath . '/index.php', $basePath . '/admin/dashboard.php'] as $page) {
        $button = request($testBase . '/__test/button?page=' . rawurlencode($page));
        check($button['status'] === 200, 'Falha ao renderizar o botao de saida.');
        check(str_contains($button['body'], 'action="' . htmlspecialchars(appUrl('actions/logout.php'), ENT_QUOTES, 'UTF-8') . '"'), 'Endereco do botao incorreto.');
        check(str_contains($button['body'], 'method="POST"') && str_contains($button['body'], 'name="csrf_token"'), 'Botao deve enviar POST com CSRF.');
    }
    echo "OK: botao reutilizavel na raiz e em subpastas.\n";
} catch (Throwable $exception) {
    $failure = $exception;
} finally {
    if (is_resource($process)) {
        proc_terminate($process);
        proc_close($process);
    }
    // Remove somente os arquivos gerados nesta pasta exclusiva de teste.
    foreach (glob($temporaryDirectory . '/*') ?: [] as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    rmdir($temporaryDirectory);
}

if ($failure !== null) {
    fwrite(STDERR, 'FALHOU: ' . $failure->getMessage() . "\n");
    exit(1);
}
