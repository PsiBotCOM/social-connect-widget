<?php
/**
 * Plugin Name: ObriiFlow Social Widget
 * Plugin URI:  https://obriiflow.com/plugin/
 * Description: Floating social messenger widget with carousel, bubble, and full admin panel.
 * Version:     1.0.1
 * Author:      obriiflow.com
 * Author URI:  https://obriiflow.com
 * License:     GPL-2.0-or-later
 * Text Domain: obriiflow-social-widget
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'OBRISOWI_VERSION',         '1.0.1' );
define( 'OBRISOWI_DB_VERSION',      '1.0.1' );
define( 'OBRISOWI_DIR',             plugin_dir_path( __FILE__ ) );
define( 'OBRISOWI_URL',             plugin_dir_url( __FILE__ ) );
define( 'OBRISOWI_OPT_GENERAL',    'obrisowi_general_settings' );
define( 'OBRISOWI_OPT_MESSENGERS', 'obrisowi_messengers' );

if ( is_admin() ) {
    require_once OBRISOWI_DIR . 'admin/dashboard.php';
    require_once OBRISOWI_DIR . 'admin/settings.php';
    require_once OBRISOWI_DIR . 'admin/messengers.php';
    add_action( 'admin_menu', 'obrisowi_register_menu' );
}

function obrisowi_register_menu() {
    add_menu_page( obrisowi_t( 'admin.menu_title' ), obrisowi_t( 'admin.menu_title' ), 'manage_options', 'obriiflow-social-widget', 'sw_page_dashboard', 'dashicons-share', 80 );
    add_submenu_page( 'obriiflow-social-widget', obrisowi_t( 'admin.dashboard' ),  obrisowi_t( 'admin.dashboard' ),  'manage_options', 'obriiflow-social-widget',           'sw_page_dashboard' );
    add_submenu_page( 'obriiflow-social-widget', obrisowi_t( 'admin.messengers' ), obrisowi_t( 'admin.messengers' ), 'manage_options', 'obriiflow-social-widget-messengers', 'sw_page_messengers' );
    add_submenu_page( 'obriiflow-social-widget', obrisowi_t( 'admin.settings' ),   obrisowi_t( 'admin.settings' ),   'manage_options', 'obriiflow-social-widget-settings',   'sw_page_settings' );
}

/* ── AJAX Tracking ── */
add_action( 'wp_ajax_obrisowi_track',        'obrisowi_ajax_track' );
add_action( 'wp_ajax_nopriv_obrisowi_track', 'obrisowi_ajax_track' );

function obrisowi_ajax_track() {
    if ( ! check_ajax_referer( 'obrisowi_track_nonce', 'nonce', false ) ) {
        wp_die( '0' );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'obrisowi_stats';

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- AJAX tracking insert, caching not applicable
    if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
        wp_die( '0' );
    }

    $type      = in_array( wp_unslash( $_POST['type'] ?? '' ), [ 'view', 'click' ], true )
        ? sanitize_key( wp_unslash( $_POST['type'] ) )
        : '';
    $messenger = sanitize_key( $_POST['messenger'] ?? '' );

    if ( ! $type ) wp_die( '0' );

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- AJAX tracking insert, caching not applicable
    $inserted = $wpdb->insert(
        $table,
        [
            'type'       => $type,
            'messenger'  => ( $type === 'click' && $messenger ) ? $messenger : null,
            'created_at' => current_time( 'mysql' ),
        ],
        [ '%s', '%s', '%s' ]
    );

    if ( false !== $inserted ) {
        wp_cache_delete( 'obrisowi_total_views' );
        wp_cache_delete( 'obrisowi_total_clicks' );
        wp_cache_delete( 'obrisowi_clicks_by_messenger' );
        wp_cache_delete( 'obrisowi_daily_rows' );
    }

    wp_die( '1' );
}

/* ── Frontend ── */
add_action( 'wp_enqueue_scripts', 'obrisowi_enqueue' );

// Try all possible hooks — Flatsome uses wp_footer but some themes skip it
add_action( 'wp_footer',    'obrisowi_render_widget', 100 );
add_action( 'wp_body_open', 'obrisowi_render_widget', 100 );
add_action( 'shutdown',     'obrisowi_render_via_ob', 0 );

function obrisowi_render_via_ob() {
    if ( ! defined( 'SW_RENDERED' ) && ! is_admin() && ! wp_doing_ajax() ) {
        // Safety no-op placeholder
    }
}

$GLOBALS['obrisowi_rendered'] = false;

