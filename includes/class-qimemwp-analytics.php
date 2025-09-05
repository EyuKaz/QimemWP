<?php
/**
 * QimemWP Analytics Class
 *
 * Manages GDPR-compliant tracking of voice interactions, stores data in a custom database table,
 * and provides an admin dashboard for analytics. Compatible with WordPress 6.6+.
 *
 * @package QimemWP
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * QimemWP Analytics
 *
 * Handles voice interaction tracking, data storage, and analytics dashboard.
 *
 * @since 1.0.0
 */
class QimemWP_Analytics {

    /**
     * Initialize the analytics functionality.
     */
    public static function init() {
        $instance = new self();
        $instance->register_hooks();
        $instance->create_table();
    }

    /**
     * Register hooks for analytics functionality.
     */
    private function register_hooks() {
        // Track voice commands
        add_action( 'qimemwp_command_processed', array( $this, 'track_command' ), 10, 2 );

        // Add analytics dashboard to admin menu
        add_action( 'admin_menu', array( $this, 'add_analytics_page' ) );

        // Handle CSV export
        add_action( 'admin_post_qimemwp_export_analytics', array( $this, 'export_analytics_csv' ) );

        // Schedule daily cleanup of old data
        if ( ! wp_next_scheduled( 'qimemwp_cleanup_analytics' ) ) {
            wp_schedule_event( time(), 'daily', 'qimemwp_cleanup_analytics' );
        }
        add_action( 'qimemwp_cleanup_analytics', array( $this, 'cleanup_old_data' ) );

        // AJAX handler for dashboard data
        add_action( 'wp_ajax_qimemwp_get_analytics', array( $this, 'get_analytics_data' ) );
    }

    /**
     * Create custom database table for analytics.
     */
    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'qimemwp_analytics';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            command VARCHAR(255) NOT NULL,
            action VARCHAR(50) NOT NULL,
            session_id VARCHAR(32) NOT NULL,
            timestamp DATETIME NOT NULL,
            duration FLOAT NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            INDEX idx_timestamp (timestamp)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Track voice command interactions.
     *
     * @param string $command The voice command.
     * @param array  $response The command response.
     */
    public function track_command( $command, $response ) {
        if ( ! $this->is_analytics_enabled() ) {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'qimemwp_analytics';

        $session_id = wp_generate_uuid4();
        $start_time = microtime( true );

        $wpdb->insert(
            $table_name,
            array(
                'command'    => sanitize_text_field( $command ),
                'action'     => sanitize_text_field( $response['action'] ?? 'unknown' ),
                'session_id' => $session_id,
                'timestamp'  => current_time( 'mysql' ),
                'duration'   => microtime( true ) - $start_time,
            ),
            array( '%s', '%s', '%s', '%s', '%f' )
        );
    }

    /**
     * Check if analytics is enabled.
     *
     * @return bool Whether analytics is enabled.
     */
    private function is_analytics_enabled() {
        $settings = get_option( 'qimemwp_settings', array( 'analytics_enabled' => '0' ) );
        return '1' === $settings['analytics_enabled'];
    }

    /**
     * Add analytics dashboard to admin menu.
     */
    public function add_analytics_page() {
        add_submenu_page(
            'qimemwp-settings',
            __( 'QimemWP Analytics', 'qimemwp-voice-first' ),
            __( 'Analytics', 'qimemwp-voice-first' ),
            'manage_options',
            'qimemwp-analytics',
            array( $this, 'render_analytics_page' )
        );
    }

    /**
     * Render the analytics dashboard.
     */
    public function render_analytics_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'qimemwp-voice-first' ) );
        }
        ?>
        <div class="wrap qimemwp-analytics">
            <h1><?php esc_html_e( 'QimemWP Voice Analytics', 'qimemwp-voice-first' ); ?></h1>
            <div id="qimemwp-analytics-dashboard">
                <h2><?php esc_html_e( 'Voice Interaction Stats', 'qimemwp-voice-first' ); ?></h2>
                <div id="qimemwp-top-commands"></div>
                <div id="qimemwp-engagement-metrics"></div>
                <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
                    <input type="hidden" name="action" value="qimemwp_export_analytics">
                    <?php wp_nonce_field( 'qimemwp_export_analytics_nonce' ); ?>
                    <?php submit_button( __( 'Export as CSV', 'qimemwp-voice-first' ), 'secondary', 'qimemwp-export-csv', false ); ?>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Get analytics data via AJAX.
     */
    public function get_analytics_data() {
        check_ajax_referer( 'qimemwp_admin_nonce', 'nonce' );

        global $wpdb;
        $table_name = $wpdb->prefix . 'qimemwp_analytics';

        // Top commands
        $top_commands = $wpdb->get_results(
            "SELECT command, COUNT(*) as count 
             FROM $table_name 
             GROUP BY command 
             ORDER BY count DESC 
             LIMIT 5",
            ARRAY_A
        );

        // Engagement metrics
        $engagement = $wpdb->get_row(
            "SELECT COUNT(DISTINCT session_id) as sessions, 
                    AVG(duration) as avg_duration, 
                    COUNT(*) as total_commands 
             FROM $table_name",
            ARRAY_A
        );

        // Cache results for 1 hour
        set_transient( 'qimemwp_analytics_data', compact( 'top_commands', 'engagement' ), HOUR_IN_SECONDS );

        wp_send_json_success( compact( 'top_commands', 'engagement' ) );
    }

    /**
     * Export analytics data as CSV.
     */
    public function export_analytics_csv() {
        check_admin_referer( 'qimemwp_export_analytics_nonce' );

        global $wpdb;
        $table_name = $wpdb->prefix . 'qimemwp_analytics';

        $data = $wpdb->get_results( "SELECT * FROM $table_name", ARRAY_A );

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=qimemwp-analytics-' . gmdate( 'Y-m-d' ) . '.csv' );

        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, array( 'ID', 'Command', 'Action', 'Session ID', 'Timestamp', 'Duration' ) );

        foreach ( $data as $row ) {
            fputcsv( $output, $row );
        }

        fclose( $output );
        exit;
    }

    /**
     * Clean up old analytics data based on retention period.
     */
    public function cleanup_old_data() {
        if ( ! $this->is_analytics_enabled() ) {
            return;
        }

        $settings = get_option( 'qimemwp_settings', array( 'analytics_retention' => 30 ) );
        $retention_days = absint( $settings['analytics_retention'] );

        global $wpdb;
        $table_name = $wpdb->prefix . 'qimemwp_analytics';
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table_name WHERE timestamp < %s",
                gmdate( 'Y-m-d H:i:s', strtotime( "-$retention_days days" ) )
            )
        );
    }
}