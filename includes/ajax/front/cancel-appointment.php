<?php

$appt_id = esc_html( $_POST['appt_id'] );
$appt = get_post( $appt_id );
$cal_taxonomy = wp_get_post_terms( $appt_id, 'booked_custom_calendars');
//returns a single taxonomy encapsulated in an array
//take the term_taxonomy_id field from the first one
if(is_array($cal_taxonomy) && count($cal_taxonomy)){
    $calendar_id = $cal_taxonomy[0]->term_taxonomy_id;
}
else{
    //otherwise false
    $calendar_id = false;
}

$appt_author = $appt->post_author;

$timeslot = get_post_meta( $appt_id,'_appointment_timeslot',true);
$timestamp = get_post_meta( $appt_id,'_appointment_timestamp',true);
$timeslots = explode('-',$timeslot);
$timestamp_start = strtotime(date_i18n('Y-m-d',$timestamp).' '.$timeslots[0]);
$current_timestamp = current_time('timestamp');

if (get_current_user_id() == $appt_author):

	if ( $timestamp_start >= $current_timestamp ):

		// Send an email to the user?
		$email_content = get_option('booked_cancellation_email_content');
		$email_subject = get_option('booked_cancellation_email_subject');

		if ($email_content && $email_subject):

			$token_replacements = quickcal_get_appointment_tokens( $appt_id );
			$email_content = quickcal_token_replacement( $email_content,$token_replacements );
			$email_subject = quickcal_token_replacement( $email_subject,$token_replacements );

			do_action( 'booked_cancellation_email', $token_replacements['email'], $email_subject, $email_content );

		endif;

		// Send an email to the Admin?
		$email_content = get_option('booked_admin_cancellation_email_content');
		$email_subject = get_option('booked_admin_cancellation_email_subject');
		if ($email_content && $email_subject):

			$admin_email = quickcal_which_admin_to_send_email( $calendar_id );
			$token_replacements = quickcal_get_appointment_tokens( $appt_id );

			$email_content = quickcal_token_replacement( $email_content,$token_replacements );
			$email_subject = quickcal_token_replacement( $email_subject,$token_replacements );

			do_action( 'booked_admin_cancellation_email', $admin_email, $email_subject, $email_content, $token_replacements['email'], $token_replacements['name'] );

		endif;

	endif;

	do_action('booked_appointment_cancelled',$appt_id);
	wp_delete_post($appt_id,true);

endif;
