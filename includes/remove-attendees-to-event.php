<?php
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\Event;
	function get_before_event_booking_deleted($booking, $event){   
	    // echo "<pre>";
	    // print_r($booking);
	    // echo "<pre>";
	    // print_r($event['id']);

	    // Get Access Token
	    $accessToken = getAccessToken();
	    if (!$accessToken) {
	        error_log('Access token retrieval failed.');
	        return $booking;
	    }
	    $attendeesEmailsToRemove = [$booking['customer']['email']]; 

	    removeSpecificAttendeesFromTeamsMeeting($accessToken, $event['id'], $attendeesEmailsToRemove);

	    return $booking;
	}

	add_action('amelia_after_event_booking_deleted', 'get_before_event_booking_deleted', 10, 2);

	function removeSpecificAttendeesFromTeamsMeeting($accessToken, $eventId, $attendeesEmailsToRemove) {
	    // Initialize the Graph client
	    $graph = new Graph();

	    if ($graph) {
	        $graph->setAccessToken($accessToken);
	    } else {
	        echo "Graph object is not initialized.";
	        exit();
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
	            $eventId
	        )
	    );

	    // Ensure the event exists
	    if ($amelia_teams_row && $amelia_teams_row->outlookcalendar_id && $amelia_teams_row->user_id) {
	        $team_eventId = $amelia_teams_row->outlookcalendar_id;
	        $team_userId = $amelia_teams_row->user_id;
	    } else {
	        echo "Outlook event ID not found for Amelia event ID: $eventId";
	        return null; // Exit if no Outlook event found
	    }

	    // Fetch the existing event with its attendees
	    try {
	        $event = $graph->createRequest("GET", "/users/$team_userId/events/$team_eventId")
	            ->setReturnType(Event::class)
	            ->execute();
	    } catch (Exception $e) {
	        echo "Error fetching event: " . $e->getMessage();
	        return null;
	    }

	    // Filter out only the attendees that should be removed
	    $remainingAttendees = array_filter($event->getAttendees(), function($attendee) use ($attendeesEmailsToRemove) {
	        return !in_array($attendee['emailAddress']['address'], $attendeesEmailsToRemove);
	    });

	    // Prepare the updated calendar event details with remaining attendees
	    $calendarEventBody = [
	        'attendees' => array_map(function($attendee) {
	            return [
	                'emailAddress' => [
	                    'address' => $attendee['emailAddress']['address'],
	                    'name' => $attendee['emailAddress']['name']
	                ],
	                'type' => $attendee['type']
	            ];
	        }, $remainingAttendees)
	    ];

	    // Update the event with the remaining attendees
	    try {
	        $graph->createRequest("PATCH", "/users/$team_userId/events/$team_eventId")
	            ->attachBody($calendarEventBody)
	            ->execute();

	        echo "Specific attendees removed successfully.";
	        return true;
	    } catch (Exception $e) {
	        echo "Error removing attendees from event: " . $e->getMessage();
	        return null; // Return null on error
	    }
	}
?>