<?php
	// Send notification to attendees (assumed function)

	function send_notification_to_attendees($last_attendee_email, $periods, $team_meetUrl, $event_name) {
	    $subject = "You're Added to a Virtual Class!";
		// Custom headers to set "From" name and email address
		$headers = array(
		    'Content-Type: text/html; charset=UTF-8',
		    'From: ' . get_bloginfo('name'),
		    // 'Reply-To: support@yourdomain.com', // Optional, for a valid reply-to address
		    'X-Mailer: PHP/' . phpversion(), // Identifies the PHP mailer version
		    'X-Priority: 3 (Normal)', // Sets the priority of the email
		    'X-MSMail-Priority: Normal', // Sets the priority for MS Outlook clients
		);


		$period_content = '';
		foreach ($periods as $period) {
	        if ($period) { 
	            $period_content = '<p><strong>Start at:</strong> '.$period['periodStart'].'</p>';
	            $period_content .= '<p><strong>End at:</strong> '.$period['periodEnd'].'</p><br>';
	        }
	    }

		$message = '
		<!DOCTYPE html>
		<html>
		<head>
		    <style>
		        /* General styling */
		        body {
		            font-family: Arial, sans-serif;
		            color: #4a4a4a;
		            line-height: 1.6;
		            background-color: #f4f7f9;
		            margin: 0;
		            padding: 0;
		        }
		        .container {
		            max-width: 600px;
		            margin: 0 auto;
		            background-color: #ffffff;
		            padding: 20px;
		            border-radius: 8px;
		            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
		        }
		        h2 {
		            color: #0078d4;
		            font-size: 24px;
		            margin-top: 0;
		        }
		        p {
		            margin: 0 0 15px;
		            font-size: 16px;
		        }
		        .details {
		            background-color: #e6f4ff;
		            padding: 15px;
		            border-radius: 8px;
		            margin: 20px 0;
		        }
		        .details p {
		            margin: 5px 0;
		            font-size: 16px;
		        }
		        .button-container {
		            text-align: center;
		            margin-top: 25px;
		        }
		        .button {
		            display: inline-block;
		            background-color: #0078d4;
		            color: #ffffff;
		            padding: 12px 24px;
		            border-radius: 5px;
		            text-decoration: none;
		            font-weight: bold;
		            font-size: 16px;
		            transition: background-color 0.3s;
		        }
		        .button:hover {
		            background-color: #005a9e;
		        }
		        .footer {
		            text-align: center;
		            font-size: 12px;
		            color: #9a9a9a;
		            margin-top: 20px;
		        }
		    </style>
		</head>
		<body>
		    <div class="container">
		        <h2>You are Invited to a a Virtual Class '.($event_name?$event_name:"").'!</h2>
		        <p>Hello Name,</p>
		        <p>Thank you for registering for our class! You have been added as an attendee to the following Class. Here are the details:</p>
		        
		        <div class="details">
		            <p><strong>Meeting Title:</strong> '.($event_name?$event_name:"").'</p>
		            <div><strong>Date & Time:</strong>'.$period_content.'</div>
		            <p><strong>Time Zone:</strong> '.(get_option('teams_amelia_meeting_timezone')!='' ? get_option('teams_amelia_meeting_timezone') :"Eastern Standard Time").'</p>
		        </div>

		        <p>To join the meeting, simply click the button below at the scheduled time:</p>

		        <div class="button-container">
		            <a href="'.$team_meetUrl.'" class="button">Join Meeting</a>
		        </div>
		    </div>
		</body>
		</html>
		';
 			/*<p>If you have any questions or need further assistance, feel free to reach out to us. We look forward to seeing you there!</p><div class="footer">
		        <p>Best regards,<br>The Team</p>
		        <p>📧 Contact us at: support@example.com</p>
		    </div>*/
			// echo "<pre>attendeesEmails";
            // print_r($last_attendee_email);
            // print_r($subject);
            // print_r($headers);
            // print_r($message);
            // exit;

		wp_mail($last_attendee_email, $subject, $message, $headers);
	}
?>