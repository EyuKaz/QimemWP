<?php
/**
 * Plugin Name: QimemWP: Voice-First Website Builder
 * Plugin URI: https://qimem.arthimetic.com
 * Description: A WordPress plugin that enables voice navigation, voice content creation, and smart speaker integration for accessible, voice-first experiences.
 * Version: 1.0.0
 * Author: EyuKaz
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: qimemwp-voice-first
 * Domain Path: /languages
 * Requires at least: 6.6
 * Requires PHP: 7.4
 *
 * @package QimemWP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * QimemWP Voice-First Website Builder
 *
 * Main plugin class responsible for initializing the plugin, defining constants,
 * and loading core components.
 *
 * @since 1.0.0
 */
class QimemWP {

    /**
     * Plugin version.
     *
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * Plugin text domain.
     *
     * @var string
     */
    const TEXT_DOMAIN = 'qimemwp-voice-first';

    /**
     * Instance of the class (singleton pattern).
     *
     * @var QimemWP
     */
    private static $instance = null;

    /**
     * Get the singleton instance of the plugin.
     *
     * @return QimemWP
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     *
     * Initializes the plugin by setting up constants, loading dependencies,
     * and registering hooks.
     */
    private function __construct() {
        $this->define_constants();
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Define plugin constants.
     */
    private function define_constants() {
        define( 'QIMEMWP_VERSION', self::VERSION );
        define( 'QIMEMWP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
        define( 'QIMEMWP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
        define( 'QIMEMWP_TEXT_DOMAIN', self::TEXT_DOMAIN );
    }

    /**
     * Load required files and dependencies.
     */
    private function load_dependencies() {
        require_once QIMEMWP_PLUGIN_DIR . 'includes/class-qimemwp-core.php';
        require_once QIMEMWP_PLUGIN_DIR . 'includes/class-qimemwp-admin.php';
        require_once QIMEMWP_PLUGIN_DIR . 'includes/class-qimemwp-frontend.php';
        require_once QIMEMWP_PLUGIN_DIR . 'includes/class-qimemwp-analytics.php';
        require_once QIMEMWP_PLUGIN_DIR . 'includes/class-qimemwp-api.php';
    }

    /**
     * Initialize hooks for the plugin.
     */
    private function init_hooks() {
        // Load text domain for translations
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

        // Initialize core components
        add_action( 'init', array( 'QimemWP_Core', 'init' ) );

        // Initialize admin functionality
        if ( is_admin() ) {
            add_action( 'init', array( 'QimemWP_Admin', 'init' ) );
        }

        // Initialize frontend functionality
        if ( ! is_admin() ) {
            add_action( 'wp', array( 'QimemWP_Frontend', 'init' ) );
        }

        // Initialize analytics
        add_action( 'init', array( 'QimemWP_Analytics', 'init' ) );

        // Initialize API integrations
        add_action( 'rest_api_init', array( 'QimemWP_API', 'init' ) );

        // Enqueue scripts and styles
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    /**
     * Load plugin text domain for translations.
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            QIMEMWP_TEXT_DOMAIN,
            false,
            dirname( plugin_basename( __FILE__ ) ) . '/languages/'
        );
    }

    /**
     * Enqueue frontend scripts and styles.
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_script(
            'qimemwp-frontend',
            QIMEMWP_PLUGIN_URL . 'assets/js/qimemwp-frontend.min.js',
            array(),
            QIMEMWP_VERSION,
            true
        );

        wp_enqueue_style(
            'qimemwp-frontend',
            QIMEMWP_PLUGIN_URL . 'assets/css/qimemwp-frontend.min.css',
            array(),
            QIMEMWP_VERSION
        );

        // Localize script with settings and nonces
        wp_localize_script(
            'qimemwp-frontend',
            'qimemwp_params',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'qimemwp_nonce' ),
                'lang'     => get_option( 'qimemwp_language', 'en-US' ),
                'voice'    => get_option( 'qimemwp_voice_persona', 'default' ),
            )
        );
    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_admin_assets() {
        wp_enqueue_script(
            'qimemwp-admin',
            QIMEMWP_PLUGIN_URL . 'assets/js/qimemwp-admin.min.js',
            array( 'jquery' ),
            QIMEMWP_VERSION,
            true
        );

        wp_enqueue_style(
            'qimemwp-admin',
            QIMEMWP_PLUGIN_URL . 'assets/css/qimemwp-admin.min.css',
            array(),
            QIMEMWP_VERSION
        );

        // Localize script with settings and nonces
        wp_localize_script(
            'qimemwp-admin',
            'qimemwp_admin_params',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'qimemwp_admin_nonce' ),
            )
        );
    }
}

// Initialize the plugin
QimemWP::get_instance();