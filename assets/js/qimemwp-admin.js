/**
 * QimemWP Admin JavaScript
 *
 * Handles dynamic interactions on the QimemWP settings page, including voice persona preview,
 * input validation, and accessibility features.
 *
 * @package QimemWP
 * @since 1.0.0
 */
(function ($) {
    'use strict';

    // Check if Web Speech API is supported for voice preview
    const SpeechSynthesis = window.speechSynthesis;
    const isSpeechSupported = !!SpeechSynthesis;

    // Initialize admin functionality
    function init() {
        const $voicePersonaSelect = $('select[name="qimemwp_settings[voice_persona]"]');
        const $analyticsRetentionInput = $('input[name="qimemwp_settings[analytics_retention]"]');

        // Voice persona preview
        if (isSpeechSupported) {
            $voicePersonaSelect.on('change', handleVoicePersonaPreview);
        } else {
            $voicePersonaSelect.after('<p class="description qimemwp-voice-warning">' + 
                qimemwp_admin_params.i18n.speech_not_supported + 
                '</p>');
        }

        // Validate analytics retention input
        $analyticsRetentionInput.on('input', validateRetentionInput);

        // Announce settings saved
        if ($('.qimemwp-settings-saved').length) {
            announce(qimemwp_admin_params.i18n.settings_saved);
        }
    }

    // Handle voice persona preview
    function handleVoicePersonaPreview() {
        const $select = $(this);
        const voicePersona = $select.val();
        const testMessage = qimemwp_admin_params.i18n.preview_message;

        // Cancel any ongoing speech
        SpeechSynthesis.cancel();

        // Create utterance for preview
        const utterance = new SpeechSynthesisUtterance(testMessage);
        utterance.lang = $('select[name="qimemwp_settings[language]"]').val() || 'en-US';
        utterance.volume = 1.0;
        utterance.rate = 1.0;
        utterance.pitch = 1.0;

        // Select voice based on persona
        const voices = SpeechSynthesis.getVoices();
        const selectedVoice = voices.find(voice => voice.name.toLowerCase().includes(voicePersona)) || voices[0];
        if (selectedVoice) {
            utterance.voice = selectedVoice;
        }

        // Speak the preview
        SpeechSynthesis.speak(utterance);
        announce(qimemwp_admin_params.i18n.voice_previewed.replace('%s', $select.find('option:selected').text()));
    }

    // Validate analytics retention input
    function validateRetentionInput() {
        const $input = $(this);
        const value = parseInt($input.val(), 10);
        if (isNaN(value) || value < 1 || value > 365) {
            $input.val(30); // Reset to default
            announce(qimemwp_admin_params.i18n.invalid_retention);
        }
    }

    // Announce messages using text-to-speech or fallback to alert
    function announce(message) {
        if (isSpeechSupported) {
            const utterance = new SpeechSynthesisUtterance(message);
            utterance.lang = $('select[name="qimemwp_settings[language]"]').val() || 'en-US';
            utterance.volume = 1.0;
            utterance.rate = 1.0;
            utterance.pitch = 1.0;

            const voices = SpeechSynthesis.getVoices();
            const selectedVoice = voices.find(voice => voice.name.toLowerCase().includes(
                $('select[name="qimemwp_settings[voice_persona]"]').val()
            )) || voices[0];
            if (selectedVoice) {
                utterance.voice = selectedVoice;
            }

            SpeechSynthesis.speak(utterance);
        } else {
            alert(message);
        }

        // Announce to screen readers
        const $liveRegion = $('<div/>', {
            'class': 'qimemwp-live-region',
            'aria-live': 'polite',
            'role': 'status',
            'style': 'position: absolute; left: -9999px;'
        }).text(message);
        $('body').append($liveRegion);
        setTimeout(() => $liveRegion.remove(), 5000);
    }

    // Load voices when available (handles async loading in some browsers)
    if (isSpeechSupported) {
        SpeechSynthesis.onvoiceschanged = () => {};
    }

    // Initialize on document ready
    $(document).ready(init);
})(jQuery);