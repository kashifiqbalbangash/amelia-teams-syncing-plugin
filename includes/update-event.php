<?php
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\Event;
use Microsoft\Graph\Model\User;
	function handle_after_event_updated($data)
	{   
	    // echo "<pre>";
	    // print_r($data);
	    // exit();

	    // Ensure event data is not empty
	    if (empty($data)) {
	        error_log('Event not found: ' . $data['id']);
	        // return;
	    }

	    // Access the event details
	    $eventName = $data['name'] ?? 'Unnamed Event';
	    $periods = $data['periods'][0] ?? null;
	    
	    if ($periods) {
	        $startTime = $periods['periodStart'] ?? null;
	        $endTime = $periods['periodEnd'] ?? null;
	    } else {
	        error_log('No periods found for event: ' . $data['id']);
	        // return;
	    }

	    // Build email subject
	    $subject = 'Event: ' . $eventName;

	    // Get Access Token
	    $accessToken = getAccessToken();
	    if (!$accessToken) {
	        error_log('Access token retrieval failed.');
	        // return;
	    }

	    // Fetch the User ID dynamically (this can be Amelia's employee or event organizer)
	    $userId = get_dynamic_user_id($data['id']);
	    // echo "user id:";
	    // print_r($userId);exit();
	    if (!$userId) {
	        error_log('User ID not found for event: ' . $data['id']);
	        // return;
	    }
	    // echo "<pre>accessToken";
	    // print_r($accessToken);
	    // echo "<pre>userId";
	    // print_r($userId);
	    // echo "<pre>subject";
	    // print_r($subject);
	    // echo "<pre>startTime";
	    // print_r((new DateTime($startTime))->format(DateTime::RFC3339));
	    // echo "<pre>endTime";
	    // print_r($endTime);
	    // echo "<pre>data-id";
	    // print_r($data['id']);
	    // $timezone = (get_option('teams_amelia_meeting_timezone')!='') ? get_option('teams_amelia_meeting_timezone') :'Eastern Standard Time';
	    // echo "<pre>timezone";
	    // print_r($timezone);
	    // exit; 

	    // Get the locationId and location
	    $locationId = isset($data['locationId']) ? $data['locationId'] : null;
	    $location = isset($data['location']) ? $data['location'] : $data['customLocation'];
	    if ($locationId) {
	        global $wpdb;
	        $am_location = $wpdb->get_row(
	            $wpdb->prepare(
	                "
	                SELECT name, address
	                FROM {$wpdb->prefix}amelia_locations 
	                WHERE id = %d
	                ",
	                $locationId
	            )
	        );
	        if ($am_location->name) {
	            $event_location = ($am_location->name?$am_location->name:'');
	        } else {
	            $event_location = ($am_location->address?$am_location->address:'');
	        }
	    } else {
	        $event_location = ($location?$location:'No Location Mentioned');
	    }
	    
	    if($accessToken){
	        $meeting = updateTeamsMeeting($accessToken, $userId, $subject, $startTime, $endTime, $data['id'], $event_location);
	        if ($meeting) {
	            // echo "Meeting created successfully. Join URL: " . $meeting->getJoinWebUrl();
	            // echo "<pre>";
	            // print_r($meeting);
	            // exit();
	            return $meeting;
	        }
	    } else {
	        echo "having problem!";
	    }
	    // Create Teams Meeting
	    

	    if ($meeting) {
	        // Retrieve the meeting link
	        $meetingLink = $meeting['joinUrl'] ?? '';

	        if ($meetingLink) {
	            // Add meeting link to event metadata
	            $data['settings']['teams_meeting_link'] = $meetingLink;
	            
	            // Optionally, notify attendees with the meeting link
	            if (!empty($data['providers'])) {
	                send_notification_to_attendees($data['providers'], $meetingLink);
	            }
	        } else {
	            error_log('Meeting created but no join URL found for event: ' . $data['id']);
	        }
	    }
	}

	add_action('amelia_after_event_updated', 'handle_after_event_updated', 10, 1);

	// Update Teams Meeting
	function updateTeamsMeeting($accessToken, $userEmail, $subject, $startTime, $endTime, $am_eventId, $event_location = null) {

	    $graph = new Graph();
	    $timezone = (get_option('teams_amelia_meeting_timezone')!='') ? get_option('teams_amelia_meeting_timezone') :'Eastern Standard Time';
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

	    $attendeeDetails = getAttendeeDetailsFromBooking($am_eventId);
	    $attendeesEmails = [];
	    foreach ($attendeeDetails as $attendee) {
	        $attendeesEmails[] = [
	            'emailAddress' => [
	                'address' => $attendee['email'],
	                'name'    => $attendee['firstName'] . ' ' . $attendee['lastName']
	            ],
	            'type' => 'required'
	        ];
	    }

	    // Prepare the updated calendar event details
	    $calendarEventBody = [
	        'subject' => $subject, // New subject for the event
	        'start' => [
	            'dateTime' => (new DateTime($startTime))->format('Y-m-d\TH:i:s'),
	            'timeZone' => $timezone, // Adjust the time zone as needed
	        ],
	        'end' => [
	        	'dateTime' => (new DateTime($endTime))->format('Y-m-d\TH:i:s'),
	            'timeZone' => $timezone, // Adjust the time zone as needed
	        ],
	        'location' => [
	            'displayName' => $event_location // Update location if necessary
	        ],
	        // Optional: Update attendees if needed
	        'attendees' => $attendeesEmails,
	        // 'body' => [
	        //     'contentType' => 'HTML',
	        //     'content' => "Updated meeting details: <a href='" . $joinUrl . "'>Join</a>"
	        // ],
	    ];


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
	        if ($amelia_teams_row->outlookcalendar_id) {
	            $team_eventId = $amelia_teams_row->outlookcalendar_id;
	        } else {
	            $team_eventId = '';
	        }
	        // echo 'userId '.$userId;
	        // echo 'team_eventId '.$team_eventId;exit();
	    // Update the calendar event
	    try {
	        $calendarEventResponse = $graph->createRequest("PATCH", "/users/$userId/events/$team_eventId")
	            ->attachBody($calendarEventBody)
	            ->setReturnType(Event::class)
	            ->execute();

	        return $calendarEventResponse; // Return the response for further processing
	    } catch (Exception $e) {
	        echo "Error updating calendar event: " . $e->getMessage();
	        return null; // Return null on error
	    }

	}
?>