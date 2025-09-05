<?php
/**
 * QimemWP Frontend Class
 *
 * Manages frontend voice navigation, speech recognition, and text-to-speech functionality
 * using the Web Speech API. Ensures accessibility, performance, and compatibility with
 * WordPress 6.6+.
 *
 * @package QimemWP
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * QimemWP Frontend
 *
 * Handles frontend voice interactions, including speech recognition, text-to-speech,
 * and fallback UI for unsupported browsers.
 *
 * @since 1.0.0
 */
class QimemWP_Frontend {

    /**
     * Initialize the frontend functionality.
     */
    public static function init() {
        $instance = new self();
        $instance->register_hooks();
    }

    /**
     * Register hooks for frontend functionality.
     */
    private function register_hooks() {
        // Add voice widget to footer
        add_action( 'wp_footer', array( $this, 'render_voice_widget' ) );

        // Handle keyboard shortcuts
        add_action( 'wp_footer', array( $this, 'add_keyboard_shortcut_script' ) );

        // Filter to modify content for text-to-speech
        add_filter( 'qimemwp_tts_content', array( $this, 'prepare_tts_content' ), 10, 1 );
    }

    /**
     * Render the voice input widget in the footer.
     */
    public function render_voice_widget() {
        // Use shortcode to render widget
        echo do_shortcode( '[qimemwp_voice_widget]' );
    }

    /**
     * Add script for keyboard shortcuts (Ctrl+Shift+V to trigger voice input).
     */
    public function add_keyboard_shortcut_script() {
        ?>
        <script type="text/javascript">
            document.addEventListener( 'keydown', function( event ) {
                if ( event.ctrlKey && event.shiftKey && event.key === 'V' ) {
                    event.preventDefault();
                    const voiceButton = document.querySelector( '.qimemwp-voice-button' );
                    if ( voiceButton ) {
                        voiceButton.click();
                    }
                }
            } );
        </script>
        <?php
    }

    /**
     * Prepare content for text-to-speech by cleaning and formatting.
     *
     * @param string $content Raw content.
     * @return string Cleaned content for TTS.
     */
    public function prepare_tts_content( $content ) {
        // Remove filler words and clean content
        $fillers = array( '/\b(um|uh|like|you know)\b/i' );
        $content = preg_replace( $fillers, '', $content );
        $content = wp_strip_all_tags( $content );
        $content = trim( $content );

        return $content;
    }

    /**
     * Get supported languages for Web Speech API.
     *
     * @return array List of supported languages.
     */
    public static function get_supported_languages() {
        return array(
            'en-US' => __( 'English (US)', 'qimemwp-voice-first' ),
            'es-ES' => __( 'Spanish (Spain)', 'qimemwp-voice-first' ),
            'fr-FR' => __( 'French (France)', 'qimemwp-voice-first' ),
        );
    }
}