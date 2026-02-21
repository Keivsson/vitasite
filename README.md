# Neural CV für WordPress (Theme + Plugin)

Dieses Repository enthält:

- **Theme:** `wp-content/themes/neural-cv-theme`
- **Plugin:** `wp-content/plugins/neural-cv-auth-export`

Die Lösung visualisiert den Lebenslauf als interaktives neuronales Netzwerk in schwarz/weiß/königsblau mit Drag-&-Drop-Neuronen.

## 1) Installation

1. Kopiere das Theme nach:
   - `wp-content/themes/neural-cv-theme`
2. Kopiere das Plugin nach:
   - `wp-content/plugins/neural-cv-auth-export`
3. Aktiviere im WordPress-Backend:
   - **Design → Themes → Neural CV Theme**
   - **Plugins → Neural CV Auth & Export**
4. Lege eine statische Startseite fest:
   - **Einstellungen → Lesen → Startseite**

## 2) Inhalte aufbauen (Neuronen)

1. Gehe zu **Neuronen** (Custom Post Type).
2. Erstelle pro Erfahrung/Arbeitgeber/Projekt/Skill/Hobby einen Eintrag.
3. Nutze Meta-Felder:
   - `neural_cv_public` (`true`/`false`) – ohne Login sichtbar.
   - `neural_cv_group` (`job`, `project`, `skill`, `hobby`) – Farbe/Typ im Netzwerk.
   - `neural_cv_order_weight` (Zahl) – Verbindungsdichte + Priorität im PDF.

## 3) Zugriff & 2FA per SMS

### Standard-Verhalten
- Nicht eingeloggte Besucher sehen nur Name + LinkedIn + Netzwerk-Überblick.
- Klick auf geschützte Neuronen zeigt Zugriffshinweis.
- Öffentliche Neuronen (`neural_cv_public=true`) sind auch ohne Registrierung aufrufbar.

### SMS-2FA einrichten (Twilio)
Setze in WordPress Optionen (z. B. per `wp option update` oder Admin-UI):

- `neural_cv_twilio_sid`
- `neural_cv_twilio_token`
- `neural_cv_twilio_from`

Dann können eingeloggte Nutzer REST-Endpunkte nutzen:

- `POST /wp-json/neural-cv/v1/request-2fa` mit `phone`
- `POST /wp-json/neural-cv/v1/verify-2fa` mit `code`

Nach erfolgreicher Verifikation wird `neural_cv_2fa_verified=1` beim Nutzer gesetzt.

## 4) PDF-Export

- Button auf der Startseite: **PDF Lebenslauf exportieren**
- Endpoint: `POST /wp-json/neural-cv/v1/export-pdf`
- Voraussetzung:
  - Login
  - 2FA erfolgreich
  - `dompdf/dompdf` installiert

Export-Reihenfolge ist chronologisch über `neural_cv_order_weight` absteigend (zuletzt/aktuell zuerst).

## 5) Dompdf installieren

Im WordPress-Root:

```bash
composer require dompdf/dompdf
```

## 6) Optional: Sichtbare Profildaten auf der Startseite

Setze Optionen:

- `neural_cv_full_name`
- `neural_cv_linkedin_url`

Beispiel:

```bash
wp option update neural_cv_full_name "Max Mustermann"
wp option update neural_cv_linkedin_url "https://www.linkedin.com/in/max-mustermann"
```
