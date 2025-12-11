<?php
// api/chat.php - FINAL WORKING VERSION
ob_start();

$root = dirname(__DIR__);
$envFile = $root . '/.env';

if (!file_exists($envFile)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => '.env file missing!']);
    exit;
}

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
    echo json_encode(['error' => 'GEMINI_API_KEY not set in .env']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Only POST allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$prompt = trim($input['prompt'] ?? '');

if ($prompt === '') {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Empty message']);
    exit;
}

// Load KSA Knowledge Base from existing knowledge base file
$kbFile = $root . '/assets/ksa_knowledge_base.json';
$ksaDataArray = [];
if (file_exists($kbFile)) {
    $kbContent = json_decode(file_get_contents($kbFile), true);
    if (is_array($kbContent)) {
        $ksaDataArray = $kbContent;
    }
}

// Build context from knowledge base
$contextParts = [];
$contextParts[] = "Organization: " . ($ksaDataArray['ksa_organization']['name'] ?? 'Kenya Scouts Association');
$contextParts[] = "Founded: " . ($ksaDataArray['ksa_organization']['founded'] ?? '1910');
$contextParts[] = "Counties: " . ($ksaDataArray['ksa_organization']['reach']['counties'] ?? '47');
$contextParts[] = "Contact: Phone " . (isset($ksaDataArray['ksa_organization']['contact']['phone']) ? $ksaDataArray['ksa_organization']['contact']['phone'][0] : '020 2020819');
$contextParts[] = "Shop: KSA Scouts Shop - Lipa Mdogo Mdogo (Pay via M-Pesa - installments available)";
$contextParts[] = "Scout Sections: Sungura (6-10), Chipukizi (10-14), Mwamba (14-18), Jasiri (18+)";
$contextParts[] = "Shop Items: Uniforms (3600-4000 KES), Badges, Training Materials, Camping Gear";
$contextParts[] = "Delivery: Free pickup at Rowallan Camp or nationwide via G4S (300-600 KES)";
$contextParts[] = "Mission: Develop character and leadership through the Scout method";

$ksaData = json_encode([
    "Organization" => "Kenya Scouts Association, founded 1910. Operating in 47 counties, serving 500,000+ youth.",
    "Mission" => "Develop character and provide young people with skills to be better people and better citizens",
    "Vision" => "Building well-rounded citizens through scouting",
    "Contact" => "Phone: 020 2020819 | Email: info@kenyascouts.org | Address: Rowallan Camp, Nairobi",
    "Motto" => "Be Prepared • Kuwa Tayari",
    "Scout_Sections" => "Sungura (6-10yr), Chipukizi (10-14yr), Mwamba (14-18yr), Jasiri (18+yr)",
    "Shop" => "Kenya Scouts Shop - Lipa Mdogo Mdogo offers official scout uniforms and merchandise. Payment via M-Pesa (installments available).",
    "Shop_Hours" => "Monday-Friday 9AM-5PM, Saturday 9AM-1PM at multiple locations nationwide",
    "Uniforms_Available" => "Complete uniforms from 3600-4000 KES. Badges from 300 KES. Individual items available.",
    "Quality_Guarantee" => "30-day quality guarantee on all items",
    "Delivery" => "Free pickup at Rowallan Camp or nationwide delivery via G4S (300-600 KES). Processing within 24 hours.",
    "Scout_Motto" => "Scout's Honour - Service to God, Country, and Others",
    "Founder" => "Lord Baden-Powell (1857-1941), established modern scouting in 1907",
    "Website" => "https://kenyascouts.org"
], JSON_PRETTY_PRINT);

$payload = [
    'contents' => [[
        'role' => 'user',
        'parts' => [[
            'text' => "You are Kenya Scouts AI Assistant. Use ONLY this official KSA data:\n\n$ksaData\n\nRELEVANT CONTEXT:\n$relevantContext\n\nUser Question: $prompt\n\nProvide accurate, helpful responses. Be friendly and professional. If the question is about ordering, direct to shop.php. If about tracking orders, direct to check.php. If unsure, suggest visiting https://kenyascouts.org."
        ]]
    ]],
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 800
    ]
];

$model = 'gemini-2.5-flash';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://generativelanguage.googleapis.com/v1beta/models/$model:generateContent?key=$key",
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30
]);

 $response = curl_exec($ch);
 $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
 $curlErr = curl_error($ch);
 curl_close($ch);

 header('Content-Type: application/json');
 if ($response === false) {
     http_response_code(502);
     echo json_encode(['error' => 'Curl error when calling Gemini API', 'detail' => $curlErr]);
     ob_end_flush();
     exit;
 }

 // Try to decode Gemini response to surface helpful error information
 $decoded = json_decode($response, true);
 if ($httpCode >= 200 && $httpCode < 300) {
     echo $response;
 } else {
     http_response_code($httpCode ?: 502);
     // If the API returned structured JSON, include it in the response (sanitized)
     if (is_array($decoded)) {
         echo json_encode(['error' => 'Gemini API error', 'http_code' => $httpCode, 'response' => $decoded]);
     } else {
         echo json_encode(['error' => 'Gemini API returned non-JSON response', 'http_code' => $httpCode, 'body_preview' => substr($response, 0, 800)]);
     }
 }
 ob_end_flush();
 exit;
ob_end_flush();
exit;
?>