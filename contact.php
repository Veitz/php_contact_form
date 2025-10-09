<?php
session_start();

// === Konfiguration ===
$TO_EMAIL = "contact@deine-domain.de"; // <-- deine Empfängeradresse
$MAX_PER_HOUR = 5;                    // max Einsendungen pro IP pro Stunde
$RATEFILE = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'contact_rate.json';
$LOGFILE  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'contact_log.txt';

// Hilfsfunktionen
function respond_and_redirect($message, $is_error = false) {
    $_SESSION['response_message'] = $message;
    $_SESSION['response_error'] = $is_error ? 1 : 0;
    // CSRF Token ungültig machen (single-use)
    unset($_SESSION['csrf_token']);

    $redirect = $_POST['redirect'] ?? 'contact_form.php';
    header("Location: " . $redirect);
    exit;
}


function safe_header_value($value) {
    // Verhindere Header-Injection (keine CRLF-Zeichen)
    return str_replace(["\r", "\n"], '', trim($value));
}

// Rate-Limiting (file-basiert, keyed by IP)
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

$rateData = [];
if (file_exists($RATEFILE)) {
    $raw = @file_get_contents($RATEFILE);
    $rateData = $raw ? json_decode($raw, true) : [];
    if (!is_array($rateData)) $rateData = [];
}

// Bereinige alte Einträge
$now = time();
foreach ($rateData as $r_ip => $timestamps) {
    $rateData[$r_ip] = array_filter($timestamps, function($t) use ($now) {
        return ($now - $t) <= 3600; // letzte Stunde
    });
    if (empty($rateData[$r_ip])) unset($rateData[$r_ip]);
}

// === Validierungen ===
// 1) CSRF
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    respond_and_redirect("Ungültiges Formular (CSRF-Fehler).", true);
}

// 2) Honeypot: darf nicht gefüllt sein
if (!empty($_POST['website'])) {
    // bot detected — log und stille ablehnung
    // keine Rückmeldung für Bots
    respond_and_redirect("Fehler beim Senden (Spamverdacht).", true);
}

// 3) Challenge/form id prüfen (optional)
if (empty($_POST['form_challenge']) || empty($_SESSION['form_challenge']) || $_POST['form_challenge'] !== $_SESSION['form_challenge']) {
    respond_and_redirect("Ungültige Anfrage (Formularcharakteristik stimmt nicht).", true);
}

// 4) Zeitprüfung
$gen = $_SESSION['form_generated_at'] ?? 0;
if ($gen === 0 || ($now - $gen) < 5) {
    respond_and_redirect("Formular zu schnell abgeschickt — Spamverdacht.", true);
}
if (($now - $gen) > 3600) {
    // Optional: Formular zu alt
    respond_and_redirect("Formular ist abgelaufen. Bitte lade die Seite neu und versuch es noch einmal.", true);
}

// 5) Rate-Limit prüfen
$ip_timestamps = $rateData[$ip] ?? [];
if (count($ip_timestamps) >= $MAX_PER_HOUR) {
    respond_and_redirect("Zu viele Einsendungen von Ihrer IP. Bitte versuchen Sie es später erneut.", true);
}

// 6) Pflichtfelder & Sanitizing
$email_raw = $_POST['email'] ?? '';
$reason_raw = $_POST['reason'] ?? '';
$message_raw = $_POST['message'] ?? '';
$copy = isset($_POST['copy']);

$email = filter_var($email_raw, FILTER_VALIDATE_EMAIL);
$reason = trim(filter_var($reason_raw, FILTER_SANITIZE_STRING));
$message = trim(filter_var($message_raw, FILTER_SANITIZE_STRING));

if (!$email || empty($reason) || empty($message)) {
    respond_and_redirect("Bitte füllen Sie alle Felder korrekt aus.", true);
}

// 7) Captcha prüfen
$captcha_input = trim($_POST['captcha'] ?? '');
$captcha_expected = $_SESSION['captcha_answer'] ?? null;
if ($captcha_expected === null || !ctype_digit($captcha_input) || intval($captcha_input) !== intval($captcha_expected)) {
    respond_and_redirect("Captcha falsch. Bitte versuchen Sie es erneut.", true);
}

// 8) Schutz gegen Header-Injection in E-Mail-Feldern
$email_safe = safe_header_value($email);
$subject_safe = safe_header_value("Kontaktformular: " . $reason);

// 9) Bereite Mail vor
$body = "Neue Nachricht vom Kontaktformular\n\n";
$body .= "Von: " . $email_safe . "\n";
$body .= "IP: " . $ip . "\n";
$body .= "Zeit: " . date('c') . "\n";
$body .= "Grund: " . $reason . "\n\n";
$body .= "Nachricht:\n" . $message . "\n";

// Header
$headers = "From: " . $email_safe . "\r\n";
$headers .= "Reply-To: " . $email_safe . "\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// Versenden
$mail_ok = false;
try {
    $mail_ok = mail($TO_EMAIL, $subject_safe, $body, $headers);
} catch (Exception $e) {
    $mail_ok = false;
}

// Optional: Kopie an Absender
if ($mail_ok && $copy) {
    $copy_subject = "Kopie Ihrer Nachricht: " . $reason;
    $copy_body = "Dies ist eine Kopie Ihrer Nachricht, gesendet am " . date('c') . "\n\n" . $body;
    // sende Kopie (still best effort)
    mail($email_safe, $copy_subject, $copy_body, $headers);
}

// 10) Logging (append)
$log_line = "[" . date('Y-m-d H:i:s') . "] IP=$ip email={$email_safe} ok=" . ($mail_ok ? '1' : '0') . " reason=" . substr($reason,0,100) . "\n";
@file_put_contents($LOGFILE, $log_line, FILE_APPEND | LOCK_EX);

// 11) Update Rate Data (nur wenn Mail erfolgreich)
if ($mail_ok) {
    $rateData[$ip][] = $now;
    @file_put_contents($RATEFILE, json_encode($rateData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    // CSRF / captcha entfernen (single-use)
    unset($_SESSION['csrf_token'], $_SESSION['captcha_answer'], $_SESSION['form_generated_at'], $_SESSION['form_challenge']);
    respond_and_redirect("Danke! Ihre Nachricht wurde gesendet.");
} else {
    respond_and_redirect("Fehler beim Senden der Nachricht. Bitte versuchen Sie es später.", true);
}
