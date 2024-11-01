<?php 
/*
Plugin Name: WPMyAvatar
Plugin URI: https://frametagmedia.com.au/
Description: Select a user profile avatar from the WordPress media library. 
Version: 1.1
Author: Frametag Media
Author URI: https://frametagmedia.com.au/
License: GNU GPLv2
*/

add_action('admin_print_styles-user-edit.php', 'wpma_admin_print_styles');
add_action('admin_print_styles-profile.php', 'wpma_admin_print_styles');
function wpma_admin_print_styles() {
	global $hook_suffix;
	wp_enqueue_style('my-avatar', plugins_url('css/my-avatar.css', __FILE__), 'css');
}

function wpma_load_wp_media_files() {
  wp_enqueue_media();
}
add_action( 'admin_enqueue_scripts', 'load_wp_media_files' );

add_action('show_user_profile', 'wpma_form');
add_action('edit_user_profile', 'wpma_form');

function wpma_form($profile)
{
	global $current_user;
	
	// Check if it is current user or super admin role
	if( $profile->ID == $current_user->ID || current_user_can('edit_user', $current_user->ID) || is_super_admin($current_user->ID) ): ?>
        <table class="form-table">
        <tr id="wpma_field_row">
            <th>
                <label for="specs"><?php _e('Profile Picture', 'my-avatar'); ?></label>
            </th>
            <td>
                <div id="my-avatar-display">
					<div id="my-avatar-display-image"><?php echo wpma_get_url($profile->ID) !== false ? wpma_get_avatar($profile->ID) : ''; ?></div>
					<button id="my-avatar-link" class="button button-secondary"><?php _e('Update Avatar','my-avatar'); ?></button> 
					<input type="hidden" id="wpma_url" name="wpma_url" value="<?php echo wpma_get_url($profile->ID) !== false ? wpma_get_url($profile->ID) : ''; ?>" />
				</div>
            </td>
        </tr>
        </table>
	<script type='text/javascript'>
		jQuery( document ).ready( function( $ ) {
			// Select From media library files
			var file_frame;
			jQuery('#my-avatar-link').on('click', function( event ){
				event.preventDefault();
				// If the media frame already exists, reopen it.
			    if ( file_frame ) {
			      file_frame.open();
			      return;
			    }
			    
			    // Create a new media frame
			    file_frame = wp.media({
			      title: 'Select or Upload Media Of Your Chosen Persuasion',
			      button: {
			        text: 'Use this media'
			      },
			      multiple: false  // Set to true to allow multiple files to be selected
			    });
				// When an image is selected, run a callback.
				file_frame.on( 'select', function() {
					// Only get one image from the uploader
					attachment = file_frame.state().get('selection').first().toJSON();

					jQuery('#wpma_url').val( attachment.url );
					jQuery('#my-avatar-display-image').html('<img src="'+attachment.url+'" width=150 height=150 alt="New Avatar" />');
				});
					// Finally, open the modal
					file_frame.open();
			});
			
		});
	</script>
	<?php
	endif;
} 

function wpma_get_avatar($ID,$size=150,$alt='User Avatar'){
	$url = get_user_meta($ID,'wpma_url',true);
	// check we got a valid url 
	if(!empty($url) && filter_var($url, FILTER_VALIDATE_URL)){
		return '<img src="'.$url.'" class="avatar avatar-'.$size.' photo" width='.$size.' height='.$size.' alt="'.$alt.'" />';
	}
	return false;
}

function wpma_get_url($ID){
	$url = get_user_meta($ID,'wpma_url',true);
	// check we got a valid url 
	if(!empty($url) && filter_var($url, FILTER_VALIDATE_URL)){
		return $url;
	}
	return false;
}

add_action( 'personal_options_update', 'wpma_profile_fields' );
add_action( 'edit_user_profile_update', 'wpma_profile_fields' );

function wpma_profile_fields( $user_id ) {

	if ( !current_user_can( 'edit_user', $user_id ) || empty($_POST['wpma_url']))
		return false;

	update_user_meta( $user_id, 'wpma_url', esc_url($_POST['wpma_url']) );
}


/**
 * WordPress Avatar Filter
 * Replaces the WordPress avatar with your custom photo using the get_avatar hook.
 */
add_filter( 'get_avatar', 'wpma' , 10 , 5 );

function wpma( $avatar, $id_or_email, $size, $default, $alt ) {
    $user = false;
    $id = false;

    if ( is_numeric( $id_or_email ) ) {

        $id = (int) $id_or_email;
        $user = get_user_by( 'id' , $id );

    } elseif ( is_object( $id_or_email ) ) {

        if ( ! empty( $id_or_email->user_id ) ) {
            $id = (int) $id_or_email->user_id;
            $user = get_user_by( 'id' , $id );
        }

    } else {
        // $id = (int) $id_or_email;
        $user = get_user_by( 'email', $id_or_email );   
    }

    if ( $user && is_object( $user ) ) {

        $custom_avatar = wpma_get_url($user->id);

        if (isset($custom_avatar) && !empty($custom_avatar)) {
            $avatar = "<img alt='{$alt}' src='{$custom_avatar}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
        }

    }

    return $avatar;
}

function wpma_move_around() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            field = $('#wpma_field_row').remove();
            field.insertBefore('tr.user-profile-picture');
        });
    </script>
    <?php
}
add_action( 'admin_head', 'wpma_move_around' );

function wpma_shortcode( $atts ) {
	global $current_user;
	$atts = shortcode_atts( array(
		'userId' => $current_user->ID,
		'size' => 150,
		'alt' => 'User Avatar'
	), $atts, 'wpma' );

	return wpma_get_avatar($atts['userId'],$atts['size'],$atts['alt']);
}
add_shortcode( 'wpma', 'wpma_shortcode' );
