<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function sw_page_dashboard() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    global $wpdb;
    $table = $wpdb->prefix . 'obrisowi_stats'; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table uses $wpdb->prefix only, safe

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- stats table query, caching not appropriate for real-time data
    if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
        echo '<div class="wrap"><h1>' . esc_html( obrisowi_t( 'admin.dashboard_title' ) ) . '</h1>';
        echo '<div class="notice notice-error"><p>' . esc_html( obrisowi_t( 'admin.stats_table_missing' ) ) . '</p></div></div>';
        return;
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $total_views = wp_cache_get( 'obrisowi_total_views' );
    if ( false === $total_views ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is built from $wpdb->prefix only, safe
        $total_views = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE type = %s", 'view' ) );
        wp_cache_set( 'obrisowi_total_views', $total_views, '', 300 );
    }
    $total_views = (int) $total_views;

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $total_clicks = wp_cache_get( 'obrisowi_total_clicks' );
    if ( false === $total_clicks ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is built from $wpdb->prefix only, safe
        $total_clicks = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE type = %s", 'click' ) );
        wp_cache_set( 'obrisowi_total_clicks', $total_clicks, '', 300 );
    }
    $total_clicks = (int) $total_clicks;

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $clicks_by_messenger = wp_cache_get( 'obrisowi_clicks_by_messenger' );
    if ( false === $clicks_by_messenger ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is built from $wpdb->prefix only, safe
        $clicks_by_messenger = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table uses $wpdb->prefix only, safe
            $wpdb->prepare(
                "SELECT messenger, COUNT(*) as cnt FROM `{$table}`
                 WHERE type = %s AND messenger IS NOT NULL AND messenger != ''
                 GROUP BY messenger ORDER BY cnt DESC",
                'click'
            ),
            ARRAY_A
        );
        wp_cache_set( 'obrisowi_clicks_by_messenger', $clicks_by_messenger, '', 300 );
    }

    $since = gmdate( 'Y-m-d', strtotime( '-29 days' ) ) . ' 00:00:00';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $daily_rows = wp_cache_get( 'obrisowi_daily_rows' );
    if ( false === $daily_rows ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is built from $wpdb->prefix only, safe
        $daily_rows = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table uses $wpdb->prefix only, safe
            $wpdb->prepare(
                "SELECT DATE(created_at) as day, type, COUNT(*) as cnt
                 FROM `{$table}` WHERE created_at >= %s
                 GROUP BY DATE(created_at), type ORDER BY day ASC",
                $since
            ),
            ARRAY_A
        );
        wp_cache_set( 'obrisowi_daily_rows', $daily_rows, '', 300 );
    }

    $chart_data = [];
    for ( $i = 29; $i >= 0; $i-- ) {
        $chart_data[ gmdate( 'Y-m-d', strtotime( "-{$i} days" ) ) ] = [ 'views' => 0, 'clicks' => 0 ];
    }
    foreach ( $daily_rows as $row ) {
        if ( isset( $chart_data[ $row['day'] ] ) ) {
            $key = $row['type'] === 'view' ? 'views' : 'clicks';
            $chart_data[ $row['day'] ][ $key ] = (int) $row['cnt'];
        }
    }

    $max_val = 0;
    foreach ( $chart_data as $d ) {
        $max_val = max( $max_val, $d['views'], $d['clicks'] );
    }

    $chart_max_px = 100;
    $messengers_config = obrisowi_default_messengers();
    ?>
    <div class="wrap sw-admin sw-dashboard">
        <h1><?php echo esc_html( obrisowi_t( 'admin.dashboard_title' ) ); ?></h1>

        <div class="sw-stats-cards">
            <div class="sw-stat-card">
                <div class="sw-stat-value"><?php echo number_format( $total_views ); ?></div>
                <div class="sw-stat-label"><?php echo esc_html( obrisowi_t( 'admin.total_views' ) ); ?></div>
            </div>
            <div class="sw-stat-card">
                <div class="sw-stat-value"><?php echo number_format( $total_clicks ); ?></div>
                <div class="sw-stat-label"><?php echo esc_html( obrisowi_t( 'admin.total_clicks' ) ); ?></div>
            </div>
        </div>

        <div class="sw-card">
            <h2><?php echo esc_html( obrisowi_t( 'admin.last_30_days' ) ); ?></h2>
            <?php if ( $max_val === 0 ) : ?>
                <p class="sw-no-data"><?php echo esc_html( obrisowi_t( 'admin.no_data' ) ); ?></p>
            <?php else : ?>
            <div class="sw-chart-legend">
                <span class="sw-leg sw-leg-v"><?php echo esc_html( obrisowi_t( 'admin.views' ) ); ?></span>
                <span class="sw-leg sw-leg-c"><?php echo esc_html( obrisowi_t( 'admin.clicks' ) ); ?></span>
            </div>
            <div class="sw-chart-wrap">
                <div class="sw-chart-bars">
                    <?php foreach ( $chart_data as $day => $vals ) :
                        $v_px = (int) round( $vals['views']  / $max_val * $chart_max_px );
                        $c_px = (int) round( $vals['clicks'] / $max_val * $chart_max_px );
                    ?>
                    <div class="sw-chart-col">
                        <div class="sw-bar-pair">
                            <div class="sw-bar sw-bar-v" style="height:<?php echo absint( $v_px ); ?>px" title="<?php echo esc_attr( obrisowi_t( 'admin.views' ) . ': ' . $vals['views'] ); ?>"></div>
                            <div class="sw-bar sw-bar-c" style="height:<?php echo absint( $c_px ); ?>px" title="<?php echo esc_attr( obrisowi_t( 'admin.clicks' ) . ': ' . $vals['clicks'] ); ?>"></div>
                        </div>
                        <span class="sw-bar-label"><?php echo esc_html( gmdate( 'd.m', strtotime( $day ) ) ); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php if ( ! empty( $clicks_by_messenger ) ) : ?>
        <div class="sw-card">
            <h2><?php echo esc_html( obrisowi_t( 'admin.clicks_by_messenger' ) ); ?></h2>
            <div class="sw-ms-stats">
                <?php
                $max_cnt = max( array_column( $clicks_by_messenger, 'cnt' ) );
                $allowed_img = [
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
                foreach ( $clicks_by_messenger as $row ) :
                    $ms_key = $row['messenger'];
                    $label  = $messengers_config[ $ms_key ]['label'] ?? $ms_key;
                    $icon   = obrisowi_get_messenger_icon_html( $messengers_config[ $ms_key ] ?? [], 'sw-dashboard-icon' );
                    $pct    = $max_cnt > 0 ? (int) round( $row['cnt'] / $max_cnt * 100 ) : 0;
                ?>
                <div class="sw-ms-row">
                    <div class="sw-ms-icon"><?php echo wp_kses( $icon, $allowed_img ); ?></div>
                    <div class="sw-ms-name"><?php echo esc_html( $label ); ?></div>
                    <div class="sw-ms-bar-wrap"><div class="sw-ms-bar" style="width:<?php echo absint( $pct ); ?>%"></div></div>
                    <div class="sw-ms-count"><?php echo (int) $row['cnt']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php
}
