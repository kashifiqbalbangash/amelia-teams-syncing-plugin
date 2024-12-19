<?php
	add_action('admin_menu', 'teams_amelia_add_admin_menu');

	function teams_amelia_add_admin_menu() {
	    // Main menu
	    add_menu_page(
	        'Teams Integration', // Main page title
	        'Teams Integration', // Main menu title
	        'manage_options', // Capability
	        'teams_integration_main', // Main menu slug
	        'teams_amelia_main_page', // Main callback function (optional dashboard or intro page)
	        'dashicons-admin-links', // Icon
	        20 // Position
	    );

	    // Add Settings submenu under Teams Integration
	    add_submenu_page(
	        'teams_integration_main', // Parent slug, which links to Teams Integration
	        'Settings', // Page title for settings
	        'Settings', // Submenu title
	        'manage_options', // Capability
	        'teams_amelia_settings', // Submenu slug
	        'teams_amelia_settings_page' // Callback function for settings page
	    );
	}

	function teams_amelia_settings_page() {
	?>
	    <div class="wrap" style="font-family: Arial, sans-serif; max-width: 700px; margin: 0 auto;">
	        <h1 style="color: #0073aa; font-size: 28px; border-bottom: 2px solid #0073aa; padding-bottom: 10px;">Teams Integration Settings</h1>
	        <p style="font-size: 16px; color: #555; margin-bottom: 20px;">Configure the necessary credentials below to enable Microsoft Teams integration with Amelia.</p>
	        
	        <form method="post" action="options.php" style="background: #f7f7f7; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
	            <?php
	            settings_fields('teams_amelia_options_group');
	            do_settings_sections('teams_amelia_settings');
	            submit_button('Save Settings', 'primary', 'submit', true, [
	                'style' => 'background-color: #0073aa; border-color: #0073aa; color: #fff; font-size: 16px; padding: 10px 20px;'
	            ]);
	            ?>
	        </form>
	    </div>

	    <style>
	        .wrap input[type="text"], .wrap input[type="password"] {
	            width: 100%;
	            padding: 10px;
	            margin-top: 8px;
	            margin-bottom: 20px;
	            border: 1px solid #ccc;
	            border-radius: 5px;
	            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
	        }
	        
	        .wrap label {
	            font-weight: bold;
	            color: #333;
	            font-size: 16px;
	        }
	        
	        .wrap .form-table th {
	            padding: 10px 0;
	            font-size: 16px;
	        }
	    </style>
	    <?php
	}


	add_action('admin_init', 'teams_amelia_register_settings');

	function teams_amelia_register_settings() {
	    register_setting('teams_amelia_options_group', 'teams_amelia_tenant_id');
	    register_setting('teams_amelia_options_group', 'teams_amelia_client_id');
	    register_setting('teams_amelia_options_group', 'teams_amelia_client_secret');
	    register_setting('teams_amelia_options_group', 'teams_amelia_meeting_timezone');

	    add_settings_section('teams_amelia_main_section', 'Main Settings', null, 'teams_amelia_settings');

	    add_settings_field('tenant_id', 'Tenant ID', 'teams_amelia_tenant_id_callback', 'teams_amelia_settings', 'teams_amelia_main_section');
	    add_settings_field('client_id', 'Client ID', 'teams_amelia_client_id_callback', 'teams_amelia_settings', 'teams_amelia_main_section');
	    add_settings_field('client_secret', 'Client Secret', 'teams_amelia_client_secret_callback', 'teams_amelia_settings', 'teams_amelia_main_section');
	    add_settings_field('meeting_timezone', 'Meeting Timezone', 'teams_amelia_meeting_timezone_callback', 'teams_amelia_settings', 'teams_amelia_main_section');
	}

	function teams_amelia_tenant_id_callback() {
	    $tenant_id = get_option('teams_amelia_tenant_id');
	    echo '<input type="text" name="teams_amelia_tenant_id" value="' . esc_attr($tenant_id) . '" />';
	}

	function teams_amelia_client_id_callback() {
	    $client_id = get_option('teams_amelia_client_id');
	    echo '<input type="text" name="teams_amelia_client_id" value="' . esc_attr($client_id) . '" />';
	}

	function teams_amelia_client_secret_callback() {
	    $client_secret = get_option('teams_amelia_client_secret');
	    echo '<input type="password" name="teams_amelia_client_secret" value="' . esc_attr($client_secret) . '" />';
	}

	function teams_amelia_meeting_timezone_callback() {
	    $meeting_timezone = get_option('teams_amelia_meeting_timezone');
	    echo '<input type="text" name="teams_amelia_meeting_timezone" value="' . esc_attr($meeting_timezone) . '" />';
	}

	function teams_amelia_main_page() {
	    echo '<div class="wrap" style="font-family: Arial, sans-serif;">';
	    echo '<h1 style="color: #0073aa; font-size: 28px; border-bottom: 2px solid #0073aa; padding-bottom: 10px;">Teams Integration - Setup Documentation</h1>';
	    echo '<p style="font-size: 16px; color: #555;">This document provides step-by-step instructions to configure your Microsoft Azure account, acquire the necessary keys, and set up permissions for integrating Microsoft Teams events with Amelia in WordPress.</p>';

	    echo '<h2 style="color: #444; font-size: 22px; margin-top: 30px;">1. Azure Account Setup</h2>';
	    echo '<div style="background: #f7f7f7; padding: 15px; border-radius: 8px; border-left: 4px solid #0073aa;">';
	    echo '<p><strong>Step 1: Create an Azure Account</strong></p>';
	    echo '<ol style="margin-left: 20px;">
	            <li>Visit the <a href="https://portal.azure.com" target="_blank" style="color: #0073aa; text-decoration: none;">Azure Portal</a>.</li>
	            <li>If you don’t have an account, sign up for a new one.</li>
	            <li>Select a subscription plan (the free tier can be used for initial setup and testing).</li>
	          </ol>';
	    echo '</div>';

	    echo '<h2 style="color: #444; font-size: 22px; margin-top: 30px;">2. Application Registration in Azure Active Directory</h2>';
	    echo '<div style="background: #f7f7f7; padding: 15px; border-radius: 8px; border-left: 4px solid #0073aa;">';
	    echo '<p><strong>Step 2: Register the Application</strong></p>';
	    echo '<ol style="margin-left: 20px;">
	            <li>In the Azure Portal, go to <strong>Azure Active Directory</strong>.</li>
	            <li>Click on <strong>App registrations</strong> and then <strong>+ New registration</strong>.</li>
	            <li>Fill in the registration details:
	                <ul style="list-style: square; margin-left: 20px;">
	                    <li><strong>Name:</strong> Enter a name, e.g., <em>TeamsAmeliaIntegration</em>.</li>
	                    <li><strong>Supported account types:</strong> Choose Single tenant (or Multitenant if needed).</li>
	                    <li><strong>Redirect URI:</strong> If applicable, add your redirect URI (e.g., https://yourdomain.com/callback).</li>
	                </ul>
	            </li>
	            <li>Click <strong>Register</strong> to create the application.</li>
	          </ol>';
	    echo '</div>';

	    echo '<h2 style="color: #444; font-size: 22px; margin-top: 30px;">3. Generate Client ID, Tenant ID, and Client Secret</h2>';
	    echo '<div style="background: #f7f7f7; padding: 15px; border-radius: 8px; border-left: 4px solid #0073aa;">';
	    echo '<p><strong>Step 3: Retrieve the Client ID and Tenant ID</strong></p>';
	    echo '<ol style="margin-left: 20px;">
	            <li>After registration, you’ll be redirected to the application’s <strong>Overview</strong> page.</li>
	            <li>Copy the <strong>Application (client) ID</strong> and <strong>Directory (tenant) ID</strong> – these will be needed in the plugin settings.</li>
	          </ol>';

	    echo '<p><strong>Step 4: Create a Client Secret</strong></p>';
	    echo '<ol style="margin-left: 20px;">
	            <li>In the left sidebar, go to <strong>Certificates & Secrets</strong>.</li>
	            <li>Click <strong>+ New client secret</strong>.</li>
	            <li>Add a description and set an expiry period.</li>
	            <li>Click <strong>Add</strong> and copy the generated client secret value. Save this securely as it will be required in the plugin settings.</li>
	          </ol>';
	    echo '</div>';

	    echo '<h2 style="color: #444; font-size: 22px; margin-top: 30px;">4. Configure API Permissions</h2>';
	    echo '<div style="background: #f7f7f7; padding: 15px; border-radius: 8px; border-left: 4px solid #0073aa;">';
	    echo '<p>For full functionality with Microsoft Teams and Amelia, you must grant the following permissions to your registered application:</p>';

	    echo '<p><strong>Step 5: Add Microsoft Graph API Permissions</strong></p>';
	    echo '<ul style="list-style-type: disc; margin-left: 20px;">
	            <li><strong>Calendars Permissions</strong>
	                <ul style="list-style: square; margin-left: 20px;">
	                    <li>Calendars.Read (Delegated): Read user calendars.</li>
	                    <li>Calendars.Read (Application): Read calendars in all mailboxes.</li>
	                    <li>Calendars.ReadWrite (Application): Read and write calendars in all mailboxes.</li>
	                </ul>
	            </li>
	            <li><strong>Device Management Permissions</strong>
	                <ul style="list-style: square; margin-left: 20px;">
	                    <li>DeviceManagementApps.ReadWrite.All (Application): Read and write Intune apps.</li>
	                    <li>DeviceManagementConfiguration.ReadWrite.All (Application): Read and write Microsoft Intune device configuration and policies.</li>
	                    <li>DeviceManagementManagedDevices.ReadWrite.All (Application): Read and write Intune devices.</li>
	                    <li>DeviceManagementServiceConfig.ReadWrite.All (Application): Read and write Microsoft Intune configuration.</li>
	                </ul>
	            </li>
	            <li><strong>Directory Permissions</strong>
	                <ul style="list-style: square; margin-left: 20px;">
	                    <li>Directory.Read.All (Delegated): Read directory data.</li>
	                    <li>Directory.ReadWrite.All (Application): Read and write directory data.</li>
	                </ul>
	            </li>
	            <li><strong>Online Meetings Permissions</strong>
	                <ul style="list-style: square; margin-left: 20px;">
	                    <li>online_access (Delegated): Maintain access to data you have given it access to.</li>
	                    <li>OnlineMeetings.Read (Delegated): Read user’s online meetings.</li>
	                    <li>OnlineMeetings.ReadWrite (Delegated): Read and create user’s online meetings.</li>
	                    <li>OnlineMeetings.ReadWrite.All (Application): Read and create online meetings for all users.</li>
	                </ul>
	            </li>
	            <li><strong>User Profile Permissions</strong>
	                <ul style="list-style: square; margin-left: 20px;">
	                    <li>User.Read (Delegated): Sign in and read user profile.</li>
	                    <li>User.Read.All (Delegated): Read all users\' full profiles.</li>
	                    <li>User.ReadWrite (Delegated): Read and write access to user profile.</li>
	                    <li>User.ReadWrite.All (Delegated): Read and write all users\' full profiles.</li>
	                </ul>
	            </li>
	          </ul>';

	    echo '<p><strong>Step 6: Grant Admin Consent</strong></p>';
	    echo '<p>After adding each permission, click <strong>Grant admin consent for [Your Organization]</strong> to allow organization-wide access.</p>';
	    echo '</div>';

	    echo '<h2 style="color: #444; font-size: 22px; margin-top: 30px;">5. Adding Configuration to the Plugin</h2>';
	    echo '<div style="background: #f7f7f7; padding: 15px; border-radius: 8px; border-left: 4px solid #0073aa;">';
	    echo '<p>Once you have your Client ID, Tenant ID, and Client Secret, go to the <strong>Teams Integration Settings</strong> page in the WordPress admin area and enter the values.</p>';
	    echo '</div>';

	    echo '<h2 style="color: #444; font-size: 22px; margin-top: 30px;">6. Testing the Integration</h2>';
	    echo '<div style="background: #f7f7f7; padding: 15px; border-radius: 8px; border-left: 4px solid #0073aa;">';
	    echo '<ol style="margin-left: 20px;">
	            <li>Create a test meeting in Amelia and check if the integration successfully generates a Microsoft Teams meeting link.</li>
	            <li>Ensure that the meeting link is accessible and functions as expected for participants.</li>
	          </ol>';
	    echo '</div>';

	    echo '<h2 style="color: #444; font-size: 22px; margin-top: 30px;">7. Troubleshooting</h2>';
	    echo '<div style="background: #f7f7f7; padding: 15px; border-radius: 8px; border-left: 4px solid #d63638;">';
	    echo '<p>If you encounter issues, check the following:</p>';
	    echo '<ul style="list-style-type: disc; margin-left: 20px;">
	            <li><strong>Permissions:</strong> Verify that all necessary permissions have been granted and admin consent is provided.</li>
	            <li><strong>Configuration:</strong> Confirm that Client ID, Tenant ID, and Client Secret are correctly entered.</li>
	            <li><strong>Access Token:</strong> Ensure that your access token is valid. If expired, implement a token refresh strategy.</li>
	          </ul>';
	    echo '</div>';

	    echo '</div>'; // End of wrap
	}

?>