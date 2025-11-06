<?php
// api/chat.php – Render.com version (no .env file needed)

ob_start();

// Get API key from Render environment
$key = getenv('GEMINI_API_KEY');
if (!$key) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'GEMINI_API_KEY not configured']);
    exit;
}

// Rate limiting (same as before)
$limitsFile = __DIR__ . '/limits.json';
$ip = $_SERVER['REMOTE_ADDR'];
$now = time();
$oneMinAgo = $now - 60;

// Load or init limits
$limits = file_exists($limitsFile) ? json_decode(file_get_contents($limitsFile), true) : [];
$ipLimits = $limits[$ip] ?? ['requests' => [], 'tokens' => 0, 'lastReset' => $now];

// Clean old requests
$ipLimits['requests'] = array_filter($ipLimits['requests'], fn($t) => $t > $oneMinAgo);

// Reset daily
if ($now - $ipLimits['lastReset'] > 86400) {
    $ipLimits['requests'] = [];
    $ipLimits['tokens'] = 0;
    $ipLimits['lastReset'] = $now;
}

// Enforce limits
if (count($ipLimits['requests']) >= 5) {
    http_response_code(429);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Too many requests. Wait 1 minute.']);
    exit;
}

// Add request
$ipLimits['requests'][] = $now;
$ipLimits['tokens'] += 500;
$limits[$ip] = $ipLimits;
file_put_contents($limitsFile, json_encode($limits));

// Validate POST
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
    'contents' => [['role' => 'user', 'parts' => [['text' => "Kenya Scouts AI: $prompt"]]]],
    'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 800]
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=$key",
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

header('Content-Type: application/json');
http_response_code($httpCode);
echo $response;
exit;
?>
