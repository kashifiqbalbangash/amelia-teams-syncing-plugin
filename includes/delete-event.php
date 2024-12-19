<?php
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\User;
	//////////////////////////////////////////////////////////////////////////////////////////////
	function get_before_event_deleted($event)
	{
	        // echo "<pre>";
	    // print_r($event);
	    // exit();

	    // Ensure event data is not empty
	    if (empty($event)) {
	        error_log('Event not found: ' . $event['id']);
	        return;
	    }

	    // Get Access Token
	    $accessToken = getAccessToken();
	    if (!$accessToken) {
	        error_log('Access token retrieval failed.');
	        return;
	    }

	    // Fetch the User ID dynamically (this can be Amelia's employee or event organizer)
	    $userId = get_dynamic_user_id($event);
	    // echo "user id:";
	    // print_r($userId);exit();
	    if (!$userId) {
	        error_log('User ID not found for event: ' . $event['id']);
	        return;
	    }
	    // echo "<pre>";
	    // print_r($userId);
	    // print_r($event);
	    // exit; 


	    if($accessToken){
	        $delete_meeting = deleteTeamsMeeting($accessToken, $userId, $event['id']);
	        if ($delete_meeting) {
	            // echo "Meeting created successfully. Join URL: " . $meeting->getJoinWebUrl();
	            // echo "<pre>";
	            // print_r($meeting);
	            // exit();
	            return $delete_meeting;
	        }
	    } else {
	        echo "having problem!";
	    }
	}
	add_filter('amelia_after_event_deleted', 'get_before_event_deleted', 10, 1);

	function deleteTeamsMeeting($accessToken, $userEmail, $am_eventId) {

	    // Initialize the Graph client
	    $graph = new Graph();

	    if ($graph) {
	        $graph->setAccessToken($accessToken);
	    } else {
	        echo "Graph object is not initialized."; exit();
	    }

	    // URL-encode the user email
	    $userPrincipalName = urlencode($userEmail); 

	    // Check if the user exists
	    try {
	        $userCheckResponse = $graph->createRequest("GET", "/users/$userPrincipalName")
	            ->setReturnType(User::class)
	            ->execute();

	        $userId = $userCheckResponse->getId(); // Get the user ID
	    } catch (Exception $e) {
	        echo "Error fetching user: " . $e->getMessage();
	        return null; // User not found
	    }

	    // Fetch the Outlook Calendar event ID from the Amelia database
	    global $wpdb;
	    $amelia_teams_row = $wpdb->get_row(
	        $wpdb->prepare(
	            "
	            SELECT user_id, joinurl, outlookcalendar_id
	            FROM {$wpdb->prefix}amelia_teams_link 
	            WHERE event_id = %d
	            ",
	            $am_eventId
	        )
	    );

	    // Ensure the event exists
	    if ($amelia_teams_row && $amelia_teams_row->outlookcalendar_id) {
	        $team_eventId = $amelia_teams_row->outlookcalendar_id;
	    } else {
	        echo "Outlook event ID not found for Amelia event ID: $am_eventId";
	        return null; // Exit if no Outlook event found
	    }

	    // Try to delete the event from the user's calendar
	    try {
	        $graph->createRequest("POST", "/users/$userId/events/$team_eventId/cancel")
	            ->attachBody($cancellationMessage) // Optional: add a message to notify attendees
	            ->execute();

	        echo "Event canceled and deleted successfully.";
	        // $graph->createRequest("DELETE", "/users/$userId/events/$team_eventId")
	        //     ->execute();

	        // echo "Event deleted successfully.";
	        return true; // Event deleted successfully
	    } catch (Exception $e) {
	        echo "Error deleting event: " . $e->getMessage();
	        return null; // Return null on error
	    }
	}
?>