<?php
/**
 * Plugin Name: Neural CV Auth & Export
 * Description: Stellt neuronale Lebenslauf-Daten, SMS-2FA, öffentliche Neuronen und PDF-Export für das Neural CV Theme bereit.
 * Version: 1.0.0
 * Author: Codex
 */

if (! defined('ABSPATH')) {
    exit;
}

function neural_cv_register_post_type(): void
{
    register_post_type('neural_neuron', [
        'label' => 'Neuronen',
        'public' => true,
        'show_in_rest' => true,
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
        'menu_icon' => 'dashicons-share',
    ]);
}
add_action('init', 'neural_cv_register_post_type');

function neural_cv_register_meta(): void
{
    register_post_meta('neural_neuron', 'neural_cv_public', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'boolean',
        'default' => false,
    ]);
    register_post_meta('neural_neuron', 'neural_cv_group', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'default' => 'skill',
    ]);
    register_post_meta('neural_neuron', 'neural_cv_order_weight', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'integer',
        'default' => 1,
    ]);
}
add_action('init', 'neural_cv_register_meta');

function neural_cv_get_neuron_payload(): array
{
    $query = new WP_Query([
        'post_type' => 'neural_neuron',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ]);

    $nodes = [];
    $edges = [];
    $index = 1;

    foreach ($query->posts as $post) {
        $public = (bool) get_post_meta($post->ID, 'neural_cv_public', true);
        $group = get_post_meta($post->ID, 'neural_cv_group', true) ?: 'skill';

        $nodes[] = [
            'id' => $post->ID,
            'label' => $post->post_title,
            'group' => $group,
            'url' => get_permalink($post),
            'public' => $public,
            'size' => 16 + (int) get_post_meta($post->ID, 'neural_cv_order_weight', true),
        ];

        if ($index > 1) {
            $edges[] = [
                'from' => $query->posts[$index - 2]->ID,
                'to' => $post->ID,
                'value' => (int) get_post_meta($post->ID, 'neural_cv_order_weight', true) ?: 1,
            ];
        }
        $index++;
    }

    return ['nodes' => $nodes, 'edges' => $edges];
}

function neural_cv_filter_neuron_content(string $content): string
{
    if (get_post_type() !== 'neural_neuron') {
        return $content;
    }

    $public = (bool) get_post_meta(get_the_ID(), 'neural_cv_public', true);
    if ($public || is_user_logged_in()) {
        return $content;
    }

    $login_url = wp_login_url(get_permalink());
    $register_url = wp_registration_url();

    return '<p><strong>Vollzugriff gesperrt:</strong> Bitte registrieren, Rufnummer für SMS-2FA hinterlegen und einloggen.</p>'
        . '<p><a href="' . esc_url($login_url) . '">Anmelden</a> | <a href="' . esc_url($register_url) . '">Registrieren</a></p>';
}
add_filter('the_content', 'neural_cv_filter_neuron_content', 20);

function neural_cv_register_rest_routes(): void
{
    register_rest_route('neural-cv/v1', '/request-2fa', [
        'methods' => 'POST',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
        'callback' => 'neural_cv_rest_request_2fa',
    ]);

    register_rest_route('neural-cv/v1', '/verify-2fa', [
        'methods' => 'POST',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
        'callback' => 'neural_cv_rest_verify_2fa',
    ]);

    register_rest_route('neural-cv/v1', '/export-pdf', [
        'methods' => 'POST',
        'permission_callback' => function () {
            return is_user_logged_in() && get_user_meta(get_current_user_id(), 'neural_cv_2fa_verified', true);
        },
        'callback' => 'neural_cv_rest_export_pdf',
    ]);
}
add_action('rest_api_init', 'neural_cv_register_rest_routes');

function neural_cv_rest_request_2fa(WP_REST_Request $request): WP_REST_Response
{
    $phone = sanitize_text_field((string) $request->get_param('phone'));
    if ($phone === '') {
        return new WP_REST_Response(['message' => 'Telefonnummer erforderlich.'], 400);
    }

    update_user_meta(get_current_user_id(), 'neural_cv_phone', $phone);
    $code = (string) random_int(100000, 999999);
    update_user_meta(get_current_user_id(), 'neural_cv_2fa_code', $code);

    $sent = neural_cv_send_sms($phone, 'Dein Neural-CV Code: ' . $code);

    return new WP_REST_Response([
        'sent' => $sent,
        'message' => $sent
            ? 'SMS wurde versendet.'
            : 'SMS konnte nicht versendet werden. Prüfe Twilio-Optionen.',
    ], $sent ? 200 : 500);
}

function neural_cv_rest_verify_2fa(WP_REST_Request $request): WP_REST_Response
{
    $input = sanitize_text_field((string) $request->get_param('code'));
    $stored = (string) get_user_meta(get_current_user_id(), 'neural_cv_2fa_code', true);

    if ($input !== '' && hash_equals($stored, $input)) {
        update_user_meta(get_current_user_id(), 'neural_cv_2fa_verified', 1);
        delete_user_meta(get_current_user_id(), 'neural_cv_2fa_code');

        return new WP_REST_Response(['verified' => true], 200);
    }

    return new WP_REST_Response(['verified' => false], 403);
}

function neural_cv_rest_export_pdf(): WP_REST_Response
{
    if (! class_exists('Dompdf\Dompdf')) {
        return new WP_REST_Response([
            'message' => 'Für PDF-Export bitte dompdf via Composer installieren.',
        ], 500);
    }

    $query = new WP_Query([
        'post_type' => 'neural_neuron',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_key' => 'neural_cv_order_weight',
        'orderby' => 'meta_value_num',
        'order' => 'DESC',
    ]);

    $html = '<h1>Lebenslauf Export</h1>';
    foreach ($query->posts as $post) {
        $html .= '<h2>' . esc_html($post->post_title) . '</h2>';
        $html .= wpautop(wp_kses_post($post->post_content));
    }

    $dompdf = new Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $upload_dir = wp_upload_dir();
    $filename = 'neural-cv-' . time() . '.pdf';
    $path = trailingslashit($upload_dir['path']) . $filename;
    file_put_contents($path, $dompdf->output());

    return new WP_REST_Response([
        'url' => trailingslashit($upload_dir['url']) . $filename,
    ], 200);
}

function neural_cv_send_sms(string $phone, string $message): bool
{
    $sid = (string) get_option('neural_cv_twilio_sid', '');
    $token = (string) get_option('neural_cv_twilio_token', '');
    $from = (string) get_option('neural_cv_twilio_from', '');

    if ($sid === '' || $token === '' || $from === '') {
        return false;
    }

    $response = wp_remote_post('https://api.twilio.com/2010-04-01/Accounts/' . $sid . '/Messages.json', [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode($sid . ':' . $token),
        ],
        'body' => [
            'From' => $from,
            'To' => $phone,
            'Body' => $message,
        ],
    ]);

    return ! is_wp_error($response) && wp_remote_retrieve_response_code($response) < 300;
}