function obrisowi_render_widget() {
    if ( ! empty( $GLOBALS['obrisowi_rendered'] ) ) return;

    $g = obrisowi_get_general();
    if ( empty( $g['enabled'] ) ) return;
    $messengers = obrisowi_get_active_messengers();
    if ( empty( $messengers ) ) return;

    $GLOBALS['obrisowi_rendered'] = true;

    $position      = $g['position'] ?? 'right';
    $offset_side   = intval( $g['offset_side'] ?? 20 );
    $offset_bottom = intval( $g['offset_bottom'] ?? 20 );
    $bubble_text   = esc_html( $g['bubble_text'] ?: obrisowi_t( 'frontend.bubble_default' ) );
    $bubble_font_size = max( 10, min( 40, intval( $g['bubble_font_size'] ?? 15 ) ) );
    $bubble_on     = ! empty( $g['bubble_enabled'] );
    $show_labels   = ! empty( $g['show_labels'] );
    $side_prop     = ( $position === 'left' ) ? 'left' : 'right';
    $allowed_img   = [
        'img' => [
            'class'    => true,
            'src'      => true,
            'alt'      => true,
            'loading'  => true,
            'decoding' => true,
            'width'    => true,
            'height'   => true,
        ],
    ];
    ?>
    <div id="sw-widget" data-position="<?php echo esc_attr( $position ); ?>"
         style="position:fixed;<?php echo esc_attr( $side_prop ); ?>:<?php echo absint( $offset_side ); ?>px;bottom:<?php echo absint( $offset_bottom ); ?>px;z-index:99999;display:flex;flex-direction:column;align-items:<?php echo $position === 'left' ? 'flex-start' : 'flex-end'; ?>;gap:10px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;--sw-bubble-font-size:<?php echo absint( $bubble_font_size ); ?>px;">

        <div id="sw-list" class="sw-list<?php echo $show_labels ? '' : ' sw-no-labels'; ?>" aria-hidden="true">
            <?php foreach ( $messengers as $m ) : ?>
            <a href="<?php echo esc_url( $m['url'] ); ?>" class="sw-item"
               data-messenger="<?php echo esc_attr( $m['key'] ?? '' ); ?>"
               target="_blank" rel="noopener noreferrer"
               aria-label="<?php echo esc_attr( $m['label'] ); ?>">
                <span class="sw-item-icon"><?php echo wp_kses( obrisowi_get_messenger_icon_html( $m, 'sw-icon-img', '' ), $allowed_img ); ?></span>
                <?php if ( $show_labels ) : ?><span class="sw-item-label"><?php echo esc_html( $m['label'] ); ?></span><?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="sw-launcher">
            <?php if ( $bubble_on ) : ?>
            <div id="sw-bubble" class="sw-bubble" style="display:none;">
                <span><?php echo esc_html( $g['bubble_text'] ?: obrisowi_t( 'frontend.bubble_default' ) ); ?></span>
            </div>
            <?php endif; ?>

            <button id="sw-toggle" class="sw-toggle" aria-label="Open messenger list" aria-expanded="false">
                <span id="sw-carousel" class="sw-carousel">
                    <?php foreach ( $messengers as $idx => $m ) : ?>
                    <span class="sw-slide<?php echo $idx === 0 ? ' active' : ''; ?>">
                        <?php echo wp_kses( obrisowi_get_messenger_icon_html( $m, 'sw-icon-img', '' ), $allowed_img ); ?>
                    </span>
                    <?php endforeach; ?>
                </span>
                <span id="sw-close-icon" class="sw-close-icon">&#x2715;</span>
            </button>
        </div>
    </div>
    <?php
}

function obrisowi_enqueue() {
    $g = obrisowi_get_general();
    if ( empty( $g['enabled'] ) ) return;
    $messengers = obrisowi_get_active_messengers();
    if ( empty( $messengers ) ) return;

    $css_version = file_exists( OBRISOWI_DIR . 'assets/widget.css' ) ? filemtime( OBRISOWI_DIR . 'assets/widget.css' ) : OBRISOWI_VERSION;
    $js_version  = file_exists( OBRISOWI_DIR . 'assets/widget.js' ) ? filemtime( OBRISOWI_DIR . 'assets/widget.js' ) : OBRISOWI_VERSION;

    wp_enqueue_style(  'obriiflow-social-widget', OBRISOWI_URL . 'assets/widget.css', [], $css_version );
    wp_enqueue_script( 'obriiflow-social-widget', OBRISOWI_URL . 'assets/widget.js',  [], $js_version, true );
    wp_localize_script( 'obriiflow-social-widget', 'OBRISOWI_CONFIG', [
        'carousel_interval' => (float) ( $g['carousel_interval'] ?? 1.5 ) * 1000,
        'animation'         => $g['animation'] ?? 'fade',
        'position'          => $g['position'] ?? 'right',
        'bubble_enabled'    => ! empty( $g['bubble_enabled'] ),
        'bubble_delay'      => (float) ( $g['bubble_delay'] ?? 3 ) * 1000,
        'ajax_url'          => admin_url( 'admin-ajax.php' ),
        'nonce'             => wp_create_nonce( 'obrisowi_track_nonce' ),
    ] );
}

