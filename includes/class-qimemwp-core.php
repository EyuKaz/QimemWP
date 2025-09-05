<?php
/**
 * QimemWP Core Class
 *
 * Handles core functionality for voice navigation and command processing using
 * the Web Speech API. Ensures compatibility with WordPress 6.6+, multilingual support,
 * and accessibility compliance.
 *
 * @package QimemWP
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * QimemWP Core
 *
 * Manages voice recognition, command parsing, and navigation execution.
 *
 * @since 1.0.0
 */
class QimemWP_Core {

    /**
     * Initialize the core functionality.
     */
    public static function init() {
        $instance = new self();
        $instance->register_hooks();
    }

    /**
     * Register hooks for core functionality.
     */
    private function register_hooks() {
        // AJAX handler for processing voice commands
        add_action( 'wp_ajax_qimemwp_process_command', array( $this, 'process_voice_command' ) );
        add_action( 'wp_ajax_nopriv_qimemwp_process_command', array( $this, 'process_voice_command' ) );

        // Filter to allow custom commands
        add_filter( 'qimemwp_available_commands', array( $this, 'get_available_commands' ) );

        // Shortcode for voice input widget
        add_shortcode( 'qimemwp_voice_widget', array( $this, 'render_voice_widget' ) );
    }

    /**
     * Get available voice commands.
     *
     * @param array $commands Existing commands.
     * @return array Updated commands.
     */
    public function get_available_commands( $commands = array() ) {
        $default_commands = array(
            'go_to' => array(
                'pattern' => '/^(go to|navigate to|open) (.*)$/i',
                'callback' => array( $this, 'handle_navigation' ),
            ),
            'search' => array(
                'pattern' => '/^search for (.*)$/i',
                'callback' => array( $this, 'handle_search' ),
            ),
            'read_post' => array(
                'pattern' => '/^read (latest|recent)? ?(post|article)$/i',
                'callback' => array( $this, 'handle_read_post' ),
            ),
        );

        return array_merge( $default_commands, $commands );
    }

    /**
     * Process voice commands via AJAX.
     */
    public function process_voice_command() {
        check_ajax_referer( 'qimemwp_nonce', 'nonce' );

        $command = isset( $_POST['command'] ) ? sanitize_text_field( wp_unslash( $_POST['command'] ) ) : '';

        if ( empty( $command ) ) {
            wp_send_json_error( array( 'message' => __( 'No command provided.', 'qimemwp-voice-first' ) ) );
        }

        $commands = apply_filters( 'qimemwp_available_commands', array() );
        $response = array( 'success' => false, 'message' => __( 'Command not recognized.', 'qimemwp-voice-first' ) );

        foreach ( $commands as $key => $cmd ) {
            if ( preg_match( $cmd['pattern'], $command, $matches ) ) {
                $result = call_user_func( $cmd['callback'], $matches );
                $response = array_merge( $response, $result );
                break;
            }
        }

        if ( $response['success'] ) {
            do_action( 'qimemwp_command_processed', $command, $response );
            wp_send_json_success( $response );
        } else {
            wp_send_json_error( $response );
        }
    }

    /**
     * Handle navigation commands (e.g., "Go to About page").
     *
     * @param array $matches Regex matches from command.
     * @return array Response data.
     */
    private function handle_navigation( $matches ) {
        $target = isset( $matches[2] ) ? sanitize_text_field( $matches[2] ) : '';
        $page = get_page_by_title( $target, OBJECT, 'page' );

        if ( $page && ! is_wp_error( $page ) ) {
            $url = get_permalink( $page->ID );
            return array(
                'success' => true,
                'action'  => 'navigate',
                'url'     => esc_url( $url ),
                'message' => sprintf( __( 'Navigating to %s.', 'qimemwp-voice-first' ), $target ),
            );
        }

        return array(
            'success' => false,
            'message' => sprintf( __( 'Page "%s" not found.', 'qimemwp-voice-first' ), $target ),
        );
    }

    /**
     * Handle search commands (e.g., "Search for vegan recipes").
     *
     * @param array $matches Regex matches from command.
     * @return array Response data.
     */
    private function handle_search( $matches ) {
        $query = isset( $matches[1] ) ? sanitize_text_field( $matches[1] ) : '';
        $search_url = esc_url( add_query_arg( 's', $query, home_url() ) );

        return array(
            'success' => true,
            'action'  => 'search',
            'url'     => $search_url,
            'message' => sprintf( __( 'Searching for "%s".', 'qimemwp-voice-first' ), $query ),
        );
    }

    /**
     * Handle read post commands (e.g., "Read latest post").
     *
     * @param array $matches Regex matches from command.
     * @return array Response data.
     */
    private function handle_read_post( $matches ) {
        $args = array(
            'post_type'      => 'post',
            'posts_per_page' => 1,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        $query = new WP_Query( $args );

        if ( $query->have_posts() ) {
            $query->the_post();
            $content = wp_strip_all_tags( get_the_content() );
            wp_reset_postdata();

            return array(
                'success' => true,
                'action'  => 'read',
                'content' => $content,
                'title'   => get_the_title(),
                'message' => __( 'Reading the latest post.', 'qimemwp-voice-first' ),
            );
        }

        return array(
            'success' => false,
            'message' => __( 'No posts found.', 'qimemwp-voice-first' ),
        );
    }

    /**
     * Render voice input widget shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string Widget HTML.
     */
    public function render_voice_widget( $atts ) {
        $atts = shortcode_atts( array(
            'label' => __( 'Voice Command', 'qimemwp-voice-first' ),
        ), $atts, 'qimemwp_voice_widget' );

        ob_start();
        ?>
        <div class="qimemwp-voice-widget" role="region" aria-label="<?php echo esc_attr( $atts['label'] ); ?>">
            <button type="button" class="qimemwp-voice-button" aria-label="<?php esc_attr_e( 'Start voice input', 'qimemwp-voice-first' ); ?>">
                <span class="dashicons dashicons-microphone"></span>
            </button>
            <input type="text" class="qimemwp-voice-input" placeholder="<?php esc_attr_e( 'Speak or type a command...', 'qimemwp-voice-first' ); ?>" aria-label="<?php esc_attr_e( 'Voice command input', 'qimemwp-voice-first' ); ?>">
        </div>
        <?php
        return ob_get_clean();
    }
}