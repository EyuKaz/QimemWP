/**
 * QimemWP Frontend JavaScript
 *
 * Handles voice recognition, text-to-speech, and fallback UI for the QimemWP plugin.
 * Uses Web Speech API for voice interactions and AJAX for command processing.
 *
 * @package QimemWP
 * @since 1.0.0
 */
(function ($) {
    'use strict';

    // Check if Web Speech API is supported
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    const SpeechSynthesis = window.speechSynthesis;
    const isSpeechSupported = SpeechRecognition && SpeechSynthesis;

    // Initialize plugin
    function init() {
        const $voiceButton = $('.qimemwp-voice-button');
        const $voiceInput = $('.qimemwp-voice-input');

        if (!$voiceButton.length || !$voiceInput.length) {
            return;
        }

        // Initialize speech recognition
        let recognition = null;
        if (isSpeechSupported) {
            recognition = new SpeechRecognition();
            recognition.lang = qimemwp_params.lang || 'en-US';
            recognition.interimResults = false;
            recognition.maxAlternatives = 1;

            recognition.onresult = handleSpeechResult;
            recognition.onerror = handleSpeechError;
            recognition.onend = () => $voiceButton.removeClass('listening');
        }

        // Voice button click handler
        $voiceButton.on('click', function () {
            if (isSpeechSupported && recognition) {
                $voiceButton.addClass('listening');
                $voiceInput.val('');
                recognition.start();
                announce('Listening for your command.');
            } else {
                $voiceInput.focus();
                announce('Speech recognition not supported. Please type your command.');
            }
        });

        // Text input fallback handler
        $voiceInput.on('keypress', function (e) {
            if (e.which === 13) { // Enter key
                const command = $voiceInput.val().trim();
                if (command) {
                    processCommand(command);
                }
            }
        });
    }

    // Handle speech recognition results
    function handleSpeechResult(event) {
        const command = event.results[0][0].transcript.trim();
        $('.qimemwp-voice-input').val(command);
        processCommand(command);
    }

    // Handle speech recognition errors
    function handleSpeechError(event) {
        $('.qimemwp-voice-button').removeClass('listening');
        let message = 'Error processing voice command.';
        if (event.error === 'no-speech') {
            message = 'No speech detected. Please try again.';
        } else if (event.error === 'not-allowed') {
            message = 'Microphone access denied. Please allow microphone access.';
        }
        announce(message);
    }

    // Process command via AJAX
    function processCommand(command) {
        $.ajax({
            url: qimemwp_params.ajax_url,
            type: 'POST',
            data: {
                action: 'qimemwp_process_command',
                nonce: qimemwp_params.nonce,
                command: command
            },
            beforeSend: () => $('.qimemwp-voice-button').addClass('processing'),
            success: function (response) {
                if (response.success) {
                    handleCommandResponse(response.data);
                } else {
                    announce(response.data.message);
                }
            },
            error: () => announce('Error communicating with the server.'),
            complete: () => $('.qimemwp-voice-button').removeClass('processing')
        });
    }

    // Handle command response
    function handleCommandResponse(data) {
        if (data.action === 'navigate' && data.url) {
            window.location.href = data.url;
        } else if (data.action === 'search' && data.url) {
            window.location.href = data.url;
        } else if (data.action === 'read' && data.content) {
            announce(data.title + '. ' + data.content);
        } else {
            announce(data.message);
        }
    }

    // Announce messages using text-to-speech or fallback to alert
    function announce(message) {
        if (SpeechSynthesis && qimemwp_params.voice !== 'none') {
            const utterance = new SpeechSynthesisUtterance(message);
            utterance.lang = qimemwp_params.lang || 'en-US';
            utterance.volume = 1.0;
            utterance.rate = 1.0;
            utterance.pitch = 1.0;

            // Select voice based on settings
            const voices = SpeechSynthesis.getVoices();
            const selectedVoice = voices.find(voice => voice.name.includes(qimemwp_params.voice)) || voices[0];
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
    if (SpeechSynthesis) {
        SpeechSynthesis.onvoiceschanged = () => {};
    }

    // Initialize on document ready
    $(document).ready(init);
})(jQuery);