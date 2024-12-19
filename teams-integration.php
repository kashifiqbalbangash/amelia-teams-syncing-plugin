
<?php
/*
Plugin Name: Teams Integration
Description: Integrates Microsoft Teams with Amelia for events handling.
Version: 1.0
Author: Bilal Khalid
*/
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\Event;
use Microsoft\Graph\Model;
use Microsoft\Graph\Model\ItemBody;
use Microsoft\Graph\Model\BodyType;
use Microsoft\Graph\Model\DateTimeTimeZone;
use Microsoft\Graph\Model\Location;
use Microsoft\Graph\Model\Attendee;
use Microsoft\Graph\Model\EmailAddress;
use Microsoft\Graph\Model\AttendeeType;
use Microsoft\Graph\Model\OnlineMeetingProviderType;
use Microsoft\Graph\Model\OnlineMeeting;

// Plugin activation hook
register_activation_hook(__FILE__, 'create_amelia_teams_link_table');

// Dynamically fetch the user ID based on the event data
function get_dynamic_user_id($event) {
    // echo "<pre>";
    // print_r($event);
    if (is_array($event)) {
        // Assuming you want the externalId or email to be the userId for creating the meeting
        foreach ($event['providers'] as $provider) {
            if (!empty($provider['outlookCalendar']['token'])) {
                // If there's an Outlook calendar token, this provider likely uses Outlook, return the externalId or email
                return $provider['email']; // You can also return $provider['externalId'] if you use it as userId
            } elseif(sizeof($event['providers'])==1) {

                global $wpdb;
                $organizer_email = $wpdb->get_var(
                    $wpdb->prepare(
                        "
                        SELECT am_user.email 
                        FROM {$wpdb->prefix}amelia_users as am_user 
                        LEFT JOIN {$wpdb->prefix}amelia_events as am_events 
                        ON am_events.organizerId = am_user.id 
                        WHERE am_events.id = %d
                        ",
                        $event['id']
                    )
                );
                if ($organizer_email) {
                    return $organizer_email;
                } else {
                    return $provider['email'];
                }
            }
        }
    } else {
        $current_user_id = get_current_user_id();
        global $wpdb;
        $organizer_email = $wpdb->get_var(
            $wpdb->prepare(
                "
                SELECT am_user.email 
                FROM {$wpdb->prefix}amelia_users as am_user 
                WHERE am_user.externalId = %d
                ",
                $current_user_id
            )
        );

        return $organizer_email;
    }
    // Default return value if no user with Outlook calendar is found
    return null;
}
// Hook into the action links for your plugin
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'my_plugin_action_links');
function my_plugin_action_links($links) {
    $settings_link = '<a href="admin.php?page=teams_amelia_settings">Settings</a>';
    array_unshift($links, $settings_link); // Adds the settings link to the beginning of the array

    $documentation_link = '<a href="admin.php?page=teams_integration_main">Documentation</a>';
    array_unshift($links, $documentation_link); // Adds the settings link to the beginning of the array
    $links = array_reverse($links);
    return $links;
}

include plugin_dir_path(__FILE__) . 'includes/create-db-table.php';
require_once plugin_dir_path(__FILE__) . 'includes/access-token.php';
require_once plugin_dir_path(__FILE__) . 'includes/create-event-meeting.php';
require_once plugin_dir_path(__FILE__) . 'includes/update-event.php';
require_once plugin_dir_path(__FILE__) . 'includes/delete-event.php';
require_once plugin_dir_path(__FILE__) . 'includes/add-attendees-to-event.php';
require_once plugin_dir_path(__FILE__) . 'includes/remove-attendees-to-event.php';
require_once plugin_dir_path(__FILE__) . 'includes/send-notification-to-attendees.php';
require_once plugin_dir_path(__FILE__) . 'includes/setting-options.php';