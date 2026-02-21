<?php
if (! defined('ABSPATH')) {
    exit;
}

$full_name = get_option('neural_cv_full_name', 'Vorname Nachname');
$linkedin = get_option('neural_cv_linkedin_url', 'https://www.linkedin.com/');
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="site-shell">
    <header class="site-header">
        <div class="identity">
            <h1><?php echo esc_html($full_name); ?></h1>
            <p>
                LinkedIn:
                <a href="<?php echo esc_url($linkedin); ?>" rel="noreferrer noopener" target="_blank">
                    <?php echo esc_html($linkedin); ?>
                </a>
            </p>
        </div>
        <div class="button-row">
            <?php if (is_user_logged_in()) : ?>
                <button class="btn primary" id="export-cv">PDF Lebenslauf exportieren</button>
            <?php else : ?>
                <a class="btn" href="<?php echo esc_url(wp_login_url(get_permalink())); ?>">Anmelden</a>
                <a class="btn primary" href="<?php echo esc_url(wp_registration_url()); ?>">Registrieren + 2FA</a>
            <?php endif; ?>
        </div>
    </header>

    <main>
        <div id="neural-cv-network" aria-label="Lebenslauf als neuronales Netzwerk"></div>
        <p class="notice-box" id="network-notice">
            <strong>Hinweis:</strong>
            Nur freigegebene Neuronen sind öffentlich sichtbar. Für den Vollzugriff mit Detailinhalten bitte registrieren,
            Rufnummer hinterlegen und die SMS-2FA abschließen.
        </p>
    </main>
</div>
<?php wp_footer(); ?>
</body>
</html>
