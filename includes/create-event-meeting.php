<?php
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\User;
use Microsoft\Graph\Model\Event;
use Microsoft\Graph\Model\OnlineMeeting;
	// Create Teams Meeting
	function createTeamsMeeting($accessToken, $userEmail, $subject, $startTime, $endTime, $eventLocation, $event_id) {
	    // echo $eventLocation;exit();
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

	    // Prepare the online meeting details
	    $requestBody = [
	        'subject' => $subject,
	        'startDateTime' => (new DateTime($startTime))->format(DateTime::RFC3339),
	        'endDateTime' => (new DateTime($endTime))->format(DateTime::RFC3339),
	        'participants' => [
	            'organizer' => [
	                'identity' => [
	                    'user' => [
	                        'id' => $userId // Use the user's email
	                    ]
	                ]
	            ]
	        ],
	        'isOnlineMeeting' => true,
	        'onlineMeetingProvider' => 'teamsForBusiness', // Use string
	    ];

	    // Create the online meeting
	    try {
	        $onlineMeetingResponse = $graph->createRequest("POST", "/users/$userId/onlineMeetings") // Use user ID here
	            ->attachBody($requestBody)
	            ->setReturnType(OnlineMeeting::class)
	            ->execute();
	            $joinUrl = $onlineMeetingResponse->getJoinUrl();
	            // echo $joinUrl;
	            // exit();
	    } catch (Exception $e) {
	        echo "Error creating online meeting: " . $e->getMessage();
	        return null; // Return null on error
	    }

	    // Prepare the calendar event details
	    $calendarEventBody = [
	        'subject' => $subject,
	        'start' => [
	            'dateTime' => (new DateTime($startTime))->format('Y-m-d\TH:i:s'),
	            'timeZone' => $timezone,  // Adjust the time zone as needed
	        ],
	        'end' => [
	        	'dateTime' => (new DateTime($endTime))->format('Y-m-d\TH:i:s'),
	            'timeZone' => $timezone, // Adjust the time zone as needed
	        ],
	        'location' => [
	            'displayName' => $eventLocation
	        ],
	        'attendees' => [
	            [
	                'emailAddress' => [
	                    'address' => $userEmail // Add attendees as needed
	                ],
	                'type' => 'required'
	            ]
	        ],
	        'body' => [
	            'contentType' => 'HTML',
	            'content' => "Join the meeting: <a href='" . $joinUrl . "'>Join</a>"
	        ],

	        'isOnlineMeeting' => true,

			'onlineMeetingProvider' => 'teamsForBusiness',

			'allowNewTimeProposals' => true,  // Enables participants to suggest new times

			'sensitivity' => 'normal', // Set to 'normal' or 'private'

			'importance' => 'normal',  // Set to 'low', 'normal', or 'high'

			'reminderMinutesBeforeStart' => 15, // Set reminder time
	    ];

	    // Create the calendar event
	    try {
	        $calendarEventResponse = $graph->createRequest("POST", "/users/$userId/events") // Use user ID here
	            ->attachBody($calendarEventBody)
	            ->setReturnType(Event::class)
	            ->execute();

	            // echo "<pre>event_id:";
	            // print_r($event_id);
	            // echo "<pre>userId:";
	            // print_r($userId);
	            // echo "<br>joinUrl:";
	            // print_r($joinUrl);
	            // echo "<br>calendarEventResponse:";
	            // print_r($calendarEventResponse->getId());
	            // exit;
	            if ($event_id && $userId && $joinUrl && $calendarEventResponse->getId()) {
	                global $wpdb;
	                // Define the table name (ensure proper prefix)
	                $table_name = $wpdb->prefix . 'amelia_teams_link';
	                // Define the data to insert
	                $data = array(
	                    'event_id'            => $event_id,
	                    'user_id'             => $userId,
	                    'joinurl'             => $joinUrl,
	                    'outlookcalendar_id'  => $calendarEventResponse->getId()
	                );

	                // Define the format for each field (bigint, text)
	                $format = array('%d', '%s', '%s', '%s');

	                // Insert the data
	                $wpdb->insert($table_name, $data, $format);

	                // Check if the insert was successful
	                if ($wpdb->insert_id) {
	                    echo 'Record inserted successfully with ID: ' . $wpdb->insert_id;
	                } else {
	                    echo 'Failed to insert record.';
	                }
	            }
	        return $calendarEventResponse; // Return the response for further processing
	    } catch (Exception $e) {
	        echo "Error creating calendar event: " . $e->getMessage();
	        return null; // Return null on error
	    }
	}

	// Handle Event Creation
	add_action('amelia_after_event_added', 'handle_after_event_created', 10, 1);

	function handle_after_event_created($event_id) {
	    // Fetch event details
	    $event = apply_filters('amelia_get_event', $event_id);

	    if (is_array($event_id)) {
	        $event_id = $event_id['id'];
	    } else {
	        $event_id = ($event_id?$event_id:'');
	    }
	    // Ensure event data is not empty
	    if (empty($event)) {
	        error_log('Event not found: ' . $event_id);
	        return;
	    }

	    // Access the event details
	    $eventName = $event['name'] ?? 'Unnamed Event';
	    $periods = $event['periods'][0] ?? null;
	    
	    if ($periods) {
	        $startTime = $periods['periodStart'] ?? null;
	        $endTime = $periods['periodEnd'] ?? null;
	    } else {
	        error_log('No periods found for event: ' . $event_id);
	        return;
	    }

	    // Build email subject
	    $subject = 'Event: ' . $eventName;

	    // Get Access Token
	    $accessToken = getAccessToken();
	    if (!$accessToken) {
	        error_log('Access token retrieval failed.');
	        return;
	    }

	    // Fetch the User ID dynamically (this can be Amelia's employee or event organizer)
	    $userId = get_dynamic_user_id($event_id);
	    if (!$userId) {
	        error_log('User ID not found for event: ' . $event_id);
	        return;
	    }
	    // echo "<pre>";
	    // print_r($userId);
	    // print_r($event);

	    // exit;
	    // Get the locationId and location
	    $locationId = isset($event['locationId']) ? $event['locationId'] : null;
	    $location = isset($event['location']) ? $event['location'] : null;
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
	        $meeting = createTeamsMeeting($accessToken, $userId, $subject, $startTime, $endTime, $event_location, $event_id);
	        if ($meeting) {
	            // echo "Meeting created successfully. Join URL: " . $meeting->getJoinWebUrl();
	            // echo "<pre>";
	            // print_r($meeting);exit();
	            // return $meeting;
	        }
	    } else {
	        echo "having problem!";
	    }
	    // Create Teams Meeting
	    

	    if ($meeting) {
	        // Retrieve the meeting link
	        $meetingLink = $meeting->joinUrl ?? '';
	        if ($meetingLink) {
	            // Add meeting link to event metadata
	            $event['settings']['teams_meeting_link'] = $meetingLink;
	        } else {
	            error_log('Meeting created but no join URL found for event: ' . $event_id);
	        }
	    }
	}
?>