function obrisowi_enqueue_admin_assets( $hook ) {
    if ( strpos( $hook, 'obriiflow-social-widget' ) === false ) return;

    wp_enqueue_style(
        'obrisowi-admin',
        OBRISOWI_URL . 'admin/admin.css',
        [],
        OBRISOWI_VERSION
    );

    if ( strpos( $hook, 'messengers' ) !== false ) {
        $list        = get_option( OBRISOWI_OPT_MESSENGERS, [] );
        $defaults    = obrisowi_default_messengers();
        $defaults_js = [];
        foreach ( $defaults as $key => $m ) {
            $defaults_js[ $key ] = [ 'label' => $m['label'], 'icon_url' => $m['icon_url'] ];
        }
        $placeholders = [
            'instagram' => 'https://instagram.com/yourname',
            'telegram'  => 'https://t.me/yourname',
            'messenger' => 'https://m.me/yourpagename',
            'whatsapp'  => 'https://wa.me/380XXXXXXXXX',
            'viber'     => 'viber://chat?number=+380XXXXXXXXX',
            'facebook'  => 'https://facebook.com/yourpage',
            'tiktok'    => 'https://tiktok.com/@yourname',
            'twitter'   => 'https://x.com/yourname',
            'linkedin'  => 'https://linkedin.com/company/yourcompany',
            'email'     => 'mailto:hello@yourdomain.com',
            'youtube'   => 'https://youtube.com/@yourchannel',
        ];

        wp_enqueue_script(
            'obrisowi-messengers',
            OBRISOWI_URL . 'admin/messengers.js',
            [],
            OBRISOWI_VERSION,
            true
        );
        wp_localize_script( 'obrisowi-messengers', 'OBRISOWI_MESSENGERS', [
            'defs'         => $defaults_js,
            'placeholders' => $placeholders,
            'deleteLabel'  => obrisowi_t( 'admin.delete' ),
            'emptyMsg'     => obrisowi_t( 'admin.no_messengers' ),
            'nextIdx'      => count( $list ) + 100,
        ] );
    }
}
add_action( 'admin_enqueue_scripts', 'obrisowi_enqueue_admin_assets' );

/* ── Helpers ── */

function obrisowi_t( $key ) {
    static $cache = [];

    $opts = get_option( OBRISOWI_OPT_GENERAL, [] );
    $lang = $opts['language'] ?? 'en';
    if ( ! in_array( $lang, [ 'en', 'uk', 'ru' ], true ) ) {
        $lang = 'en';
    }

    if ( ! isset( $cache[ $lang ] ) ) {
        $file = OBRISOWI_DIR . 'languages/' . $lang . '.json';
        $cache[ $lang ] = file_exists( $file )
            ? ( json_decode( file_get_contents( $file ), true ) ?: [] )
            : [];
    }

    $parts = explode( '.', $key );
    $val   = $cache[ $lang ];
    foreach ( $parts as $part ) {
        if ( ! isset( $val[ $part ] ) ) {
            if ( $lang !== 'en' ) {
                if ( ! isset( $cache['en'] ) ) {
                    $f = OBRISOWI_DIR . 'languages/en.json';
                    $cache['en'] = file_exists( $f )
                        ? ( json_decode( file_get_contents( $f ), true ) ?: [] )
                        : [];
                }
                $v = $cache['en'];
                foreach ( $parts as $p ) {
                    if ( ! isset( $v[ $p ] ) ) return $key;
                    $v = $v[ $p ];
                }
                return is_string( $v ) ? $v : $key;
            }
            return $key;
        }
        $val = $val[ $part ];
    }
    return is_string( $val ) ? $val : $key;
}

