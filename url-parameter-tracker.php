<?php
/*
Plugin Name: URL Parameter Tracker
Description: Tracks URL parameters and autofills them into Contact Form 7 forms.
Version: 1.0.1
Author: LFMC
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin Update Checker
require 'plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/websupport-lfmc/url-parameter-tracker',
    __FILE__,
    'url-parameter-tracker'
);

$myUpdateChecker->setBranch('main');

// Enqueue the JavaScript file
add_action('wp_enqueue_scripts', 'upt_enqueue_scripts');
function upt_enqueue_scripts() {
    wp_enqueue_script('upt-script', plugin_dir_url(__FILE__) . 'js/upt-script.js', array('jquery'), '1.4', true);

    // Get plugin settings
    $upt_settings = get_option('upt_settings', array());

    wp_localize_script('upt-script', 'upt_params', array(
        'trackedParams'     => isset($upt_settings['tracked_params']) ? array_map('trim', explode(',', $upt_settings['tracked_params'])) : array(),
        'trackAll'          => isset($upt_settings['track_all']) ? (bool)$upt_settings['track_all'] : false,
        'cookieLifetime'    => isset($upt_settings['cookie_lifetime']) ? intval($upt_settings['cookie_lifetime']) : 30,
        'paramFieldMapping' => isset($upt_settings['param_field_mapping']) ? $upt_settings['param_field_mapping'] : '',
        'ajaxUrl'           => admin_url('admin-ajax.php'),
    ));
}

// Add top-level menu and submenu pages
add_action('admin_menu', 'upt_add_admin_menus');
function upt_add_admin_menus() {
    // Add top-level menu
    add_menu_page(
        'URL Parameter Tracker',         // Page title
        'URL Parameter Tracker',         // Menu title
        'manage_options',                // Capability
        'url_parameter_tracker',         // Menu slug
        'upt_options_page',              // Function to display the page content
        'dashicons-welcome-view-site',   // Icon URL
        80                               // Position
    );

    // Add settings submenu page
    add_submenu_page(
        'url_parameter_tracker',         // Parent slug
        'Settings',                      // Page title
        'Settings',                      // Menu title
        'manage_options',                // Capability
        'url_parameter_tracker',         // Menu slug (same as parent to override the parent page callback)
        'upt_options_page'               // Function to display the page content
    );

    // Add session data submenu page
    add_submenu_page(
        'url_parameter_tracker',         // Parent slug
        'Session Data',                  // Page title
        'Session Data',                  // Menu title
        'manage_options',                // Capability
        'upt_session_data',              // Menu slug
        'upt_session_data_page'          // Function to display the page content
    );
}

// Register settings
add_action('admin_init', 'upt_settings_init');
function upt_settings_init() {
    register_setting('upt_settings_group', 'upt_settings');

    add_settings_section(
        'upt_settings_section',
        __('Tracking Settings', 'upt'),
        'upt_settings_section_callback',
        'url_parameter_tracker'
    );

    add_settings_field(
        'tracked_params',
        __('Parameters to Track', 'upt'),
        'upt_tracked_params_render',
        'url_parameter_tracker',
        'upt_settings_section'
    );

    add_settings_field(
        'track_all',
        __('Track All Parameters', 'upt'),
        'upt_track_all_render',
        'url_parameter_tracker',
        'upt_settings_section'
    );

    add_settings_field(
        'cookie_lifetime',
        __('Cookie Lifetime (days)', 'upt'),
        'upt_cookie_lifetime_render',
        'url_parameter_tracker',
        'upt_settings_section'
    );

    add_settings_field(
        'param_field_mapping',
        __('Parameter to Field Mapping', 'upt'),
        'upt_param_field_mapping_render',
        'url_parameter_tracker',
        'upt_settings_section'
    );

    // Session Data Management Section
    add_settings_section(
        'upt_session_management_section',
        __('Session Data Management', 'upt'),
        'upt_session_management_section_callback',
        'url_parameter_tracker'
    );

    add_settings_field(
        'session_cleanup_interval',
        __('Automatic Session Data Cleanup', 'upt'),
        'upt_session_cleanup_interval_render',
        'url_parameter_tracker',
        'upt_session_management_section'
    );
}

// Settings section callbacks
function upt_settings_section_callback() {
    echo '<p>Configure how URL parameters are tracked and autofilled into forms.</p>';
}

function upt_session_management_section_callback() {
    echo '<p>Manage the session data stored by the plugin to prevent excessive database growth.</p>';
}

// Render functions for settings fields
function upt_tracked_params_render() {
    $options = get_option('upt_settings');
    $tracked_params = isset($options['tracked_params']) ? $options['tracked_params'] : '';
    ?>
    <input type='text' name='upt_settings[tracked_params]' value='<?php echo esc_attr($tracked_params); ?>' style="width: 100%;">
    <p class="description">Enter a comma-separated list of URL parameters you want to track (e.g., utm_source, utm_medium). These parameters will be stored and autofilled into forms based on your mappings.</p>
    <?php
}

function upt_track_all_render() {
    $options = get_option('upt_settings');
    $track_all = isset($options['track_all']) ? $options['track_all'] : 0;
    ?>
    <input type='checkbox' name='upt_settings[track_all]' <?php checked($track_all, 1); ?> value='1'>
    <p class="description">If checked, all URL parameters will be tracked and stored, regardless of the list above.</p>
    <?php
}

function upt_cookie_lifetime_render() {
    $options = get_option('upt_settings');
    $cookie_lifetime = isset($options['cookie_lifetime']) ? intval($options['cookie_lifetime']) : 30;
    ?>
    <input type='number' name='upt_settings[cookie_lifetime]' value='<?php echo esc_attr($cookie_lifetime); ?>' min="1">
    <p class="description">Specify how many days the tracked parameters should be stored in the user's browser cookies. This ensures parameters persist across multiple pages and sessions.</p>
    <?php
}

function upt_param_field_mapping_render() {
    $options = get_option('upt_settings');
    $param_field_mapping = isset($options['param_field_mapping']) ? $options['param_field_mapping'] : '';
    ?>
    <textarea name='upt_settings[param_field_mapping]' rows='10' cols='50' style="width:100%;"><?php echo esc_textarea($param_field_mapping); ?></textarea>
    <p class="description">
        Define how tracked parameters are mapped to form fields. Use one mapping per line in the format:<br><br>
        <code>parameter=field_selector</code> or<br>
        <code>{Label Text}parameter=field_selector</code> for labeled values.<br><br>
        The users current page and referrer page data are stored in the following values:<br>
        <code>current_url</code> and <code>referrer</code><br><br>
        Examples:<br>
        <code>utm_source,utm_medium,utm_campaign=#all-utms</code><br>
        <code>{Source: }utm_source,{Medium: }utm_medium=#utm-parameters</code><br><br>
        In the first example, the values of <code>utm_source</code>, <code>utm_medium</code>, and <code>utm_campaign</code> are inserted into the field with ID <code>all-utms</code>.<br>
        In the second example, labels are added before the values.
    </p>
    <?php
}

function upt_session_cleanup_interval_render() {
    $options = get_option('upt_settings');
    $session_cleanup_interval = isset($options['session_cleanup_interval']) ? $options['session_cleanup_interval'] : 'never';
    ?>
    <select name="upt_settings[session_cleanup_interval]">
        <option value="never" <?php selected($session_cleanup_interval, 'never'); ?>>Never</option>
        <option value="weekly" <?php selected($session_cleanup_interval, 'weekly'); ?>>Weekly</option>
        <option value="monthly" <?php selected($session_cleanup_interval, 'monthly'); ?>>Monthly</option>
        <option value="yearly" <?php selected($session_cleanup_interval, 'yearly'); ?>>Yearly</option>
    </select>
    <p class="description">Choose how often to automatically delete session data to prevent database growth.</p>
    <?php
}

// Handle delete session data action
add_action('admin_post_upt_delete_session_data', 'upt_handle_delete_session_data');
function upt_handle_delete_session_data() {
    if (check_admin_referer('upt_delete_session_data_action', 'upt_delete_session_data_nonce')) {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized user');
        }
        upt_delete_all_session_data();
        // Redirect back to the session data page with a success message
        $redirect_url = add_query_arg('upt_message', 'deleted', admin_url('admin.php?page=upt_session_data'));
        wp_redirect($redirect_url);
        exit;
    } else {
        // Redirect back with an error message
        $redirect_url = add_query_arg('upt_message', 'error', admin_url('admin.php?page=upt_session_data'));
        wp_redirect($redirect_url);
        exit;
    }
}

// Function to delete all session data
function upt_delete_all_session_data() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'upt_sessions';
    $wpdb->query("TRUNCATE TABLE $table_name");
}

// Schedule automatic session data cleanup
register_activation_hook(__FILE__, 'upt_schedule_cleanup');
function upt_schedule_cleanup() {
    $options = get_option('upt_settings');
    $interval = isset($options['session_cleanup_interval']) ? $options['session_cleanup_interval'] : 'never';

    if ($interval !== 'never') {
        if (!wp_next_scheduled('upt_cleanup_event')) {
            wp_schedule_event(time(), $interval, 'upt_cleanup_event');
        }
    }
}

// Hook into plugin deactivation to clear scheduled events
register_deactivation_hook(__FILE__, 'upt_clear_scheduled_cleanup');
function upt_clear_scheduled_cleanup() {
    wp_clear_scheduled_hook('upt_cleanup_event');
}

// Schedule the cleanup event
add_action('upt_cleanup_event', 'upt_cleanup_session_data');
function upt_cleanup_session_data() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'upt_sessions';
    $wpdb->query("TRUNCATE TABLE $table_name");
}

// Update the scheduled event when settings are saved
add_action('update_option_upt_settings', 'upt_update_scheduled_cleanup', 10, 2);
function upt_update_scheduled_cleanup($old_value, $value) {
    upt_clear_scheduled_cleanup();
    $interval = isset($value['session_cleanup_interval']) ? $value['session_cleanup_interval'] : 'never';

    if ($interval !== 'never') {
        wp_schedule_event(time(), $interval, 'upt_cleanup_event');
    }
}

// Add custom intervals for cron schedules
add_filter('cron_schedules', 'upt_add_custom_cron_intervals');
function upt_add_custom_cron_intervals($schedules) {
    $schedules['weekly'] = array(
        'interval' => 604800, // 1 week in seconds
        'display'  => __('Once Weekly')
    );
    $schedules['monthly'] = array(
        'interval' => 2635200, // Approx 1 month in seconds
        'display'  => __('Once Monthly')
    );
    $schedules['yearly'] = array(
        'interval' => 31536000, // 1 year in seconds
        'display'  => __('Once Yearly')
    );
    return $schedules;
}

// Function to display the settings page
function upt_options_page() {
    // Display messages if any
    if (isset($_GET['upt_message'])) {
        if ($_GET['upt_message'] == 'deleted') {
            echo '<div class="updated notice is-dismissible"><p>All session data has been deleted.</p></div>';
        } elseif ($_GET['upt_message'] == 'error') {
            echo '<div class="error notice is-dismissible"><p>Security check failed. Please try again.</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <h1>URL Parameter Tracker Settings</h1>
        <form action='options.php' method='post'>
            <?php
            settings_fields('upt_settings_group');
            do_settings_sections('url_parameter_tracker');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Function to display the session data page
function upt_session_data_page() {
    ?>
    <div class="wrap">
        <h1>User Session Data</h1>
        <p>This table displays the session data collected from users who visited your site with tracked URL parameters.</p>

        <!-- Display messages if any -->
        <?php
        if (isset($_GET['upt_message'])) {
            if ($_GET['upt_message'] == 'deleted') {
                echo '<div class="updated notice is-dismissible"><p>All session data has been deleted.</p></div>';
            } elseif ($_GET['upt_message'] == 'error') {
                echo '<div class="error notice is-dismissible"><p>Security check failed. Please try again.</p></div>';
            }
        }
        ?>

        <!-- Delete All Session Data Form -->
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('upt_delete_session_data_action', 'upt_delete_session_data_nonce'); ?>
            <input type="hidden" name="action" value="upt_delete_session_data">
            <input type="submit" name="delete_session_data" class="button button-secondary" value="Delete All Session Data" onclick="return confirm('Are you sure you want to delete all session data? This action cannot be undone.');">
        </form>

        <br>

        <!-- Session Data Table -->
        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th>User IP</th>
                    <th>Parameters</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch session data from database
                global $wpdb;
                $table_name = $wpdb->prefix . 'upt_sessions';
                $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC");
                if ($results) {
                    foreach ($results as $row) {
                        $parameters = esc_html($row->parameters);
                        $user_ip = esc_html($row->user_ip);
                        $timestamp = esc_html($row->timestamp);
                        echo "<tr>
                                <td style='padding:8px 25px;vertical-align: middle;border-bottom: solid 2px #f8f8f8;'>{$user_ip}</td>
                                <td style='overflow-x: auto;padding:8px 25px;vertical-align: middle;border-bottom: solid 2px #f8f8f8;'><pre>{$parameters}</pre></td>
                                <td style='padding:8px 25px;vertical-align: middle;border-bottom: solid 2px #f8f8f8;'>{$timestamp}</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='3'>No session data available.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Create database table on plugin activation
register_activation_hook(__FILE__, 'upt_create_sessions_table');
function upt_create_sessions_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'upt_sessions';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_ip varchar(100) DEFAULT '' NOT NULL,
        parameters text NOT NULL,
        timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Handle AJAX request to store session data
add_action('wp_ajax_nopriv_upt_store_session_data', 'upt_store_session_data');
add_action('wp_ajax_upt_store_session_data', 'upt_store_session_data');
function upt_store_session_data() {
    if (isset($_POST['sessionData']) && is_array($_POST['sessionData'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'upt_sessions';

        $user_ip = $_SERVER['REMOTE_ADDR'];
        $parameters = wp_json_encode($_POST['sessionData']);

        $wpdb->insert($table_name, array(
            'user_ip'    => $user_ip,
            'parameters' => $parameters,
            'timestamp'  => current_time('mysql'),
        ));

        wp_send_json_success();
    } else {
        wp_send_json_error('No session data provided.');
    }
}