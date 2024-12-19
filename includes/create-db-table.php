<?php
function create_amelia_teams_link_table() {
    global $wpdb;

    // Get the current WordPress table prefix
    $table_name = $wpdb->prefix . 'amelia_teams_link';

    // Character set and collation
    $charset_collate = $wpdb->get_charset_collate();

    // Define the SQL for creating the table
    $sql = "CREATE TABLE $table_name (
        eventeam_id BIGINT(20) NOT NULL AUTO_INCREMENT,
        event_id BIGINT(20) NOT NULL,
        user_id TEXT NOT NULL,
        joinurl TEXT NOT NULL,
        outlookcalendar_id TEXT NOT NULL,
        PRIMARY KEY (eventeam_id)
    ) $charset_collate;";

    // Include the required file for dbDelta
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Execute the SQL statement to create the table
    dbDelta($sql);

    // Debugging: Log any errors
    if (!empty($wpdb->last_error)) {
        error_log("Database creation error: " . $wpdb->last_error);
    }
}
?>