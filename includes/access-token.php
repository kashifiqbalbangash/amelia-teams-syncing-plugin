<?php
	// Retrieve Access Token
	function getAccessToken() {
		// usage
		$tenantId = get_option('teams_amelia_tenant_id'); // Your Tenant ID
	    $clientId = get_option('teams_amelia_client_id'); // Your Client ID
	    $clientSecret = get_option('teams_amelia_client_secret'); // Your Client Secret

		if ($tenantId && $clientId && $clientSecret) {
		    $url = "https://login.microsoftonline.com/$tenantId/oauth2/v2.0/token";

		    $response = wp_remote_post($url, array(
		        'body' => array(
		            'grant_type' => 'client_credentials',
		            'client_id' => $clientId,
		            'client_secret' => $clientSecret,
		            'scope' => 'https://graph.microsoft.com/.default',
		        ),
		    ));

		    if (is_wp_error($response)) {
		        error_log('Error getting access token: ' . $response->get_error_message());
		        return false;
		    }

		    $body = wp_remote_retrieve_body($response);
		   
		    $data = json_decode($body, true);
		    return $data['access_token'] ?? false;
		} else {
		    echo 'Please configure your settings in the Teams Integration Settings page.';
		}
		return false;
	}
?>