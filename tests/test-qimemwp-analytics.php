<?php
/**
 * QimemWP Analytics Unit Tests
 *
 * Tests the QimemWP_Analytics class for voice interaction tracking, data storage, and cleanup.
 *
 * @package QimemWP
 * @since 1.0.0
 */

class Test_QimemWP_Analytics extends WP_UnitTestCase {

    /**
     * Set up before each test.
     */
    public function setUp(): void {
        parent::setUp();
        $this->analytics = new QimemWP_Analytics();
        $this->analytics->create_table();
        update_option('qimemwp_settings', array('analytics_enabled' => '1', 'analytics_retention' => 30));
    }

    /**
     * Test command tracking.
     */
    public function test_track_command() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'qimemwp_analytics';

        $command = 'Go to About';
        $response = array('action' => 'navigate');
        $this->analytics->track_command($command, $response);

        $row = $wpdb->get_row("SELECT * FROM $table_name WHERE command = 'Go to About'", ARRAY_A);

        $this->assertNotEmpty($row, 'Command not tracked.');
        $this->assertEquals('navigate', $row['action'], 'Incorrect action tracked.');
        $this->assertNotEmpty($row['session_id'], 'Session ID missing.');
        $this->assertGreaterThan(0, $row['duration'], 'Invalid duration.');
    }

    /**
     * Test analytics disabled.
     */
    public function test_analytics_disabled() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'qimemwp_analytics';
        update_option('qimemwp_settings', array('analytics_enabled' => '0'));

        $command = 'Search for test';
        $response = array('action' => 'search');
        $this->analytics->track_command($command, $response);

        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $this->assertEquals(0, $count, 'Command tracked when analytics disabled.');
    }

    /**
     * Test data cleanup.
     */
    public function test_cleanup_old_data() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'qimemwp_analytics';

        // Insert old data
        $wpdb->insert(
            $table_name,
            array(
                'command'    => 'Old command',
                'action'     => 'navigate',
                'session_id' => wp_generate_uuid4(),
                'timestamp'  => gmdate('Y-m-d H:i:s', strtotime('-31 days')),
                'duration'   => 0.5,
            ),
            array('%s', '%s', '%s', '%s', '%f')
        );

        // Insert recent data
        $wpdb->insert(
            $table_name,
            array(
                'command'    => 'Recent command',
                'action'     => 'search',
                'session_id' => wp_generate_uuid4(),
                'timestamp'  => current_time('mysql'),
                'duration'   => 0.3,
            ),
            array('%s', '%s', '%s', '%s', '%f')
        );

        $this->analytics->cleanup_old_data();

        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $this->assertEquals(1, $count, 'Old data not cleaned up.');
        $row = $wpdb->get_row("SELECT * FROM $table_name", ARRAY_A);
        $this->assertEquals('Recent command', $row['command'], 'Wrong data retained.');
    }

    /**
     * Test analytics data retrieval.
     */
    public function test_get_analytics_data() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'qimemwp_analytics';

        // Insert test data
        $wpdb->insert(
            $table_name,
            array(
                'command'    => 'Go to About',
                'action'     => 'navigate',
                'session_id' => wp_generate_uuid4(),
                'timestamp'  => current_time('mysql'),
                'duration'   => 0.4,
            ),
            array('%s', '%s', '%s', '%s', '%f')
        );

        $response = $this->analytics->get_analytics_data();
        $data = json_decode(wp_json_encode($response->data), true);

        $this->assertTrue($response->success, 'Analytics data retrieval failed.');
        $this->assertNotEmpty($data['top_commands'], 'Top commands missing.');
        $this->assertEquals('Go to About', $data['top_commands'][0]['command'], 'Incorrect top command.');
        $this->assertEquals(1, $data['engagement']['sessions'], 'Incorrect session count.');
    }

    /**
     * Test CSV export.
     */
    public function test_export_analytics_csv() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'qimemwp_analytics';

        // Insert test data
        $wpdb->insert(
            $table_name,
            array(
                'command'    => 'Test command',
                'action'     => 'navigate',
                'session_id' => wp_generate_uuid4(),
                'timestamp'  => current_time('mysql'),
                'duration'   => 0.2,
            ),
            array('%s', '%s', '%s', '%s', '%f')
        );

        // Mock admin post request
        $_POST['action'] = 'qimemwp_export_analytics';
        $_POST['_wpnonce'] = wp_create_nonce('qimemwp_export_analytics_nonce');

        ob_start();
        $this->analytics->export_analytics_csv();
        $output = ob_get_clean();

        $this->assertStringContainsString('Test command', $output, 'CSV export missing command.');
        $this->assertStringContainsString('ID,Command,Action,Session ID,Timestamp,Duration', $output, 'CSV header missing.');
    }
}