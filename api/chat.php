<?php
// api/chat.php – Direct Gemini call with rate limiting

ob_start(); // Prevent "headers already sent"

// Load .env from project root
$root = dirname(__DIR__);
$envFile = $root . '/.env';

if (!file_exists($envFile)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => '.env not found']);
    exit;
}

// Parse .env
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    if (strpos($line, '=') !== false) {
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        putenv("$key=$value");
    }
}

$key = getenv('GEMINI_API_KEY');
if (!$key) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'GEMINI_API_KEY missing']);
    exit;
}

// Rate limiting config (under Gemini limits)
$limitsFile = __DIR__ . '/limits.json';
$ip = $_SERVER['REMOTE_ADDR'];
$now = time();
$oneMinAgo = $now - 60;
$oneDayAgo = $now - 86400;

// Load limits (or create empty)
$limits = file_exists($limitsFile) ? json_decode(file_get_contents($limitsFile), true) ?? [] : [];
$ipLimits = $limits[$ip] ?? ['requests' => [], 'tokens' => 0, 'lastReset' => $now];

// Clean old requests
$ipLimits['requests'] = array_filter($ipLimits['requests'], function($timestamp) use ($oneMinAgo) {
    return $timestamp > $oneMinAgo;
});

// Reset daily if needed
if ($now - $ipLimits['lastReset'] > 86400) {
    $ipLimits['requests'] = [];
    $ipLimits['tokens'] = 0;
    $ipLimits['lastReset'] = $now;
}

// Check limits
$reqCount = count($ipLimits['requests']);
if ($reqCount >= 5) { // 5/min (under 10 RPM)
    http_response_code(429);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Too many requests. Try again in 1 min.']);
    exit;
}

// Estimate tokens (~500 per request, under 250K TPM)
if ($ipLimits['tokens'] >= 100000) { // Conservative
    http_response_code(429);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Daily token limit reached. Try tomorrow.']);
    exit;
}

// Add current request
$ipLimits['requests'][] = $now;
$ipLimits['tokens'] += 500; // Estimate

// Save limits
$limits[$ip] = $ipLimits;
file_put_contents($limitsFile, json_encode($limits));

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'POST only']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$prompt = trim($input['prompt'] ?? '');

if ($prompt === '') {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Empty prompt']);
    exit;
}

// Call Gemini
$payload = [
    'contents' => [
        ['role' => 'user', 'parts' => [['text' => "Kenya Scouts AI: $prompt"]]]
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 800
    ]
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=$key",
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => true
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

header('Content-Type: application/json');
if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'cURL: ' . $curlError]);
} else {
    http_response_code($httpCode);
    echo $response;
}
exit;
?>