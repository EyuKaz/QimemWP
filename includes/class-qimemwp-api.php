<?php
/**
 * QimemWP API Class
 *
 * Provides mock REST API endpoints for smart speaker integration (Alexa, Google Home).
 * Handles content delivery for voice output, ensuring security and compatibility with WordPress 6.6+.
 *
 * @package QimemWP
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * QimemWP API
 *
 * Manages mock API endpoints for smart speaker integration, delivering content for text-to-speech.
 *
 * @since 1.0.0
 */
class QimemWP_API {

    /**
     * Initialize the API functionality.
     */
    public static function init() {
        $instance = new self();
        $instance->register_hooks();
    }

    /**
     * Register hooks for API functionality.
     */
    private function register_hooks() {
        // Register REST API routes
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Register REST API routes for smart speaker integration.
     */
    public function register_routes() {
        if ( ! $this->is_smart_speaker_enabled() ) {
            return;
        }

        // Endpoint for reading latest post
        register_rest_route(
            'qimemwp/v1',
            '/latest-post',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_latest_post' ),
                'permission_callback' => array( $this, 'check_permissions' ),
            )
        );

        // Endpoint for reading specific page by slug
        register_rest_route(
            'qimemwp/v1',
            '/page/(?P<slug>[a-zA-Z0-9-]+)',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_page_by_slug' ),
                'permission_callback' => array( $this, 'check_permissions' ),
            )
        );

        // Endpoint for reading WooCommerce product by slug (if WooCommerce is active)
        register_rest_route(
            'qimemwp/v1',
            '/product/(?P<slug>[a-zA-Z0-9-]+)',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_product_by_slug' ),
                'permission_callback' => array( $this, 'check_permissions' ),
            )
        );
    }

    /**
     * Check if smart speaker integration is enabled.
     *
     * @return bool Whether smart speaker integration is enabled.
     */
    private function is_smart_speaker_enabled() {
        $settings = get_option( 'qimemwp_settings', array( 'smart_speaker_enabled' => '0' ) );
        return '1' === $settings['smart_speaker_enabled'];
    }

    /**
     * Permission callback for REST endpoints.
     *
     * @return bool Whether the request is authorized.
     */
    public function check_permissions() {
        // Mock authentication: In a real implementation, use OAuth or API keys
        // For demo, allow public access to simulate smart speaker requests
        return true;
    }

    /**
     * Get the latest post for smart speaker output.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response|WP_Error
     */
    public function get_latest_post( $request ) {
        $cache_key = 'qimemwp_latest_post';
        $cached = get_transient( $cache_key );

        if ( false !== $cached ) {
            return rest_ensure_response( $cached );
        }

        $args = array(
            'post_type'      => 'post',
            'posts_per_page' => 1,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        $query = new WP_Query( $args );

        if ( ! $query->have_posts() ) {
            return new WP_Error(
                'no_posts',
                __( 'No posts found.', 'qimemwp-voice-first' ),
                array( 'status' => 404 )
            );
        }

        $query->the_post();
        $response = array(
            'title'   => get_the_title(),
            'content' => wp_strip_all_tags( apply_filters( 'qimemwp_tts_content', get_the_content() ) ),
        );
        wp_reset_postdata();

        set_transient( $cache_key, $response, HOUR_IN_SECONDS );

        return rest_ensure_response( $response );
    }

    /**
     * Get a page by slug for smart speaker output.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response|WP_Error
     */
    public function get_page_by_slug( $request ) {
        $slug = sanitize_text_field( $request['slug'] );
        $cache_key = 'qimemwp_page_' . md5( $slug );
        $cached = get_transient( $cache_key );

        if ( false !== $cached ) {
            return rest_ensure_response( $cached );
        }

        $page = get_page_by_path( $slug, OBJECT, 'page' );

        if ( ! $page || 'publish' !== $page->post_status ) {
            return new WP_Error(
                'no_page',
                __( 'Page not found.', 'qimemwp-voice-first' ),
                array( 'status' => 404 )
            );
        }

        $response = array(
            'title'   => get_the_title( $page->ID ),
            'content' => wp_strip_all_tags( apply_filters( 'qimemwp_tts_content', $page->post_content ) ),
        );

        set_transient( $cache_key, $response, HOUR_IN_SECONDS );

        return rest_ensure_response( $response );
    }

    /**
     * Get a WooCommerce product by slug for smart speaker output.
     *
     * @param WP_REST_Request $request The REST request.
     * @return WP_REST_Response|WP_Error
     */
    public function get_product_by_slug( $request ) {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return new WP_Error(
                'woocommerce_not_active',
                __( 'WooCommerce is not active.', 'qimemwp-voice-first' ),
                array( 'status' => 400 )
            );
        }

        $slug = sanitize_text_field( $request['slug'] );
        $cache_key = 'qimemwp_product_' . md5( $slug );
        $cached = get_transient( $cache_key );

        if ( false !== $cached ) {
            return rest_ensure_response( $cached );
        }

        $product = get_page_by_path( $slug, OBJECT, 'product' );

        if ( ! $product || 'publish' !== $product->post_status ) {
            return new WP_Error(
                'no_product',
                __( 'Product not found.', 'qimemwp-voice-first' ),
                array( 'status' => 404 )
            );
        }

        $wc_product = wc_get_product( $product->ID );
        $response = array(
            'title'   => get_the_title( $product->ID ),
            'content' => wp_strip_all_tags( apply_filters( 'qimemwp_tts_content', $wc_product->get_description() ) ),
        );

        set_transient( $cache_key, $response, HOUR_IN_SECONDS );

        return rest_ensure_response( $response );
    }
}

/**
 * Notes for Real Smart Speaker Integration:
 * - For Alexa: Create an Alexa Skill using the Alexa Skills Kit (ASK). Configure the skill to call the /qimemwp/v1/latest-post endpoint.
 * - For Google Home: Create an Action using Actions on Google. Set up Dialogflow to handle intents and call the /qimemwp/v1 endpoints.
 * - Secure endpoints with OAuth or API keys in production (replace check_permissions with proper authentication).
 * - Test endpoints with tools like Postman before deploying to smart speaker platforms.
 * - Ensure HTTPS is enabled on the WordPress site for secure API calls.
 */