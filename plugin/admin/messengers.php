<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function obrisowi_page_messengers() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    if ( isset( $_POST['obrisowi_messengers_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['obrisowi_messengers_nonce'] ) ), 'obrisowi_save_messengers' ) ) {
        $defaults = obrisowi_default_messengers();
        $raw    = isset( $_POST['messengers'] ) ? wp_unslash( (array) $_POST['messengers'] ) : [];
        $posted = array_map( function( $entry ) {
            return is_array( $entry ) ? array_map( 'sanitize_text_field', $entry ) : sanitize_text_field( $entry );
        }, $raw );
        $save     = [];
        foreach ( $posted as $entry ) {
            if ( ! is_array( $entry ) ) {
                continue;
            }

            $key   = sanitize_key( $entry['key'] ?? '' );
            $label = sanitize_text_field( $entry['label'] ?? '' );
            $url   = sanitize_text_field( $entry['url'] ?? '' );
            if ( ! $key || ! isset( $defaults[ $key ] ) ) continue;
            $save[] = [
                'key'   => $key,
                'label' => $label !== '' ? $label : $defaults[ $key ]['label'],
                'url'   => $url,
            ];
        }
        update_option( OBRISOWI_OPT_MESSENGERS, $save );
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( obrisowi_t( 'admin.messengers_saved' ) ) . '</p></div>';
    }

    $list     = get_option( OBRISOWI_OPT_MESSENGERS, [] );
    $defaults = obrisowi_default_messengers();

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

    $trash_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>';
    ?>
    <div class="wrap sw-admin">
        <h1><?php echo esc_html( obrisowi_t( 'admin.messengers_title' ) ); ?></h1>
        <p class="description" style="margin-bottom:16px;"><?php echo esc_html( obrisowi_t( 'admin.messengers_desc' ) ); ?></p>

        <form method="post" id="sw-form">
            <?php wp_nonce_field( 'obrisowi_save_messengers', 'obrisowi_messengers_nonce' ); ?>
            <div class="sw-card">
                <table class="wp-list-table widefat fixed striped" id="sw-sortable">
                    <thead><tr>
                        <th style="width:32px;"></th>
                        <th style="width:40px;"><?php echo esc_html( obrisowi_t( 'admin.col_icon' ) ); ?></th>
                        <th style="width:170px;"><?php echo esc_html( obrisowi_t( 'admin.col_label' ) ); ?></th>
                        <th><?php echo esc_html( obrisowi_t( 'admin.col_url' ) ); ?></th>
                        <th class="sw-delete-col"></th>
                    </tr></thead>
                    <tbody id="sw-tbody">
                    <?php if ( empty( $list ) ) : ?>
                        <tr id="sw-empty-row"><td colspan="5" class="sw-empty-msg"><?php echo esc_html( obrisowi_t( 'admin.no_messengers' ) ); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ( $list as $i => $entry ) :
                            $key   = $entry['key'] ?? '';
                            if ( ! isset( $defaults[ $key ] ) ) continue;
                            $label = ( $entry['label'] ?? '' ) !== '' ? $entry['label'] : $defaults[ $key ]['label'];
                            $url   = $entry['url'] ?? '';
                            $ph    = $placeholders[ $key ] ?? '';
                            $allowed_img = [ 'img' => [ 'class' => true, 'src' => true, 'alt' => true, 'loading' => true, 'decoding' => true ] ];
                        ?>
                        <tr class="sw-row" draggable="true">
                            <td class="sw-handle" title="Drag to reorder">&#9776;</td>
                            <td class="sw-icon-cell"><?php echo wp_kses( obrisowi_get_messenger_icon_html( $defaults[ $key ], 'sw-admin-icon' ), $allowed_img ); ?></td>
                            <td>
                                <input type="hidden" name="messengers[<?php echo absint( $i ); ?>][key]" value="<?php echo esc_attr( $key ); ?>">
                                <input type="text"   name="messengers[<?php echo absint( $i ); ?>][label]" value="<?php echo esc_attr( $label ); ?>" class="sw-label-input">
                            </td>
                            <td><input type="text" name="messengers[<?php echo absint( $i ); ?>][url]" value="<?php echo esc_attr( $url ); ?>" placeholder="<?php echo esc_attr( $ph ); ?>" class="sw-url-input"></td>
                            <td class="sw-delete-cell">
                                <button type="button" class="sw-delete-btn" title="<?php echo esc_attr( obrisowi_t( 'admin.delete' ) ); ?>">
                                    <?php
                                    $allowed_svg = [
                                        'svg'      => [ 'xmlns' => true, 'width' => true, 'height' => true, 'viewBox' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true ],
                                        'polyline' => [ 'points' => true ],
                                        'path'     => [ 'd' => true ],
                                    ];
                                    echo wp_kses( $trash_svg, $allowed_svg );
                                    ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php submit_button( obrisowi_t( 'admin.save_messengers' ) ); ?>
        </form>

        <div class="sw-card sw-add-card">
            <h2><?php echo esc_html( obrisowi_t( 'admin.add_messenger' ) ); ?></h2>
            <div class="sw-add-form">
                <div class="sw-add-select-wrap">
                    <img id="sw-add-icon" class="sw-admin-icon" src="" alt="">
                    <select id="sw-add-key">
                        <?php foreach ( $defaults as $key => $m ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $m['label'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="text" id="sw-add-label" style="width:150px;" placeholder="<?php echo esc_attr( obrisowi_t( 'admin.col_label' ) ); ?>">
                <input type="text" id="sw-add-url" style="width:280px;" placeholder="">
                <button type="button" id="sw-add-btn" class="button button-primary"><?php echo esc_html( obrisowi_t( 'admin.add' ) ); ?></button>
            </div>
            <p class="description" style="margin-top:10px;"><?php echo esc_html( obrisowi_t( 'admin.messengers_tip' ) ); ?></p>
        </div>
    </div>
    <?php
}
