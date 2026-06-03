<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function obrisowi_page_settings() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    if ( isset( $_POST['obrisowi_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['obrisowi_settings_nonce'] ) ), 'obrisowi_save_settings' ) ) {
        update_option( OBRISOWI_OPT_GENERAL, [
            'enabled'           => ! empty( $_POST['enabled'] ) ? 1 : 0,
            'position'          => in_array( wp_unslash( $_POST['position'] ?? '' ), [ 'left', 'right' ] ) ? sanitize_key( wp_unslash( $_POST['position'] ) ) : 'right',
            'offset_side'       => intval( $_POST['offset_side'] ?? 20 ),
            'offset_bottom'     => intval( $_POST['offset_bottom'] ?? 20 ),
            'show_labels'       => ! empty( $_POST['show_labels'] ) ? 1 : 0,
            'carousel_interval' => max( 0.5, min( 10, floatval( str_replace( ',', '.', sanitize_text_field( wp_unslash( $_POST['carousel_interval'] ?? '1.5' ) ) ) ) ) ),
            'animation'         => in_array( wp_unslash( $_POST['animation'] ?? '' ), [ 'fade', 'slide' ] ) ? sanitize_key( wp_unslash( $_POST['animation'] ) ) : 'fade',
            'bubble_enabled'    => ! empty( $_POST['bubble_enabled'] ) ? 1 : 0,
            'bubble_text'       => sanitize_text_field( wp_unslash( $_POST['bubble_text'] ?? '' ) ),
            'bubble_font_size'  => max( 10, min( 40, intval( $_POST['bubble_font_size'] ?? 15 ) ) ),
            'bubble_delay'      => max( 0, floatval( str_replace( ',', '.', sanitize_text_field( wp_unslash( $_POST['bubble_delay'] ?? '3' ) ) ) ) ),
            'language'          => in_array( wp_unslash( $_POST['language'] ?? '' ), [ 'en', 'uk', 'ru' ] ) ? sanitize_key( wp_unslash( $_POST['language'] ) ) : 'en',
        ] );
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( obrisowi_t( 'admin.settings_saved' ) ) . '</p></div>';
    }

    $g = obrisowi_get_general();
    ?>
    <div class="wrap sw-admin">
        <h1><?php echo esc_html( obrisowi_t( 'admin.settings_title' ) ); ?></h1>
        <form method="post">
            <?php wp_nonce_field( 'obrisowi_save_settings', 'obrisowi_settings_nonce' ); ?>

            <div class="sw-card">
                <h2><?php echo esc_html( obrisowi_t( 'admin.section_widget' ) ); ?></h2>
                <table class="form-table">
                    <tr><th><?php echo esc_html( obrisowi_t( 'admin.enable_plugin' ) ); ?></th><td>
                        <label class="sw-toggle-label">
                            <input type="checkbox" name="enabled" value="1" <?php checked( $g['enabled'], 1 ); ?>>
                            <span class="sw-toggle-switch"></span>
                            <span class="sw-toggle-text"><?php echo esc_html( obrisowi_t( 'admin.show_widget_text' ) ); ?></span>
                        </label>
                    </td></tr>
                    <tr><th><?php echo esc_html( obrisowi_t( 'admin.position' ) ); ?></th><td>
                        <label><input type="radio" name="position" value="left"  <?php checked( $g['position'], 'left' );  ?>> <?php echo esc_html( obrisowi_t( 'admin.position_left' ) ); ?></label>
                        <label><input type="radio" name="position" value="right" <?php checked( $g['position'], 'right' ); ?>> <?php echo esc_html( obrisowi_t( 'admin.position_right' ) ); ?></label> &nbsp;
                    </td></tr>
                    <tr><th><?php echo esc_html( obrisowi_t( 'admin.offset_side' ) ); ?></th><td>
                        <input type="number" name="offset_side" value="<?php echo esc_attr( $g['offset_side'] ); ?>" min="0" max="200" class="small-text"> px
                    </td></tr>
                    <tr><th><?php echo esc_html( obrisowi_t( 'admin.offset_bottom' ) ); ?></th><td>
                        <input type="number" name="offset_bottom" value="<?php echo esc_attr( $g['offset_bottom'] ); ?>" min="0" max="200" class="small-text"> px
                    </td></tr>
                    <tr><th><?php echo esc_html( obrisowi_t( 'admin.show_labels' ) ); ?></th><td>
                        <label class="sw-toggle-label">
                            <input type="checkbox" name="show_labels" value="1" <?php checked( $g['show_labels'], 1 ); ?>>
                            <span class="sw-toggle-switch"></span>
                            <span class="sw-toggle-text"><?php echo esc_html( obrisowi_t( 'admin.show_labels_text' ) ); ?></span>
                        </label>
                    </td></tr>
                </table>
            </div>

            <div class="sw-card">
                <h2><?php echo esc_html( obrisowi_t( 'admin.section_carousel' ) ); ?></h2>
                <table class="form-table">
                    <tr><th><?php echo esc_html( obrisowi_t( 'admin.icon_display_time' ) ); ?></th><td>
                        <input type="number" name="carousel_interval" value="<?php echo esc_attr( $g['carousel_interval'] ); ?>" min="0.5" max="10" step="0.5" class="small-text"> sec
                        <p class="description"><?php echo esc_html( obrisowi_t( 'admin.icon_display_default' ) ); ?></p>
                    </td></tr>
                    <tr><th><?php echo esc_html( obrisowi_t( 'admin.transition_animation' ) ); ?></th><td>
                        <select name="animation">
                            <option value="fade"  <?php selected( $g['animation'], 'fade' );  ?>><?php echo esc_html( obrisowi_t( 'admin.anim_fade' ) ); ?></option>
                            <option value="slide" <?php selected( $g['animation'], 'slide' ); ?>><?php echo esc_html( obrisowi_t( 'admin.anim_slide' ) ); ?></option>
                        </select>
                    </td></tr>
                </table>
            </div>

            <div class="sw-card">
                <h2><?php echo esc_html( obrisowi_t( 'admin.section_bubble' ) ); ?></h2>
                <table class="form-table">
                    <tr><th><?php echo esc_html( obrisowi_t( 'admin.enable_bubble' ) ); ?></th><td>
                        <label class="sw-toggle-label">
                            <input type="checkbox" name="bubble_enabled" value="1" <?php checked( $g['bubble_enabled'], 1 ); ?>>
                            <span class="sw-toggle-switch"></span>
                            <span class="sw-toggle-text"><?php echo esc_html( obrisowi_t( 'admin.show_bubble_text' ) ); ?></span>
                        </label>
                    </td></tr>
                    <tr><th><?php echo esc_html( obrisowi_t( 'admin.bubble_text' ) ); ?></th><td>
                        <input type="text" name="bubble_text" value="<?php echo esc_attr( $g['bubble_text'] ); ?>"
                               placeholder="<?php echo esc_attr( obrisowi_t( 'frontend.bubble_default' ) ); ?>"
                               class="regular-text">
                    </td></tr>
                    <tr><th><?php echo esc_html( obrisowi_t( 'admin.bubble_font_size' ) ); ?></th><td>
                        <input type="number" name="bubble_font_size" value="<?php echo esc_attr( $g['bubble_font_size'] ); ?>" min="10" max="40" class="small-text"> px
                    </td></tr>
                    <tr><th><?php echo esc_html( obrisowi_t( 'admin.appear_delay' ) ); ?></th><td>
                        <input type="number" name="bubble_delay" value="<?php echo esc_attr( $g['bubble_delay'] ); ?>" min="0" max="60" step="0.5" class="small-text"> <?php echo esc_html( obrisowi_t( 'admin.appear_delay_suffix' ) ); ?>
                    </td></tr>
                </table>
            </div>

            <div class="sw-card">
                <h2><?php echo esc_html( obrisowi_t( 'admin.section_language' ) ); ?></h2>
                <table class="form-table">
                    <tr><th><?php echo esc_html( obrisowi_t( 'admin.language' ) ); ?></th><td>
                        <select name="language">
                            <option value="en" <?php selected( $g['language'], 'en' ); ?>>English</option>
                            <option value="uk" <?php selected( $g['language'], 'uk' ); ?>>Українська</option>
                            <option value="ru" <?php selected( $g['language'], 'ru' ); ?>>Русский</option>
                        </select>
                    </td></tr>
                </table>
            </div>

            <?php submit_button( obrisowi_t( 'admin.save_settings' ) ); ?>
        </form>
    </div>
    <?php
}
