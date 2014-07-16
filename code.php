<?php
// A dirty script by Ashok
/*
 * adding action when user profile is updated
 */
add_action('personal_options_update', 'check_display_name');
add_action('edit_user_profile_update', 'check_display_name');
function check_display_name($user_id) {
        global $wpdb;
	// Getting user data and user meta data
        $err['display'] = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM $wpdb->users WHERE display_name = %s AND ID <> %d", $_POST['display_name'], $_POST['user_id']));
	$err['nick'] = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM $wpdb->users as users, $wpdb->usermeta as meta WHERE users.ID = meta.user_id AND meta.meta_key = 'nickname' AND meta.meta_value = %s AND users.ID <> %d", $_POST['nickname'], $_POST['user_id']));
	foreach($err as $key => $e) {
		// If display name or nickname already exists
		if($e >= 1) {
			$err[$key] = $_POST['username'];
			// Adding filter to corresponding error
			add_filter('user_profile_update_errors', "check_{$key}_field", 10, 3);
		}
	}
}
/*
 * Filter function for display name error
 */
function check_display_field($errors, $update, $user) {
        $errors->add('display_name_error',__('Sorry, Display Name is already in use. It needs to be unique.'));
        return false;
}
/*
 * Filter function for nickname error
 */
function check_nick_field($errors, $update, $user) {
        $errors->add('display_nick_error',__('Sorry, Nickname is already in use. It needs to be unique.'));
        return false;
}
/*
 * Check for duplicate display name and nickname and replace with username
 */
function display_name_and_nickname_duplicate_check() {
	global $wpdb;
	$query = $wpdb->get_results("select * from $wpdb->users");
	$query2 = $wpdb->get_results("SELECT * FROM $wpdb->users as users, $wpdb->usermeta as meta WHERE users.ID = meta.user_id AND meta.meta_key = 'nickname'");
	$c = count($query);
	for($i = 0; $i < $c; $i++) {
		for($j = $i+1; $j < $c; $j++) {
			if($query[$i]->display_name == $query[$j]->display_name){
				wp_update_user(
						array(
						      'ID' => $query[$i]->ID,
						      'display_name' => $query[$i]->user_login
						)       
					);
			}
			if($query2[$i]->meta_value == $query2[$j]->meta_value){
				update_user_meta($query2[$i]->ID, 'nickname', $query2[$i]->user_login, $prev_value);
			}
		}
	}
}
// Call the function
display_name_and_nickname_duplicate_check();

/*
 * Calling the display_name_and_nickname_duplicate_check() again when a new user is registered
 */
add_action( 'user_register', 'check_nickname', 10, 1 );
function check_nickname() {
	display_name_and_nickname_duplicate_check();
}
