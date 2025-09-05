# QimemWP: Voice-First Website Builder

**Transform your WordPress site into a voice-activated experience with seamless navigation, content creation, and smart speaker integration.**

**Contributors**: EyuKaz 
**Tags**: voice navigation, accessibility, smart speaker, web speech api, wordpress plugin  
**Requires at least**: WordPress 6.6  
**Tested up to**: WordPress 6.6  
**Requires PHP**: 7.4  
**Stable tag**: 1.0.0  

## Overview

QimemWP: Voice-First Website Builder is a revolutionary WordPress plugin that enables voice-driven interactions for your site. Visitors can navigate pages, search content, or listen to posts using voice commands. Admins can dictate content directly into the WordPress editor, and the plugin supports smart speaker integration (e.g., Alexa, Google Home) for hands-free content delivery. Built with accessibility (WCAG 2.1 compliant), performance, and scalability in mind, QimemWP is perfect for modern WordPress sites running 6.6 or higher, including compatibility with Gutenberg, Elementor, and WooCommerce.

### Key Features

- **Voice Navigation**: Use commands like "Go to About page" or "Search for vegan recipes" to navigate or search your site.
- **Voice Content Editor**: Dictate posts and pages in Gutenberg or Classic Editor with real-time transcription.
- **Smart Speaker Integration**: Access content via Alexa or Google Home using mock API endpoints (production-ready with real APIs).
- **Voice Analytics**: Track voice interactions in a GDPR/CCPA-compliant manner with a customizable dashboard.
- **Custom Voice Branding**: Choose male, female, or default voice personas for text-to-speech output.
- **Accessibility**: Supports screen readers, keyboard shortcuts (Ctrl+Shift+V), and high-contrast mode.
- **Multilingual Support**: Handles English, Spanish, and French with proper encoding for special characters and RTL languages.
- **Performance**: Processes commands in under 1 second, optimized for 1000 concurrent users.
- **Extensibility**: Offers hooks and filters for developers to customize functionality.

## Installation

Follow these steps to install and configure QimemWP:

1. **Download the Plugin**:
   - Upload the `qimemwp-voice-first` folder to `/wp-content/plugins/` via FTP, or use the WordPress admin panel to upload the plugin zip file.

2. **Activate the Plugin**:
   - Navigate to **Plugins > Installed Plugins** in your WordPress admin dashboard.
   - Locate **QimemWP: Voice-First Website Builder** and click **Activate**.

3. **Configure Settings**:
   - Go to **Settings > QimemWP Voice** in the WordPress admin.
   - Set the activation phrase (e.g., "Hey Qimem"), language, voice persona, analytics preferences, and smart speaker integration.

4. **Add the Voice Widget**:
   - The voice widget automatically appears in the footer of your site.
   - To place it elsewhere, use the shortcode `[qimemwp_voice_widget]` in your pages, posts, or theme templates.

5. **Test Voice Features**:
   - Ensure your browser supports the Web Speech API (Chrome, Firefox, Safari, Edge; latest versions as of September 2025).
   - Click the microphone icon or press **Ctrl+Shift+V** to start voice input.
   - Try commands like "Go to About page," "Search for blog posts," or "Read latest post."

6. **Set Up Smart Speaker Integration (Optional)**:
   - Enable smart speaker support in the settings.
   - For testing, use the mock API endpoints provided.
   - For production, configure Alexa Skills Kit or Actions on Google (see Developer Guide below).

## Usage

### For Site Visitors
- **Voice Navigation**:
  - Click the microphone icon (bottom-right corner) or press **Ctrl+Shift+V**.
  - Say commands like:
    - "Go to [page name]" (e.g., "Go to Contact").
    - "Search for [term]" (e.g., "Search for vegan recipes").
    - "Read latest post" to hear the latest blog post.
  - If your browser doesn’t support voice input, type commands in the text field.

- **Supported Languages**:
  - English (US), Spanish (Spain), and French (France) are supported.
  - The plugin handles special characters and RTL languages correctly.

- **Accessibility**:
  - The widget is compatible with screen readers (e.g., NVDA, VoiceOver).
  - High-contrast mode is supported for visually impaired users.
  - Keyboard shortcuts ensure usability without a mouse.

### For Admins
- **Voice Content Editor**:
  - In Gutenberg or Classic Editor, click the microphone icon to dictate content.
  - Use commands like "Insert heading: My Title" or "Add paragraph break."
  - Drafts are auto-saved every 30 seconds to prevent data loss.
  - Filler words (e.g., "um," "uh") are automatically removed.

- **Analytics Dashboard**:
  - Go to **Settings > QimemWP Analytics** to view voice interaction stats.
  - See top commands, session counts, and average interaction duration.
  - Export data as CSV for further analysis (GDPR-compliant, no PII stored).

- **Settings Configuration**:
  - Access **Settings > QimemWP Voice** to customize:
    - **Activation Phrase**: Default is "Hey Qimem."
    - **Language**: Choose English, Spanish, or French.
    - **Voice Persona**: Select default, male, or female for text-to-speech.
    - **Analytics**: Enable/disable tracking and set retention period (1-365 days).
    - **Smart Speaker**: Enable mock API endpoints or configure real APIs.

## Developer Guide

QimemWP is built for extensibility, allowing developers to customize and integrate with ease. Below are detailed instructions for extending the plugin.

**Detailed Instructions coming soon**
