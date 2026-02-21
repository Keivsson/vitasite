<?php

if (! defined('ABSPATH')) {
    exit;
}

function neural_cv_theme_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
}
add_action('after_setup_theme', 'neural_cv_theme_setup');

function neural_cv_theme_assets(): void
{
    wp_enqueue_style('neural-cv-theme-style', get_stylesheet_uri(), [], '1.0.0');
    wp_enqueue_script(
        'vis-network',
        'https://unpkg.com/vis-network/standalone/umd/vis-network.min.js',
        [],
        '9.1.9',
        true
    );
    wp_enqueue_script(
        'neural-cv-network',
        get_template_directory_uri() . '/assets/js/network.js',
        ['vis-network'],
        '1.0.0',
        true
    );

    $neurons = function_exists('neural_cv_get_neuron_payload')
        ? neural_cv_get_neuron_payload()
        : [];

    wp_localize_script('neural-cv-network', 'NeuralCVData', [
        'isLoggedIn' => is_user_logged_in(),
        'loginUrl' => wp_login_url(get_permalink()),
        'registerUrl' => wp_registration_url(),
        'nodes' => $neurons['nodes'] ?? [],
        'edges' => $neurons['edges'] ?? [],
    ]);
}
add_action('wp_enqueue_scripts', 'neural_cv_theme_assets');