function obrisowi_get_general() {
    return wp_parse_args( get_option( OBRISOWI_OPT_GENERAL, [] ), [
        'enabled'           => 1,
        'position'          => 'right',
        'offset_side'       => 20,
        'offset_bottom'     => 20,
        'show_labels'       => 1,
        'carousel_interval' => 1.5,
        'animation'         => 'fade',
        'bubble_enabled'    => 1,
        'bubble_text'       => '',
        'bubble_font_size'  => 15,
        'bubble_delay'      => 3,
        'language'          => 'en',
    ] );
}

function obrisowi_get_active_messengers() {
    $list     = get_option( OBRISOWI_OPT_MESSENGERS, [] );
    $defaults = obrisowi_default_messengers();
    $active   = [];
    foreach ( $list as $entry ) {
        $key = $entry['key'] ?? '';
        if ( ! $key || empty( $entry['url'] ) || ! isset( $defaults[ $key ] ) ) continue;
        $active[] = [
            'key'      => $key,
            'label'    => ( $entry['label'] ?? '' ) !== '' ? $entry['label'] : $defaults[ $key ]['label'],
            'url'      => $entry['url'],
            'icon_url' => $defaults[ $key ]['icon_url'],
        ];
    }
    return $active;
}

function obrisowi_get_messenger_icon_html( $messenger, $class = 'sw-icon-img', $alt = null ) {
    $icon_url = $messenger['icon_url'] ?? '';
    if ( ! $icon_url ) {
        return '';
    }

    $alt_text = null === $alt ? ( $messenger['label'] ?? '' ) : $alt;

    return sprintf(
        '<img class="%s" src="%s" alt="%s" loading="lazy" decoding="async">',
        esc_attr( $class ),
        esc_url( $icon_url ),
        esc_attr( $alt_text )
    );
}

function obrisowi_default_messengers() {
    $icons_dir = OBRISOWI_DIR . 'assets/icons/';
    $icons_url = OBRISOWI_URL . 'assets/icons/';

    $items = [
        'instagram' => [ 'label' => 'Instagram',   'order' => 1  ],
        'telegram'  => [ 'label' => 'Telegram',    'order' => 2  ],
        'messenger' => [ 'label' => 'Messenger',   'order' => 3  ],
        'whatsapp'  => [ 'label' => 'WhatsApp',    'order' => 4  ],
        'viber'     => [ 'label' => 'Viber',       'order' => 5  ],
        'facebook'  => [ 'label' => 'Facebook',    'order' => 6  ],
        'tiktok'    => [ 'label' => 'TikTok',      'order' => 7  ],
        'twitter'   => [ 'label' => 'X / Twitter', 'order' => 8  ],
        'linkedin'  => [ 'label' => 'LinkedIn',    'order' => 9  ],
        'email'     => [ 'label' => 'Email',       'order' => 10 ],
        'youtube'   => [ 'label' => 'YouTube',     'order' => 11 ],
    ];

    foreach ( $items as $key => &$item ) {
        $item['url']     = '';
        $item['enabled'] = 0;
        $item['icon_url'] = $icons_url . $key . '.svg';
        $item['svg']     = file_get_contents( $icons_dir . $key . '.svg' ) ?: '';
    }

    return $items;
}

/* ── DB setup ── */

function obrisowi_install_db() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( "CREATE TABLE {$wpdb->prefix}obrisowi_stats (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  type varchar(10) NOT NULL,
  messenger varchar(50) DEFAULT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY type_created (type,created_at)
) {$charset};" );
    update_option( 'obrisowi_db_version', OBRISOWI_DB_VERSION );
    if ( false === get_option( OBRISOWI_OPT_GENERAL ) ) {
        $locale    = get_locale();
        $auto_lang = ( 0 === strpos( $locale, 'uk' ) ) ? 'uk'
                   : ( ( 0 === strpos( $locale, 'ru' ) ) ? 'ru' : 'en' );
        add_option( OBRISOWI_OPT_GENERAL, [ 'language' => $auto_lang ] );
    }
    if ( false === get_option( OBRISOWI_OPT_MESSENGERS ) ) add_option( OBRISOWI_OPT_MESSENGERS, [
        [ 'key' => 'telegram', 'label' => 'Telegram', 'url' => '' ],
    ] );
}

// Runs on every load — creates/upgrades the table when version changes
add_action( 'plugins_loaded', function () {
    if ( get_option( 'obrisowi_db_version' ) !== OBRISOWI_DB_VERSION ) {
        obrisowi_install_db();
    }
} );

register_activation_hook( __FILE__, 'obrisowi_install_db' );
