<?php
/**
 * Plugin Name: KO - WooCommerce User Orders Admin
 * Description: Adds WooCommerce order details to user profile screens and helpful order-related columns to the Users admin list.
 * Version: 1.2.0
 * Author: KO
 * License: GPL v2 or later
 * Text Domain: ko-wc-user-orders-admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'KO_WC_User_Orders_Admin' ) ) {

    final class KO_WC_User_Orders_Admin {

        const VERSION    = '1.2.0';
        const OPTION_KEY = 'ko_wc_user_orders_admin_settings';

        public static function init() {
            add_action( 'show_user_profile', array( __CLASS__, 'render_profile_orders_section' ) );
            add_action( 'edit_user_profile', array( __CLASS__, 'render_profile_orders_section' ) );

            add_filter( 'manage_users_columns', array( __CLASS__, 'add_users_columns' ) );
            add_filter( 'manage_users_custom_column', array( __CLASS__, 'render_users_column' ), 10, 3 );
            add_filter( 'manage_users_sortable_columns', array( __CLASS__, 'make_users_columns_sortable' ) );
            add_action( 'pre_get_users', array( __CLASS__, 'handle_users_sorting' ) );
            add_action( 'pre_user_query', array( __CLASS__, 'handle_pre_user_query_sorting' ) );

            add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
            add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );

            add_filter( 'plugin_row_meta', array( __CLASS__, 'filter_plugin_row_meta' ), 10, 2 );
        }

        public static function get_settings() {
            $defaults = array(
                'column_mode'         => 'count',
                'profile_order_limit' => 20,
            );

            $settings = get_option( self::OPTION_KEY, array() );
            $settings = is_array( $settings ) ? $settings : array();

            return wp_parse_args( $settings, $defaults );
        }

        public static function get_column_mode() {
            $settings = self::get_settings();

            return in_array( $settings['column_mode'], array( 'count', 'yesno' ), true ) ? $settings['column_mode'] : 'count';
        }

        public static function get_profile_order_limit() {
            $settings = self::get_settings();
            $limit    = isset( $settings['profile_order_limit'] ) ? absint( $settings['profile_order_limit'] ) : 20;

            return max( 1, $limit );
        }

        public static function register_admin_menu() {
            add_management_page(
                __( 'WooCommerce User Orders', 'ko-wc-user-orders-admin' ),
                __( 'User Order Admin', 'ko-wc-user-orders-admin' ),
                'manage_options',
                'ko-wc-user-orders-admin',
                array( __CLASS__, 'render_settings_page' )
            );
        }

        public static function register_settings() {
            register_setting(
                'ko_wc_user_orders_admin_group',
                self::OPTION_KEY,
                array(
                    'type'              => 'array',
                    'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
                    'default'           => self::get_settings(),
                )
            );

            add_settings_section(
                'ko_wc_user_orders_admin_section',
                __( 'Display Settings', 'ko-wc-user-orders-admin' ),
                array( __CLASS__, 'render_settings_section_text' ),
                'ko-wc-user-orders-admin'
            );

            add_settings_field(
                'column_mode',
                __( 'Users Column Display', 'ko-wc-user-orders-admin' ),
                array( __CLASS__, 'render_column_mode_field' ),
                'ko-wc-user-orders-admin',
                'ko_wc_user_orders_admin_section'
            );

            add_settings_field(
                'profile_order_limit',
                __( 'Profile Orders Limit', 'ko-wc-user-orders-admin' ),
                array( __CLASS__, 'render_profile_order_limit_field' ),
                'ko-wc-user-orders-admin',
                'ko_wc_user_orders_admin_section'
            );
        }

        public static function sanitize_settings( $input ) {
            $input    = is_array( $input ) ? $input : array();
            $settings = self::get_settings();

            $settings['column_mode'] = ( isset( $input['column_mode'] ) && 'yesno' === $input['column_mode'] ) ? 'yesno' : 'count';

            $limit                           = isset( $input['profile_order_limit'] ) ? absint( $input['profile_order_limit'] ) : 20;
            $settings['profile_order_limit'] = max( 1, $limit );

            wp_cache_flush();

            return $settings;
        }

        public static function render_settings_section_text() {
            echo '<p>' . esc_html__( 'Choose how the Orders column displays on the Users screen and how many recent orders appear on each user profile.', 'ko-wc-user-orders-admin' ) . '</p>';
        }

        public static function render_column_mode_field() {
            $settings = self::get_settings();
            ?>
            <fieldset>
                <label>
                    <input type="radio" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[column_mode]" value="count" <?php checked( $settings['column_mode'], 'count' ); ?> />
                    <?php esc_html_e( 'Show total order count', 'ko-wc-user-orders-admin' ); ?>
                </label>
                <br />
                <label>
                    <input type="radio" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[column_mode]" value="yesno" <?php checked( $settings['column_mode'], 'yesno' ); ?> />
                    <?php esc_html_e( 'Show Yes / No only', 'ko-wc-user-orders-admin' ); ?>
                </label>
            </fieldset>
            <?php
        }

        public static function render_profile_order_limit_field() {
            $settings = self::get_settings();
            ?>
            <input
                type="number"
                min="1"
                step="1"
                class="small-text"
                name="<?php echo esc_attr( self::OPTION_KEY ); ?>[profile_order_limit]"
                value="<?php echo esc_attr( absint( $settings['profile_order_limit'] ) ); ?>"
            />
            <p class="description"><?php esc_html_e( 'How many recent orders to show on the user profile screen.', 'ko-wc-user-orders-admin' ); ?></p>
            <?php
        }

        public static function render_settings_page() {
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }
            ?>
            <div class="wrap">
                <h1><?php esc_html_e( 'KO - WooCommerce User Orders Admin', 'ko-wc-user-orders-admin' ); ?></h1>
                <form method="post" action="options.php">
                    <?php
                    settings_fields( 'ko_wc_user_orders_admin_group' );
                    do_settings_sections( 'ko-wc-user-orders-admin' );
                    submit_button();
                    ?>
                </form>
            </div>
            <?php
        }

        public static function render_profile_orders_section( $user ) {
            if ( ! class_exists( 'WooCommerce' ) ) {
                return;
            }

            if ( ! current_user_can( 'edit_users' ) && ! current_user_can( 'manage_woocommerce' ) ) {
                return;
            }

            $user_id = isset( $user->ID ) ? (int) $user->ID : 0;

            if ( $user_id <= 0 ) {
                return;
            }

            $orders = wc_get_orders(
                array(
                    'limit'    => self::get_profile_order_limit(),
                    'customer' => $user_id,
                    'orderby'  => 'date',
                    'order'    => 'DESC',
                    'return'   => 'objects',
                    'status'   => array_keys( wc_get_order_statuses() ),
                )
            );
            ?>
            <h2><?php esc_html_e( 'WooCommerce Orders', 'ko-wc-user-orders-admin' ); ?></h2>

            <table class="form-table" role="presentation">
                <tr>
                    <th><label><?php esc_html_e( 'User Orders', 'ko-wc-user-orders-admin' ); ?></label></th>
                    <td>
                        <?php if ( empty( $orders ) ) : ?>
                            <p><?php esc_html_e( 'No orders found for this user account.', 'ko-wc-user-orders-admin' ); ?></p>
                        <?php else : ?>
                            <table class="widefat striped" style="max-width: 1100px; width: 100%;">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e( 'Order', 'ko-wc-user-orders-admin' ); ?></th>
                                        <th><?php esc_html_e( 'Date', 'ko-wc-user-orders-admin' ); ?></th>
                                        <th><?php esc_html_e( 'Status', 'ko-wc-user-orders-admin' ); ?></th>
                                        <th><?php esc_html_e( 'Total', 'ko-wc-user-orders-admin' ); ?></th>
                                        <th><?php esc_html_e( 'Items', 'ko-wc-user-orders-admin' ); ?></th>
                                        <th><?php esc_html_e( 'View', 'ko-wc-user-orders-admin' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $orders as $order ) : ?>
                                        <?php
                                        $order_id     = $order->get_id();
                                        $order_number = $order->get_order_number();
                                        $order_date   = $order->get_date_created();
                                        $status       = wc_get_order_status_name( $order->get_status() );
                                        $total        = $order->get_formatted_order_total();
                                        $item_count   = $order->get_item_count();
                                        $edit_link    = admin_url( 'post.php?post=' . absint( $order_id ) . '&action=edit' );
                                        ?>
                                        <tr>
                                            <td>#<?php echo esc_html( $order_number ); ?></td>
                                            <td>
                                                <?php
                                                echo $order_date
                                                    ? esc_html( $order_date->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) )
                                                    : '&mdash;';
                                                ?>
                                            </td>
                                            <td><?php echo esc_html( $status ); ?></td>
                                            <td><?php echo wp_kses_post( $total ); ?></td>
                                            <td><?php echo esc_html( $item_count ); ?></td>
                                            <td><a href="<?php echo esc_url( $edit_link ); ?>"><?php esc_html_e( 'View Order', 'ko-wc-user-orders-admin' ); ?></a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <?php
        }

        public static function add_users_columns( $columns ) {
            $columns['ko_wc_orders']     = __( 'Orders', 'ko-wc-user-orders-admin' );
            $columns['ko_wc_revenue']    = __( 'Revenue', 'ko-wc-user-orders-admin' );
            $columns['ko_wc_last_order'] = __( 'Last Order', 'ko-wc-user-orders-admin' );

            return $columns;
        }

        public static function render_users_column( $value, $column_name, $user_id ) {
            if ( ! in_array( $column_name, array( 'ko_wc_orders', 'ko_wc_revenue', 'ko_wc_last_order' ), true ) ) {
                return $value;
            }

            if ( ! class_exists( 'WooCommerce' ) ) {
                return '&mdash;';
            }

            $stats = self::get_customer_order_stats( $user_id );

            if ( 'ko_wc_orders' === $column_name ) {
                return self::render_orders_column_value( $user_id, $stats );
            }

            if ( 'ko_wc_revenue' === $column_name ) {
                return $stats['count'] > 0 ? wp_kses_post( wc_price( $stats['revenue'] ) ) : '&mdash;';
            }

            if ( empty( $stats['last_order_gmt'] ) ) {
                return '&mdash;';
            }

            $timestamp = mysql2date( 'U', $stats['last_order_gmt'], false );

            return $timestamp
                ? esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp ) )
                : '&mdash;';
        }

        private static function render_orders_column_value( $user_id, $stats ) {
            $count     = isset( $stats['count'] ) ? absint( $stats['count'] ) : 0;
            $mode      = self::get_column_mode();
            $orders_url = self::get_user_orders_admin_url( $user_id );

            if ( 'yesno' === $mode ) {
                if ( $count > 0 ) {
                    return '<a href="' . esc_url( $orders_url ) . '">' . esc_html__( 'Yes', 'ko-wc-user-orders-admin' ) . '</a>';
                }

                return esc_html__( 'No', 'ko-wc-user-orders-admin' );
            }

            if ( $count > 0 ) {
                return '<a href="' . esc_url( $orders_url ) . '">' . esc_html( (string) $count ) . '</a>';
            }

            return '0';
        }

        public static function get_customer_order_count( $user_id ) {
            $stats = self::get_customer_order_stats( $user_id );
            return isset( $stats['count'] ) ? absint( $stats['count'] ) : 0;
        }

        public static function get_customer_order_stats( $user_id ) {
            $user_id = absint( $user_id );

            if ( $user_id <= 0 ) {
                return self::get_empty_stats();
            }

            $cache_key = 'ko_wc_order_stats_' . $user_id;
            $stats     = wp_cache_get( $cache_key, 'users' );

            if ( false !== $stats && is_array( $stats ) ) {
                return wp_parse_args( $stats, self::get_empty_stats() );
            }

            global $wpdb;

            $statuses = array_keys( wc_get_order_statuses() );
            $statuses = array_filter( array_map( 'wc_clean', $statuses ) );

            if ( empty( $statuses ) ) {
                return self::get_empty_stats();
            }

            if ( self::is_hpos_enabled() ) {
                $orders_table = $wpdb->prefix . 'wc_orders';
                $placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
                $sql          = "SELECT COUNT(id) AS order_count, COALESCE(SUM(total_amount), 0) AS total_revenue, MAX(date_created_gmt) AS last_order_gmt FROM {$orders_table} WHERE customer_id = %d AND type = 'shop_order' AND status IN ({$placeholders})";
                $prepare_args = array_merge( array( $sql, $user_id ), $statuses );
                $row          = $wpdb->get_row( call_user_func_array( array( $wpdb, 'prepare' ), $prepare_args ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            } else {
                $posts_table    = $wpdb->posts;
                $postmeta_table = $wpdb->postmeta;
                $placeholders   = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
                $sql            = "SELECT COUNT(DISTINCT p.ID) AS order_count, COALESCE(SUM(CAST(pm_total.meta_value AS DECIMAL(26,8))), 0) AS total_revenue, MAX(p.post_date_gmt) AS last_order_gmt
                    FROM {$posts_table} p
                    INNER JOIN {$postmeta_table} pm_customer ON p.ID = pm_customer.post_id
                    LEFT JOIN {$postmeta_table} pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = '_order_total'
                    WHERE pm_customer.meta_key = '_customer_user'
                    AND pm_customer.meta_value = %d
                    AND p.post_type = 'shop_order'
                    AND p.post_status IN ({$placeholders})";
                $prepare_args   = array_merge( array( $sql, $user_id ), $statuses );
                $row            = $wpdb->get_row( call_user_func_array( array( $wpdb, 'prepare' ), $prepare_args ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            }

            $stats = array(
                'count'          => isset( $row['order_count'] ) ? (int) $row['order_count'] : 0,
                'revenue'        => isset( $row['total_revenue'] ) ? (float) $row['total_revenue'] : 0,
                'last_order_gmt' => ! empty( $row['last_order_gmt'] ) ? (string) $row['last_order_gmt'] : '',
            );

            wp_cache_set( $cache_key, $stats, 'users', 10 * MINUTE_IN_SECONDS );

            return $stats;
        }

        public static function make_users_columns_sortable( $columns ) {
            $columns['ko_wc_orders']     = 'ko_wc_orders';
            $columns['ko_wc_revenue']    = 'ko_wc_revenue';
            $columns['ko_wc_last_order'] = 'ko_wc_last_order';

            return $columns;
        }

        public static function handle_users_sorting( $query ) {
            global $pagenow;

            if ( ! is_admin() || 'users.php' !== $pagenow ) {
                return;
            }

            if ( ! ( $query instanceof WP_User_Query ) ) {
                return;
            }

            $orderby = $query->get( 'orderby' );

            if ( ! in_array( $orderby, array( 'ko_wc_orders', 'ko_wc_revenue', 'ko_wc_last_order' ), true ) ) {
                return;
            }

            $order = strtoupper( (string) $query->get( 'order' ) );
            $order = in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'DESC';

            $query->set( 'orderby', $orderby );
            $query->set( 'order', $order );
        }

        public static function handle_pre_user_query_sorting( $query ) {
            global $pagenow, $wpdb;

            if ( ! is_admin() || 'users.php' !== $pagenow ) {
                return;
            }

            if ( ! ( $query instanceof WP_User_Query ) ) {
                return;
            }

            $orderby = $query->get( 'orderby' );

            if ( ! in_array( $orderby, array( 'ko_wc_orders', 'ko_wc_revenue', 'ko_wc_last_order' ), true ) ) {
                return;
            }

            $order = strtoupper( (string) $query->get( 'order' ) );
            $order = in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'DESC';

            $statuses = array_keys( wc_get_order_statuses() );
            $statuses = array_filter( array_map( 'wc_clean', $statuses ) );

            if ( empty( $statuses ) ) {
                $query->query_orderby = "ORDER BY {$wpdb->users}.user_login ASC";
                return;
            }

            $status_list_sql = "'" . implode( "','", array_map( 'esc_sql', $statuses ) ) . "'";

            if ( self::is_hpos_enabled() ) {
                $orders_table = $wpdb->prefix . 'wc_orders';

                if ( false === strpos( $query->query_from, 'ko_wc_order_metrics' ) ) {
                    $query->query_from .= "
LEFT JOIN (
    SELECT customer_id AS ko_user_id,
           COUNT(id) AS ko_order_count,
           COALESCE(SUM(total_amount), 0) AS ko_order_revenue,
           MAX(date_created_gmt) AS ko_last_order_gmt
    FROM {$orders_table}
    WHERE type = 'shop_order'
      AND status IN ({$status_list_sql})
    GROUP BY customer_id
) AS ko_wc_order_metrics ON {$wpdb->users}.ID = ko_wc_order_metrics.ko_user_id
";
                }
            } else {
                if ( false === strpos( $query->query_from, 'ko_wc_order_metrics' ) ) {
                    $query->query_from .= "
LEFT JOIN (
    SELECT CAST(pm_customer.meta_value AS UNSIGNED) AS ko_user_id,
           COUNT(DISTINCT p.ID) AS ko_order_count,
           COALESCE(SUM(CAST(pm_total.meta_value AS DECIMAL(26,8))), 0) AS ko_order_revenue,
           MAX(p.post_date_gmt) AS ko_last_order_gmt
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm_customer ON p.ID = pm_customer.post_id AND pm_customer.meta_key = '_customer_user'
    LEFT JOIN {$wpdb->postmeta} pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = '_order_total'
    WHERE p.post_type = 'shop_order'
      AND p.post_status IN ({$status_list_sql})
    GROUP BY CAST(pm_customer.meta_value AS UNSIGNED)
) AS ko_wc_order_metrics ON {$wpdb->users}.ID = ko_wc_order_metrics.ko_user_id
";
                }
            }

            switch ( $orderby ) {
                case 'ko_wc_revenue':
                    $query->query_orderby = "ORDER BY COALESCE(ko_wc_order_metrics.ko_order_revenue, 0) {$order}, {$wpdb->users}.user_login ASC";
                    break;
                case 'ko_wc_last_order':
                    $query->query_orderby = "ORDER BY COALESCE(ko_wc_order_metrics.ko_last_order_gmt, '0000-00-00 00:00:00') {$order}, {$wpdb->users}.user_login ASC";
                    break;
                case 'ko_wc_orders':
                default:
                    $query->query_orderby = "ORDER BY COALESCE(ko_wc_order_metrics.ko_order_count, 0) {$order}, {$wpdb->users}.user_login ASC";
                    break;
            }
        }

        public static function filter_plugin_row_meta( $links, $file ) {
            if ( plugin_basename( __FILE__ ) !== $file ) {
                return $links;
            }

            $filtered_links = array();

            foreach ( $links as $link ) {
                if ( false !== stripos( wp_strip_all_tags( $link ), 'Visit plugin site' ) ) {
                    continue;
                }

                $filtered_links[] = $link;
            }

            $filtered_links[] = '<a href="mailto:6822858@kevinoneill.us">' . esc_html__( 'Contact KO', 'ko-wc-user-orders-admin' ) . '</a>';

            return $filtered_links;
        }

        private static function get_user_orders_admin_url( $user_id ) {
            $user_id = absint( $user_id );
            $user    = get_user_by( 'id', $user_id );
            $email   = ( $user && ! empty( $user->user_email ) ) ? $user->user_email : '';

            if ( self::is_hpos_enabled() ) {
                $args = array(
                    'page'          => 'wc-orders',
                    'customer'      => $user_id,
                    '_customer_user' => $user_id,
                );

                if ( $email ) {
                    $args['s'] = $email;
                }

                return add_query_arg( $args, admin_url( 'admin.php' ) );
            }

            $args = array(
                'post_type'       => 'shop_order',
                '_customer_user'  => $user_id,
            );

            if ( $email ) {
                $args['s'] = $email;
            }

            return add_query_arg( $args, admin_url( 'edit.php' ) );
        }

        private static function get_empty_stats() {
            return array(
                'count'          => 0,
                'revenue'        => 0,
                'last_order_gmt' => '',
            );
        }

        private static function is_hpos_enabled() {
            if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\OrderUtil' ) ) {
                return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
            }

            return false;
        }
    }

    KO_WC_User_Orders_Admin::init();
}
