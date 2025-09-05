<?php
/**
 * QimemWP Admin Class
 *
 * Manages the admin settings page for configuring voice features, using the WordPress
 * Settings API. Ensures security, accessibility, and compatibility with WordPress 6.6+.
 *
 * @package QimemWP
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * QimemWP Admin
 *
 * Handles the settings page for voice feature configuration, including activation phrase,
 * language, voice persona, analytics, and smart speaker integration.
 *
 * @since 1.0.0
 */
class QimemWP_Admin {

    /**
     * Initialize the admin functionality.
     */
    public static function init() {
        $instance = new self();
        $instance->register_hooks();
    }

    /**
     * Register hooks for admin functionality.
     */
    private function register_hooks() {
        // Add settings page to admin menu
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );

        // Register settings
        add_action( 'admin_init', array( $this, 'register_settings' ) );

        // Enqueue admin assets (already handled in main plugin file)
    }

    /**
     * Add settings page to the admin menu.
     */
    public function add_settings_page() {
        add_options_page(
            __( 'QimemWP Settings', 'qimemwp-voice-first' ),
            __( 'QimemWP Voice', 'qimemwp-voice-first' ),
            'manage_options',
            'qimemwp-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register settings using WordPress Settings API.
     */
    public function register_settings() {
        // Register settings group
        register_setting(
            'qimemwp_settings_group',
            'qimemwp_settings',
            array(
                'sanitize_callback' => array( $this, 'sanitize_settings' ),
                'default' => array(
                    'activation_phrase' => 'Hey Qimem',
                    'language' => 'en-US',
                    'voice_persona' => 'default',
                    'analytics_enabled' => '0',
                    'analytics_retention' => '30',
                    'smart_speaker_enabled' => '0',
                ),
            )
        );

        // Add settings section
        add_settings_section(
            'qimemwp_main_section',
            __( 'Voice Settings', 'qimemwp-voice-first' ),
            array( $this, 'render_section_info' ),
            'qimemwp-settings'
        );

        // Add settings fields
        add_settings_field(
            'activation_phrase',
            __( 'Activation Phrase', 'qimemwp-voice-first' ),
            array( $this, 'render_activation_phrase_field' ),
            'qimemwp-settings',
            'qimemwp_main_section'
        );

        add_settings_field(
            'language',
            __( 'Language', 'qimemwp-voice-first' ),
            array( $this, 'render_language_field' ),
            'qimemwp-settings',
            'qimemwp_main_section'
        );

        add_settings_field(
            'voice_persona',
            __( 'Voice Persona', 'qimemwp-voice-first' ),
            array( $this, 'render_voice_persona_field' ),
            'qimemwp-settings',
            'qimemwp_main_section'
        );

        add_settings_field(
            'analytics_enabled',
            __( 'Enable Analytics', 'qimemwp-voice-first' ),
            array( $this, 'render_analytics_enabled_field' ),
            'qimemwp-settings',
            'qimemwp_main_section'
        );

        add_settings_field(
            'analytics_retention',
            __( 'Analytics Retention (days)', 'qimemwp-voice-first' ),
            array( $this, 'render_analytics_retention_field' ),
            'qimemwp-settings',
            'qimemwp_main_section'
        );

        add_settings_field(
            'smart_speaker_enabled',
            __( 'Enable Smart Speaker Integration', 'qimemwp-voice-first' ),
            array( $this, 'render_smart_speaker_enabled_field' ),
            'qimemwp-settings',
            'qimemwp_main_section'
        );
    }

    /**
     * Sanitize settings input.
     *
     * @param array $input Raw input data.
     * @return array Sanitized input.
     */
    public function sanitize_settings( $input ) {
        $sanitized = array();

        $sanitized['activation_phrase'] = sanitize_text_field( $input['activation_phrase'] ?? 'Hey Qimem' );
        $sanitized['language'] = in_array( $input['language'] ?? 'en-US', array_keys( QimemWP_Frontend::get_supported_languages() ), true ) ? $input['language'] : 'en-US';
        $sanitized['voice_persona'] = sanitize_text_field( $input['voice_persona'] ?? 'default' );
        $sanitized['analytics_enabled'] = isset( $input['analytics_enabled'] ) ? '1' : '0';
        $sanitized['analytics_retention'] = absint( $input['analytics_retention'] ?? 30 );
        $sanitized['smart_speaker_enabled'] = isset( $input['smart_speaker_enabled'] ) ? '1' : '0';

        return $sanitized;
    }

    /**
     * Render settings section description.
     */
    public function render_section_info() {
        echo '<p>' . esc_html__( 'Configure voice navigation, analytics, and smart speaker integration for QimemWP.', 'qimemwp-voice-first' ) . '</p>';
    }

    /**
     * Render activation phrase field.
     */
    public function render_activation_phrase_field() {
        $settings = get_option( 'qimemwp_settings', array( 'activation_phrase' => 'Hey Qimem' ) );
        ?>
        <input type="text" name="qimemwp_settings[activation_phrase]" value="<?php echo esc_attr( $settings['activation_phrase'] ); ?>" class="regular-text" aria-describedby="activation-phrase-desc">
        <p id="activation-phrase-desc"><?php esc_html_e( 'The phrase to activate voice commands (e.g., "Hey Qimem").', 'qimemwp-voice-first' ); ?></p>
        <?php
    }

    /**
     * Render language selection field.
     */
    public function render_language_field() {
        $settings = get_option( 'qimemwp_settings', array( 'language' => 'en-US' ) );
        $languages = QimemWP_Frontend::get_supported_languages();
        ?>
        <select name="qimemwp_settings[language]" aria-describedby="language-desc">
            <?php foreach ( $languages as $code => $name ) : ?>
                <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $settings['language'], $code ); ?>><?php echo esc_html( $name ); ?></option>
            <?php endforeach; ?>
        </select>
        <p id="language-desc"><?php esc_html_e( 'Select the language for voice recognition and synthesis.', 'qimemwp-voice-first' ); ?></p>
        <?php
    }

    /**
     * Render voice persona field.
     */
    public function render_voice_persona_field() {
        $settings = get_option( 'qimemwp_settings', array( 'voice_persona' => 'default' ) );
        $personas = array(
            'default' => __( 'Default', 'qimemwp-voice-first' ),
            'male' => __( 'Male', 'qimemwp-voice-first' ),
            'female' => __( 'Female', 'qimemwp-voice-first' ),
        );
        ?>
        <select name="qimemwp_settings[voice_persona]" aria-describedby="voice-persona-desc">
            <?php foreach ( $personas as $key => $name ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $settings['voice_persona'], $key ); ?>><?php echo esc_html( $name ); ?></option>
            <?php endforeach; ?>
        </select>
        <p id="voice-persona-desc"><?php esc_html_e( 'Select the voice persona for text-to-speech output.', 'qimemwp-voice-first' ); ?></p>
        <?php
    }

    /**
     * Render analytics enabled field.
     */
    public function render_analytics_enabled_field() {
        $settings = get_option( 'qimemwp_settings', array( 'analytics_enabled' => '0' ) );
        ?>
        <input type="checkbox" name="qimemwp_settings[analytics_enabled]" value="1" <?php checked( $settings['analytics_enabled'], '1' ); ?> aria-describedby="analytics-enabled-desc">
        <p id="analytics-enabled-desc"><?php esc_html_e( 'Enable tracking of voice interactions (GDPR/CCPA compliant).', 'qimemwp-voice-first' ); ?></p>
        <?php
    }

    /**
     * Render analytics retention field.
     */
    public function render_analytics_retention_field() {
        $settings = get_option( 'qimemwp_settings', array( 'analytics_retention' => '30' ) );
        ?>
        <input type="number" name="qimemwp_settings[analytics_retention]" value="<?php echo esc_attr( $settings['analytics_retention'] ); ?>" min="1" max="365" class="small-text" aria-describedby="analytics-retention-desc">
        <p id="analytics-retention-desc"><?php esc_html_e( 'Number of days to retain analytics data.', 'qimemwp-voice-first' ); ?></p>
        <?php
    }

    /**
     * Render smart speaker enabled field.
     */
    public function render_smart_speaker_enabled_field() {
        $settings = get_option( 'qimemwp_settings', array( 'smart_speaker_enabled' => '0' ) );
        ?>
        <input type="checkbox" name="qimemwp_settings[smart_speaker_enabled]" value="1" <?php checked( $settings['smart_speaker_enabled'], '1' ); ?> aria-describedby="smart-speaker-enabled-desc">
        <p id="smart-speaker-enabled-desc"><?php esc_html_e( 'Enable integration with Alexa and Google Home (mock API).', 'qimemwp-voice-first' ); ?></p>
        <?php
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'qimemwp-voice-first' ) );
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'QimemWP Voice-First Settings', 'qimemwp-voice-first' ); ?></h1>
            <form method="post" action="options.php" novalidate="novalidate">
                <?php
                settings_fields( 'qimemwp_settings_group' );
                do_settings_sections( 'qimemwp-settings' );
                submit_button();
                ?>
            </form>
            <div class="qimemwp-help">
                <h2><?php esc_html_e( 'Help & Setup Guide', 'qimemwp-voice-first' ); ?></h2>
                <p><?php esc_html_e( 'Use the settings above to configure voice navigation. Add the [qimemwp_voice_widget] shortcode to your pages or let it render automatically in the footer.', 'qimemwp-voice-first' ); ?></p>
                <p><?php esc_html_e( 'Supported commands include: "Go to [page]", "Search for [term]", "Read latest post".', 'qimemwp-voice-first' ); ?></p>
            </div>
        </div>
        <?php
    }
}