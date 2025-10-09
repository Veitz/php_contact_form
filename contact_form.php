<?php
session_start();

// Konfiguration
// (Anpassen)
$MAX_AGE_SECONDS = 60 * 60; // Maximum Alter eines Tokens (optional)
$MIN_SUBMIT_SECONDS = 5;    // Minimalzeit bevor Formular erlaubt ist (verhindert schnelle Bots)

// CSRF: Einmal-Token erzeugen (single-use)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Captcha: einfache Rechenaufgabe (Zahlen 2..12) + ein salt-token, damit Bots nicht einfach raten
$n1 = rand(2, 9);
$n2 = rand(2, 12);
$_SESSION['captcha_answer'] = $n1 + $n2;

// Timestamp für Zeitprüfung
$_SESSION['form_generated_at'] = time();

// Optional: eine zufällige challenge-id (verhindert wiederverwendung)
$_SESSION['form_challenge'] = bin2hex(random_bytes(8));
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<title>Kontaktformular</title>
<style>
/* Honeypot: für Menschen unsichtbar, für einfache Bots sichtbar */
.hp-field { position: absolute; left: -10000px; top: auto; width: 1px; height: 1px; overflow: hidden; }
.notice { margin-bottom: 1rem; color: green; }
.error { margin-bottom: 1rem; color: red; }
</style>
</head>
<body>

<?php
// Rückmeldung anzeigen (aus Session gesetzt von contact.php)
if (!empty($_SESSION['response_message'])) {
    $msg = htmlspecialchars($_SESSION['response_message']);
    $type = !empty($_SESSION['response_error']) ? 'error' : 'notice';
    echo "<p class=\"$type\">$msg</p>";
    unset($_SESSION['response_message'], $_SESSION['response_error']);
}
?>

<form action="contact.php" method="post">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <input type="hidden" name="form_challenge" value="<?php echo htmlspecialchars($_SESSION['form_challenge']); ?>">

    <label for="email">E-Mail:</label><br>
    <input id="email" name="email" type="email" required><br><br>

    <label for="reason">Grund:</label><br>
    <select id="reason" name="reason" required>
        <option value="Questions">Questions</option>
        <option value="Admiration">Admiration</option>
        <option value="Can I get your number?">Can I get your number?</option>
    </select><br><br>

    <label for="message">Nachricht:</label><br>
    <textarea id="message" name="message" rows="6" required></textarea><br><br>

    <label>
        <input type="checkbox" name="copy"> Eine Kopie an mich senden
    </label><br><br>

    <!-- Captcha -->
    <label for="captcha">Captcha: Wie viel ergibt <?php echo $n1 . " + " . $n2; ?> ?</label><br>
    <input id="captcha" name="captcha" type="text" required autocomplete="off"><br><br>

    <!-- Honeypot: sichtbare Feldname 'website' - Bots füllen es, Menschen nicht -->
    <div class="hp-field" aria-hidden="true">
        <label for="website">Website (leave empty)</label>
        <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
    </div>

    <input type="submit" value="Senden">
</form>

</body>
</html>
