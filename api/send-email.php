<?php
// CORS using ALLOW_ORIGIN from SetEnv
$allowed = array_map('trim', explode(',', getenv('ALLOW_ORIGIN') ?: 'https://www.infinityinvestmentpropertysolutions.com,https://infinityinvestmentpropertysolutions.com'));
$origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
header('Vary: Origin');
if ($origin && in_array($origin, $allowed, true)) {
  header("Access-Control-Allow-Origin: $origin");
} else {
  header("Access-Control-Allow-Origin: " . $allowed[0]);
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method Not Allowed'; exit; }

$input   = json_decode(file_get_contents('php://input'), true) ?: [];
$subject = $input['subject'] ?? null;
$html    = $input['html'] ?? null;
$replyTo = $input['reply_to'] ?? null;
if (!$subject || !$html) { http_response_code(400); echo 'Missing required fields.'; exit; }

$validReply = $replyTo && preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $replyTo);

$payload = [
  'from'    => 'Infinity Investment Property Solutions <noreply@infinityinvestmentpropertysolutions.com>',
  'to'      => ['soccerghana12342@gmail.com'], // <- change this
  'subject' => $subject,
  'html'    => $html
];
if ($validReply) {
  $payload['reply_to'] = $replyTo;
  $payload['headers']  = ['Reply-To' => $replyTo];
}

$ch = curl_init('https://api.resend.com/emails');
curl_setopt_array($ch, [
  CURLOPT_POST           => true,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER     => [
    'Authorization: Bearer ' . getenv('RESEND_API_KEY'),
    'Content-Type: application/json'
  ],
  CURLOPT_POSTFIELDS     => json_encode($payload),
  CURLOPT_TIMEOUT        => 15
]);
$response = curl_exec($ch);
$code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code($code ?: 500);
header('Content-Type: application/json');
echo $response ?: json_encode(['error' => 'No response from Resend']);
