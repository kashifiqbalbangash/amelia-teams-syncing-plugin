<?php
use Microsoft\Graph\Graph;
    //////////////////////////////////////////////////////////////////////////////////////
    function get_data_after_event_booking_saved($booking, $reservation)
    {
        // echo "<pre>";
        // print_r($booking['customer']['email']);
        // print_r($booking);
        // print_r($reservation);
        // exit();
        $eventId = $reservation['periods'][0]['eventId'];
        
        // echo "<pre>userId";
        // print_r($userId);
        // echo "<pre>eventId";
        // print_r($eventId);
        // echo "<pre>event";
        // print_r($event);
        // exit; 
        
        // Ensure event data is not empty
        if (empty($eventId)) {
            error_log('Event not found');
            return $booking;
        }

        // Get Access Token
        $accessToken = getAccessToken();
        if (!$accessToken) {
            error_log('Access token retrieval failed.');
            return $booking;
        }

        // // Fetch the User ID dynamically (this can be Amelia's employee or event organizer)
        // $userId = get_dynamic_user_id($eventId);
        // // echo "user id:";
        // // print_r($userId);exit();


        // if (!$userId) {
        //     error_log('User ID not found for event: ' . $eventId);
        //     return;
        // }
        
        $last_attendee_email = (!empty($booking['customer']['email']) ? $booking['customer']['email'] : '');

        if($accessToken){
            // Extract the event ID and attendee emails from the booking data
            // $eventId = getEventIdFromAmeliaBooking($booking); // Replace with your logic to get the Teams event ID
            $attendeeDetails = getAttendeeDetailsFromBooking($eventId); // Replace with your logic to get attendee emails

    // echo "<pre>";
    // print_r($attendeeDetails);exit();
            // Prepare attendee email addresses and names
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
            // echo "<pre>attendeesEmails";
            // print_r($last_attendee_email);
            // print_r($reservation);
            // exit;
            // Add attendees to the Teams event
            $addAttendees = addAttendeesToTeamsMeeting($accessToken, $eventId, $attendeesEmails, $last_attendee_email, $reservation);

            if ($addAttendees) {
                // echo "Attendees added successfully";
                return $booking;
            }
        } else {
            echo "having problem!";
            return $booking;
        }
        
        return $booking;
    }

    add_filter('amelia_after_event_booking_saved', 'get_data_after_event_booking_saved', 10, 2);


    function getAttendeeDetailsFromBooking($eventId) {
        
        global $wpdb;
        $amelia_attendees_row = $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT au.firstName, au.lastName, au.email
                FROM {$wpdb->prefix}amelia_events AS ae
                JOIN {$wpdb->prefix}amelia_events_periods AS aep ON ae.id = aep.eventId 
                JOIN {$wpdb->prefix}amelia_customer_bookings_to_events_periods AS acbep ON aep.id = acbep.eventPeriodId
                JOIN {$wpdb->prefix}amelia_customer_bookings AS acb ON acbep.customerBookingId = acb.id
                JOIN {$wpdb->prefix}amelia_users AS au ON acb.customerId = au.id
                WHERE ae.id = %d
                ",
                $eventId
            )
        );

        $attendees = array();
        if ($amelia_attendees_row) {
            foreach ($amelia_attendees_row as $key => $value) {
                // print_r($key);
                // print_r($value);
                $attendees[$key] = [
                    'firstName' => ($value->firstName ? $value->firstName : ''),
                    'lastName'  => ($value->lastName ? $value->lastName : ''),
                    'email'     => ($value->email ? $value->email : '')
                ];
            }
        }

        // Add more logic if there are multiple attendees or custom fields with more details
        return $attendees;
    }


    function addAttendeesToTeamsMeeting($accessToken, $eventId, $attendeesEmails, $last_attendee_email = null, $reservation = []) {
        // Initialize the Graph client
        $graph = new Graph();

        if ($graph) {
            $graph->setAccessToken($accessToken);
        } else {
            echo "Graph object is not initialized."; exit();
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
            $team_meetUrl = $amelia_teams_row->joinurl;

            // Optionally, notify attendees with the meeting link
            if (!empty($reservation) && $last_attendee_email != null) {
                send_notification_to_attendees($last_attendee_email, $reservation['periods'], $team_meetUrl, $reservation['name']);
            }

        } else {
            echo "Outlook event ID not found for Amelia event ID: $am_eventId";
            return null; // Exit if no Outlook event found
        }

        // Prepare the updated calendar event details with attendees
        $calendarEventBody = [
            'attendees' => $attendeesEmails
        ];

        // echo "<pre>userId";
        // print_r($team_userId);
        // echo "<pre>team_eventId";
        // print_r($team_eventId);
        // echo "<pre>calendarEventBody";
        // print_r($calendarEventBody);
        // exit; 

        // Update the calendar event with attendees
        try {
            $graph->createRequest("PATCH", "/users/$team_userId/events/$team_eventId")
                ->attachBody($calendarEventBody)
                ->execute();

            // echo "Attendees added successfully.";
            return true; // Attendees added successfully
        } catch (Exception $e) {
            echo "Error adding attendees to event: " . $e->getMessage();
            return null; // Return null on error
        }
    }
?>