# Kontaktformular

## Beschreibung
Dies ist ein sicheres und responsives Kontaktformular für eine Website.  
Es kann auf jeder Seite eingebunden werden und schützt vor Spam mit:

- **CSRF-Token** (einmaliger Schutz pro Formular)(zufälliger, geheimer Wert, der von einer Webanwendung generiert und an den Benutzer gesendet wird, um schädliche Anfragen (CSRF-Angriffe) zu verhindern)
- **Captcha** (einfache Rechenaufgabe)
- **Honeypot-Feld** für Bots
- **Rate-Limiting** pro IP-Adresse (max. 5 Einsendungen pro Stunde)
- Option, eine Kopie der Nachricht an den Absender zu senden

Alle Einsendungen werden geloggt und können bei Bedarf nachvollzogen werden.

## Dateien
- `index.php` – Hauptformularseite, kann beliebig angepasst werden
- `contact.php` – verarbeitet das Formular, versendet E-Mail, protokolliert Logs
- `logs/` – Verzeichnis für `contact_log.txt` und `contact_rate.json` (muss Schreibrechte haben)
- `css/normalize.css`, `css/skeleton.css`, `style.css` – optionales Styling

## Installation
1. Alle Dateien auf den Webserver hochladen.
2. Schreibrechte für den Ordner `logs/` setzen:
   ```bash
   chmod 775 logs
   chown www-data:www-data logs

  
$TO_EMAIL = "contact@meine-domain.de";  // bitte anpassen
