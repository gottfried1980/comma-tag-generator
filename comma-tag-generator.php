<?php
/*
Plugin Name: Comma-separated Tag and Category Generator
Plugin URI: https://www.gancpt.at
Description: Generates WordPress tags and categories from a configurable comma-separated list. Supports English, Italian, French, and German.
Version: 1.11
Author: Gottfried Aumann
Author URI: https://www.gancpt.at
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$skipped_keywords = [];

// Register the admin menu
add_action('admin_menu', 'cstg_tag_category_generator_menu');

function cstg_tag_category_generator_menu() {
    add_menu_page(
        esc_html(cstg_translate('Tag and Category Generator')), // Page title
        esc_html(cstg_translate('Tag and Category Generator')), // Menu title
        'manage_options',                                       // Capability required to access the page
        'cstg-tag-category-generator',                          // Menu slug
        'cstg_tag_category_generator_options_page',             // Callback function to render the page
        'dashicons-tag',                                        // Icon URL or dashicon
        80                                                      // Position on the menu
    );
}

// Display the options page
function cstg_tag_category_generator_options_page() {
    global $skipped_keywords;

    // Get the selected language
    $selected_language = get_option('cstg_tag_category_generator_language', 'en');

    ?>
    <div class="wrap">
        <h1><?php echo esc_html(cstg_translate('Comma-Separated Tag and Category Generator')); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('cstg_tag_category_generator_options_group');
            do_settings_sections('cstg-tag-category-generator');
            submit_button();
            ?>
        </form>
        <?php if (!empty($skipped_keywords) && isset($_GET['settings-updated']) && $_GET['settings-updated']): ?>
            <div class="notice notice-warning is-dismissible">
                <p><?php echo esc_html(cstg_translate('The following keywords were skipped because they already existed:')); ?></p>
                <ul>
                    <?php foreach ($skipped_keywords as $keyword): ?>
                        <li><?php echo esc_html($keyword); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

// Register and define the settings
add_action('admin_init', 'cstg_tag_category_generator_settings_init');

function cstg_tag_category_generator_settings_init() {
    register_setting(
        'cstg_tag_category_generator_options_group', // Option group
        'cstg_tag_category_generator_tags',          // Option name
        'sanitize_textarea_field'                    // Sanitize the input
    );

    register_setting(
        'cstg_tag_category_generator_options_group', // Option group
        'cstg_tag_category_generator_categories',    // Option name for categories
        'sanitize_textarea_field'                    // Sanitize the input
    );

    register_setting(
        'cstg_tag_category_generator_options_group', // Option group
        'cstg_tag_category_generator_language',      // Option name for language
        'cstg_sanitize_language'                     // Custom sanitization function
    );

    add_settings_section(
        'cstg_tag_category_generator_section',       // ID
        esc_html(cstg_translate('Tag and Category Configuration')), // Title
        'cstg_tag_category_generator_section_callback', // Callback
        'cstg-tag-category-generator'                // Page
    );

    add_settings_field(
        'cstg_tag_category_generator_tags',          // ID
        esc_html(cstg_translate('Comma-Separated Tags')),  // Title
        'cstg_tag_category_generator_tags_callback', // Callback
        'cstg-tag-category-generator',               // Page
        'cstg_tag_category_generator_section'        // Section
    );

    add_settings_field(
        'cstg_tag_category_generator_categories',    // ID
        esc_html(cstg_translate('Comma-Separated Categories')), // Title
        'cstg_tag_category_generator_categories_callback', // Callback
        'cstg-tag-category-generator',               // Page
        'cstg_tag_category_generator_section'        // Section
    );

    add_settings_field(
        'cstg_tag_category_generator_language',      // ID
        esc_html(cstg_translate('Language Selection')), // Title
        'cstg_tag_category_generator_language_callback', // Callback
        'cstg-tag-category-generator',               // Page
        'cstg_tag_category_generator_section'        // Section
    );
}

function cstg_sanitize_language($input) {
    $valid_languages = ['en', 'it', 'fr', 'de'];
    return in_array($input, $valid_languages) ? $input : 'en';
}

function cstg_tag_category_generator_section_callback() {
    echo '<p>' . esc_html(cstg_translate('Enter the tags and categories you want to generate, separated by commas. Example: tag1, tag2, tag3')) . '</p>';
}

function cstg_tag_category_generator_tags_callback() {
    $tags = esc_textarea(get_option('cstg_tag_category_generator_tags', ''));
    echo '<textarea name="cstg_tag_category_generator_tags" rows="5" cols="50" class="large-text code">' . $tags . '</textarea>';
}

function cstg_tag_category_generator_categories_callback() {
    $categories = esc_textarea(get_option('cstg_tag_category_generator_categories', ''));
    echo '<textarea name="cstg_tag_category_generator_categories" rows="5" cols="50" class="large-text code">' . $categories . '</textarea>';
}

function cstg_tag_category_generator_language_callback() {
    $selected_language = get_option('cstg_tag_category_generator_language', 'en');
    ?>
    <label>
        <input type="radio" name="cstg_tag_category_generator_language" value="en" <?php checked('en', $selected_language); ?>>
        <?php echo esc_html(cstg_translate('English')); ?>
    </label><br>
    <label>
        <input type="radio" name="cstg_tag_category_generator_language" value="it" <?php checked('it', $selected_language); ?>>
        <?php echo esc_html(cstg_translate('Italian')); ?>
    </label><br>
    <label>
        <input type="radio" name="cstg_tag_category_generator_language" value="fr" <?php checked('fr', $selected_language); ?>>
        <?php echo esc_html(cstg_translate('French')); ?>
    </label><br>
    <label>
        <input type="radio" name="cstg_tag_category_generator_language" value="de" <?php checked('de', $selected_language); ?>>
        <?php echo esc_html(cstg_translate('German')); ?>
    </label>
    <?php
}

// Function to create tags and categories from the comma-separated lists and track skipped keywords
add_action('admin_init', 'cstg_tag_category_generator_create_terms');

function cstg_tag_category_generator_create_terms() {
    global $skipped_keywords;
    $tags = get_option('cstg_tag_category_generator_tags');
    $categories = get_option('cstg_tag_category_generator_categories');

    // Reset the skipped keywords on each save
    $skipped_keywords = [];

    // Create tags
    if (!empty($tags)) {
        $tag_list = explode(',', $tags);
        foreach ($tag_list as $tag) {
            $tag = sanitize_text_field(trim($tag));
            if (term_exists($tag, 'post_tag')) {
                $skipped_keywords[] = $tag;
            } else {
                if (!empty($tag)) {
                    wp_insert_term($tag, 'post_tag');
                }
            }
        }
    }

    // Create categories
    if (!empty($categories)) {
        $category_list = explode(',', $categories);
        foreach ($category_list as $category) {
            $category = sanitize_text_field(trim($category));
            if (term_exists($category, 'category')) {
                $skipped_keywords[] = $category;
            } else {
                if (!empty($category)) {
                    wp_insert_term($category, 'category');
                }
            }
        }
    }
}

// Function to handle translations based on the selected language
function cstg_translate($text) {
    $selected_language = get_option('cstg_tag_category_generator_language', 'en');

    $translations = [
        'en' => [
            'Tag and Category Generator' => 'Tag and Category Generator',
            'Comma-Separated Tag and Category Generator' => 'Comma-Separated Tag and Category Generator',
            'Tag and Category Configuration' => 'Tag and Category Configuration',
            'Comma-Separated Tags' => 'Comma-Separated Tags',
            'Comma-Separated Categories' => 'Comma-Separated Categories',
            'Language Selection' => 'Language Selection',
            'Enter the tags and categories you want to generate, separated by commas. Example: tag1, tag2, tag3' => 'Enter the tags and categories you want to generate, separated by commas. Example: tag1, tag2, tag3',
            'English' => 'English',
            'Italian' => 'Italian',
            'French' => 'French',
            'German' => 'German',
            'The following keywords were skipped because they already existed:' => 'The following keywords were skipped because they already existed:',
        ],
        'it' => [
            'Tag and Category Generator' => 'Generatore di Tag e Categorie',
            'Comma-Separated Tag and Category Generator' => 'Generatore di Tag e Categorie Separati da Virgola',
            'Tag and Category Configuration' => 'Configurazione di Tag e Categorie',
            'Comma-Separated Tags' => 'Tag Separati da Virgola',
            'Comma-Separated Categories' => 'Categorie Separate da Virgola',
            'Language Selection' => 'Selezione della Lingua',
            'Enter the tags and categories you want to generate, separated by commas. Example: tag1, tag2, tag3' => 'Inserisci i tag e le categorie che vuoi generare, separati da virgole. Esempio: tag1, tag2, tag3',
            'English' => 'Inglese',
            'Italian' => 'Italiano',
            'French' => 'Francese',
            'German' => 'Tedesco',
            'The following keywords were skipped because they already existed:' => 'I seguenti termini sono stati ignorati perché esistevano già:',
        ],
        'fr' => [
            'Tag and Category Generator' => 'Générateur de Tags et Catégories',
            'Comma-Separated Tag and Category Generator' => 'Générateur de Tags et Catégories Séparés par des Virgules',
            'Tag and Category Configuration' => 'Configuration de Tags et Catégories',
            'Comma-Separated Tags' => 'Tags Séparés par des Virgules',
            'Comma-Separated Categories' => 'Catégories Séparées par des Virgules',
            'Language Selection' => 'Sélection de la Langue',
            'Enter the tags and categories you want to generate, separated by commas. Example: tag1, tag2, tag3' => 'Entrez les tags et catégories que vous souhaitez générer, séparés par des virgules. Exemple : tag1, tag2, tag3',
            'English' => 'Anglais',
            'Italian' => 'Italien',
            'French' => 'Français',
            'German' => 'Allemand',
            'The following keywords were skipped because they already existed:' => 'Les termes suivants ont été ignorés car ils existaient déjà :',
        ],
        'de' => [
            'Tag and Category Generator' => 'Tag- und Kategoriegenerator',
            'Comma-Separated Tag and Category Generator' => 'Tag- und Kategoriegenerator mit Komma-Trennung',
            'Tag and Category Configuration' => 'Tag- und Kategoriekonfiguration',
            'Comma-Separated Tags' => 'Tags mit Komma-Trennung',
            'Comma-Separated Categories' => 'Kategorien mit Komma-Trennung',
            'Language Selection' => 'Sprachauswahl',
            'Enter the tags and categories you want to generate, separated by commas. Example: tag1, tag2, tag3' => 'Geben Sie die Tags und Kategorien ein, die Sie generieren möchten, getrennt durch Kommas. Beispiel: tag1, tag2, tag3',
            'English' => 'Englisch',
            'Italian' => 'Italienisch',
            'French' => 'Französisch',
            'German' => 'Deutsch',
            'The following keywords were skipped because they already existed:' => 'Die folgenden Schlüsselwörter wurden übersprungen, da sie bereits vorhanden waren:',
        ]
    ];

    return $translations[$selected_language][$text] ?? $text;
}
?>
