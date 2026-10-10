<?php
/**
 * contact.php — HANU PAINTS AND DECOR contact form handler
 *
 * Hosted on Hostinger (PHP mail() is available on all shared hosting plans).
 * Receives a POST request from the website contact form, validates the input,
 * and sends a formatted HTML email to the business inbox.
 *
 * Expected POST fields:
 *   name     (string, required)
 *   phone    (string, required)
 *   email    (string, optional)
 *   interest (string, required)
 *   message  (string, required)
 *   consent  (string, required — must equal "on")
 */

// ─── 1. CORS / Request Guards ────────────────────────────────────────────────

header('Content-Type: application/json; charset=UTF-8');

// Only accept POST requests.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// ─── 2. Configuration ────────────────────────────────────────────────────────

/**
 * The email address that will RECEIVE the enquiry.
 */
define('RECIPIENT_EMAIL', 'hanupaintsanddecor@gmail.com');

/**
 * The "From" address shown in the email client.
 * On Hostinger shared hosting this MUST be a mailbox that belongs
 * to your hosting domain (e.g. noreply@yourdomain.com).
 * Using Gmail here will cause rejection or spam delivery.
 *
 * UPDATE THIS to a real mailbox on your Hostinger domain before going live.
 */
define('SENDER_EMAIL', 'noreply@yourdomain.com');   // <-- change this
define('SENDER_NAME',  'HANU PAINTS AND DECOR Website');

/**
 * Honeypot field: hidden in the HTML, left blank by real users.
 * Bots that fill every field will be silently discarded.
 */
define('HONEYPOT_FIELD', 'website_url');

// ─── 3. Honeypot Check ───────────────────────────────────────────────────────

if (!empty($_POST[HONEYPOT_FIELD])) {
    // Pretend success so bots move on.
    echo json_encode(['success' => true, 'message' => 'Thank you for your enquiry!']);
    exit;
}

// ─── 4. Sanitise & Validate ──────────────────────────────────────────────────

$errors = [];

$name     = trim(strip_tags($_POST['name']     ?? ''));
$phone    = trim(strip_tags($_POST['phone']    ?? ''));
$email    = trim(strip_tags($_POST['email']    ?? ''));
$interest = trim(strip_tags($_POST['interest'] ?? ''));
$message  = trim(strip_tags($_POST['message']  ?? ''));
$consent  = trim($_POST['consent'] ?? '');

// Allowed interest options — must match the HTML <select> values exactly.
$allowed_interests = [
    'Interior paints',
    'Exterior paints',
    'Texture paints',
    'Wood coatings',
    'Metal paints',
    'Primers and putty',
    'Painting services',
    'Colour consultation',
    'Other enquiry',
];

if (strlen($name) < 2) {
    $errors[] = 'Please enter your full name (at least 2 characters).';
}

if (!preg_match('/^[0-9+() \-]{8,18}$/', $phone)) {
    $errors[] = 'Please enter a valid phone number.';
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'The email address you entered does not look valid.';
}

if (!in_array($interest, $allowed_interests, true)) {
    $errors[] = 'Please select a valid interest from the list.';
}

if (strlen($message) < 5) {
    $errors[] = 'Your message is too short — please tell us a bit more.';
}

if ($consent !== 'on') {
    $errors[] = 'Please confirm that you agree to be contacted.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => implode(' ', $errors),
    ]);
    exit;
}

// ─── 5. Escape Values for Safe HTML Insertion ────────────────────────────────

$name_esc     = htmlspecialchars($name,     ENT_QUOTES, 'UTF-8');
$phone_esc    = htmlspecialchars($phone,    ENT_QUOTES, 'UTF-8');
$interest_esc = htmlspecialchars($interest, ENT_QUOTES, 'UTF-8');
$message_esc  = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
$email_display = $email !== ''
    ? htmlspecialchars($email, ENT_QUOTES, 'UTF-8')
    : 'Not provided';
$reply_to_addr = $email !== '' ? $email : RECIPIENT_EMAIL;

// ─── 6. Build the HTML Email ─────────────────────────────────────────────────

$html_body = '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  body        { font-family: "Segoe UI", Arial, sans-serif; background: #f5f5f0; margin: 0; padding: 0; }
  .wrapper    { max-width: 600px; margin: 32px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
  .header     { background: linear-gradient(135deg, #d97c2b 0%, #b05e1a 100%); padding: 32px 36px; }
  .header h1  { color: #fff; margin: 0; font-size: 22px; letter-spacing: .5px; }
  .header p   { color: rgba(255,255,255,.85); margin: 6px 0 0; font-size: 13px; }
  .body       { padding: 32px 36px; }
  .field      { margin-bottom: 20px; }
  .label      { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: #888; margin-bottom: 4px; }
  .value      { font-size: 15px; color: #1a1a1a; line-height: 1.6; word-break: break-word; }
  .divider    { border: none; border-top: 1px solid #eee; margin: 24px 0; }
  .msg-box    { background: #f9f6f1; border-left: 4px solid #d97c2b; border-radius: 6px; padding: 16px 20px; }
  .footer     { background: #f5f5f0; padding: 20px 36px; font-size: 12px; color: #aaa; text-align: center; }
  .badge      { display: inline-block; background: #d97c2b; color: #fff; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 99px; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 16px; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>&#128236; New Website Enquiry</h1>
    <p>Received from the HANU PAINTS AND DECOR contact form</p>
  </div>
  <div class="body">
    <span class="badge">Enquiry Details</span>
    <div class="field">
      <div class="label">Name</div>
      <div class="value">' . $name_esc . '</div>
    </div>
    <div class="field">
      <div class="label">Phone Number</div>
      <div class="value">' . $phone_esc . '</div>
    </div>
    <div class="field">
      <div class="label">Email Address</div>
      <div class="value">' . $email_display . '</div>
    </div>
    <div class="field">
      <div class="label">Interested In</div>
      <div class="value">' . $interest_esc . '</div>
    </div>
    <hr class="divider">
    <div class="field">
      <div class="label">Message</div>
      <div class="msg-box value">' . $message_esc . '</div>
    </div>
  </div>
  <div class="footer">
    This message was sent via the contact form on the HANU PAINTS AND DECOR website.<br>
    To reply directly, contact: ' . $email_display . '
  </div>
</div>
</body>
</html>';

// ─── 7. Mail Headers ─────────────────────────────────────────────────────────

$subject  = "Website enquiry: {$interest} \xe2\x80\x94 {$name}";

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: " . SENDER_NAME . " <" . SENDER_EMAIL . ">\r\n";
$headers .= "Reply-To: {$reply_to_addr}\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

// ─── 8. Send & Respond ───────────────────────────────────────────────────────

$sent = mail(RECIPIENT_EMAIL, $subject, $html_body, $headers);

if ($sent) {
    echo json_encode([
        'success' => true,
        'message' => 'Thank you, ' . $name_esc . '! Your enquiry has been received. We\'ll get back to you shortly.',
    ]);
} else {
    error_log('[HANU PAINTS AND DECOR] mail() failed — enquiry from: ' . $name . ' (' . $phone . ')');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Sorry, something went wrong while sending your message. Please call us directly or try again later.',
    ]);
}
exit;
