<?php
/**
 * QimemWP Core Unit Tests
 *
 * Tests the QimemWP_Core class for voice command parsing, navigation, search, and content reading.
 *
 * @package QimemWP
 * @since 1.0.0
 */

class Test_QimemWP_Core extends WP_UnitTestCase {

    /**
     * Set up before each test.
     */
    public function setUp(): void {
        parent::setUp();
        $this->core = new QimemWP_Core();
    }

    /**
     * Test command parsing for navigation.
     */
    public function test_navigation_command() {
        // Create a test page
        $page_id = $this->factory()->post->create(array(
            'post_type'   => 'page',
            'post_title'  => 'About',
            'post_status' => 'publish',
        ));

        $command = 'Go to About';
        $commands = $this->core->get_available_commands();
        $matches = array();
        $found = false;

        foreach ($commands as $cmd) {
            if (preg_match($cmd['pattern'], $command, $matches)) {
                $result = call_user_func($cmd['callback'], $matches);
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'Navigation command not recognized.');
        $this->assertTrue($result['success'], 'Navigation command failed.');
        $this->assertEquals(get_permalink($page_id), $result['url'], 'Incorrect page URL.');
        $this->assertStringContainsString('Navigating to About', $result['message'], 'Incorrect message.');
    }

    /**
     * Test command parsing for search.
     */
    public function test_search_command() {
        $command = 'Search for vegan recipes';
        $commands = $this->core->get_available_commands();
        $matches = array();
        $found = false;

        foreach ($commands as $cmd) {
            if (preg_match($cmd['pattern'], $command, $matches)) {
                $result = call_user_func($cmd['callback'], $matches);
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'Search command not recognized.');
        $this->assertTrue($result['success'], 'Search command failed.');
        $this->assertEquals(add_query_arg('s', 'vegan recipes', home_url()), $result['url'], 'Incorrect search URL.');
        $this->assertStringContainsString('Searching for "vegan recipes"', $result['message'], 'Incorrect message.');
    }

    /**
     * Test command parsing for reading latest post.
     */
    public function test_read_post_command() {
        // Create a test post
        $post_id = $this->factory()->post->create(array(
            'post_title'   => 'Test Post',
            'post_content' => 'This is a test post content.',
            'post_status'  => 'publish',
            'post_type'    => 'post',
        ));

        $command = 'Read latest post';
        $commands = $this->core->get_available_commands();
        $matches = array();
        $found = false;

        foreach ($commands as $cmd) {
            if (preg_match($cmd['pattern'], $command, $matches)) {
                $result = call_user_func($cmd['callback'], $matches);
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'Read post command not recognized.');
        $this->assertTrue($result['success'], 'Read post command failed.');
        $this->assertEquals('Test Post', $result['title'], 'Incorrect post title.');
        $this->assertEquals('This is a test post content.', $result['content'], 'Incorrect post content.');
        $this->assertStringContainsString('Reading the latest post', $result['message'], 'Incorrect message.');
    }

    /**
     * Test invalid command handling.
     */
    public function test_invalid_command() {
        $command = 'Invalid command';
        $commands = $this->core->get_available_commands();
        $matches = array();
        $found = false;

        foreach ($commands as $cmd) {
            if (preg_match($cmd['pattern'], $command, $matches)) {
                $found = true;
                break;
            }
        }

        $this->assertFalse($found, 'Invalid command was recognized.');
    }

    /**
     * Test voice widget shortcode rendering.
     */
    public function test_voice_widget_shortcode() {
        $output = do_shortcode('[qimemwp_voice_widget]');
        $this->assertStringContainsString('qimemwp-voice-widget', $output, 'Widget class missing.');
        $this->assertStringContainsString('dashicons-microphone', $output, 'Microphone icon missing.');
        $this->assertStringContainsString('Speak or type a command', $output, 'Input placeholder missing.');
    }
}