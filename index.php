<?php
session_start();

// CSRF + Captcha vorbereiten
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf_token = $_SESSION['csrf_token'];
$n1 = rand(2,9);
$n2 = rand(2,12);
$_SESSION['captcha_answer'] = $n1 + $n2;
$_SESSION['form_generated_at'] = time();
$_SESSION['form_challenge'] = bin2hex(random_bytes(8));

// Rückmeldung aus Session
$msg = '';
$msg_style = '';
if (!empty($_SESSION['response_message'])) {
    $msg = htmlspecialchars($_SESSION['response_message']);
    $msg_style = !empty($_SESSION['response_error']) ? 'color:red;' : 'color:green;';
    unset($_SESSION['response_message'], $_SESSION['response_error']);
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Kontaktformular</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="css/skeleton.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container" style="max-width:600px; margin:2rem auto; padding:2rem; border:1px solid #ccc; border-radius:10px; background-color:#f9f9f9; box-shadow:0 2px 8px rgba(0,0,0,0.1);">

    <h3>Kontaktieren Sie uns</h3>

    <?php if($msg): ?>
        <p style="<?php echo $msg_style; ?>"><?php echo $msg; ?></p>
    <?php endif; ?>

    <form action="contact.php" method="post" style="display:flex; flex-direction:column; gap:1rem;">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <input type="hidden" name="form_challenge" value="<?php echo htmlspecialchars($_SESSION['form_challenge']); ?>">
        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" placeholder="test@mailbox.com" required style="padding:0.5rem; border-radius:5px; border:1px solid #ccc;">

        <label for="reason">Grund:</label>
        <select id="reason" name="reason" required style="padding:0.5rem; border-radius:5px; border:1px solid #ccc;">
            <option value="Questions">Questions</option>
            <option value="Admiration">Admiration</option>
            <option value="Can I get your number?">Can I get your number?</option>
        </select>

        <label for="message">Nachricht:</label>
        <textarea id="message" name="message" placeholder="Hi …" rows="5" required style="padding:0.5rem; border-radius:5px; border:1px solid #ccc;"></textarea>

        <label>
            <input type="checkbox" name="copy"> Eine Kopie an mich senden
        </label>

        <label for="captcha">Captcha: Wie viel ergibt <?php echo $n1 . " + " . $n2; ?> ?</label>
        <input type="text" id="captcha" name="captcha" required autocomplete="off" style="padding:0.5rem; border-radius:5px; border:1px solid #ccc;">

        <!-- Honeypot für Bots -->
        <div style="position:absolute; left:-10000px; top:auto; width:1px; height:1px; overflow:hidden;">
            <label for="website">Website</label>
            <input type="text" name="website" tabindex="-1" autocomplete="off">
        </div>

        <input type="submit" value="Senden" style="padding:0.7rem; border-radius:5px; border:none; background-color:#007bff; color:white; cursor:pointer;">
    </form>
</div>
</body>
</html>
