<?php
/*
Plugin Name: pMailer Campaign Archive
Plugin URI: http://www.pmailer.co.za/
Description: Adds a paginated list of sent messages to a page or post
Version: 0.1
Author: pMailer
Author URI: http://www.prefix.co.za
License: GPL
*/

// check if classes have already been included by another pmailer plugin.

/**
 * Include required files.
 */
if ( class_exists('PMailerSubscriptionApiV1_0') === false )
{
	require_once 'pmailer_api.php';
}

/* Runs when plugin is activated */
register_activation_hook(__FILE__, 'pmailer_cam_install');

/* Runs on plugin deactivation*/
register_deactivation_hook(__FILE__, 'pmailer_cam_remove');

/**
 * Runs code when Pmailer widget is activated.
 */
function pmailer_cam_install()
{
    // Add database options
    add_option('pmailer_cam_valid', '', '', 'no');
    add_option('pmailer_cam_url', '', '', 'no');
    add_option('pmailer_cam_api_key', '', '', 'no');

}

/**
 * Runs clean-up code when pmailer is de-activated.
 */
function pmailer_cam_remove()
{
    // Remove database options
    delete_option('pmailer_cam_valid');
    delete_option('pmailer_cam_url');
    delete_option('pmailer_cam_api_key');

}



// create custom plugin settings menu
add_action('admin_menu', 'pmailer_cam_create_menu');

function pmailer_cam_create_menu()
{

    //create new top-level menu
    add_options_page('pMailer', 'pMailer Campaigns', 'manage_options', 'pmailer-campaigns', 'pmailer_cam_settings_page');

}

function pmailer_cam_save_api_settings()
{
    // Update API details
    if ( isset($_POST['pmailer_cam_api_details']) === true )
    {
        // save details
        update_option('pmailer_cam_url', $_POST['pmailer_cam_url']);
        update_option('pmailer_cam_api_key', $_POST['pmailer_cam_api_key']);
        $pmailerApi = new PMailerSubscriptionApiV1_0($_POST['pmailer_cam_url'], $_POST['pmailer_cam_api_key']);
        try
        {
            $lists = $pmailerApi->getLists();
        }
        catch ( PMailerSubscriptionException $e )
        {
            echo '<div class="error"><p>'.$e->getMessage().'</p></div>';
            return;
        }

        update_option('pmailer_cam_valid', 'yes');
        echo '<div class="updated"><p>API details successfully updated.</p></div>';

    }
}

function pmailer_cam_reset_enterprise_settings()
{
    // Reset details
    if ( isset($_POST['pmailer_cam_reset_details']) === true )
    {
        update_option('pmailer_cam_valid', '');
    }
}

function pmailer_cam_settings_page()
{
    if ( is_admin() )
    {
    	pmailer_cam_save_api_settings();
        pmailer_cam_reset_enterprise_settings();
    }

?>
<div class="wrap">
<div class="icon32" id="icon-options-general"><br>
</div>
<h2>pMailer campaign archive paginator</h2>
    <?php
    $valid = get_option('pmailer_cam_valid');
    if ( empty($valid) === true ):
    ?>
    <div style="background-color:white; padding:10px;">
    <strong>Please enter your pMailer details:</strong>
    <form method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
        <table class="form-table">
            <tr valign="top">
                <th scope="row">pMailer URL</th>
                <td><input type="text" name="pmailer_cam_url" value="<?php echo get_option('pmailer_cam_url'); ?>" /> - e.g. live.pmailer.co.za</td>
            </tr>
            <tr valign="top">
                <th scope="row">API key</th>
                <td><input type="text" name="pmailer_cam_api_key" size="40" value="<?php echo get_option('pmailer_cam_api_key'); ?>" /></td>
            </tr>
        </table>
        <input type="hidden" name="pmailer_cam_api_details" value="Y">
        <p class="submit"><input type="submit" class="button-primary" value="<?php _e('Next') ?>" /></p>
    </form>
    </div>
    <?php
    endif;
    ?>

    <?php
    if ( $valid === 'yes' ):
    ?>
<div style="background-color:white; padding:10px;">
<form method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
    <strong>Enterprise details:</strong><br />
    Enterprise URL: <i><?php echo get_option('pmailer_cam_url'); ?></i><br />
    API key: <i><?php echo get_option('pmailer_cam_api_key'); ?></i> <input type="hidden" name="pmailer_cam_reset_details" value="Y">
    <br /><br />
    <strong>How to use:</strong><br />
        <i>Add [pmailer_campaign_paginator] to a page or post and it will be replaced with a paginated list of sent messages ordered by the latest sent.</i>
    <p class="submit"><input type="submit" class="button-primary" value="<?php _e('Change enterprise & API details') ?>" /></p>
</form>
</div>
<br />

</div>
    <?php
    endif;
    ?>
<?php
}

// add short code functionality
function pmailer_cam_paginator_short_tag($atts)
{
    $fetch_limit = 10;

    // sanitize $page var
    $page = ( isset($_GET['pmailer_cam_show_page']) === true ) ? (int)$_GET['pmailer_cam_show_page'] : 1;

    $html = '';
    // get messages
    $pmailerApi = new PMailerSubscriptionApiV1_0(get_option('pmailer_cam_url'), get_option('pmailer_cam_api_key'));
    try
    {
        $messages = $pmailerApi->getMessages(array('message_status' => 'sent'), array('message_send_date' => 'DESC'), $page, $fetch_limit);
    }
    catch ( PMailerSubscriptionException $e )
    {
    	// display no error message to plebs
        $html .= 'An error occurred, please try again later: <br />' . $e->getMessage();
        if ( is_admin() )
        {
        	// display error message to the admin user:
        	$html = 'An error occurred due to: ' . $e->getMessage();
        }
        return $html;
    }

    // get GET variables into a query string
    $query_string = '?';
    foreach ( $_GET as $get_key => $get_val )
    {
    	// do not include the page var if it exists because it will be included later
    	if ( $get_key == 'pmailer_cam_show_page' )
    	{
    		break;
    	}
        $query_string .= $get_key . '=' . $get_val . '&';
    }

    // build paginator
    $html .= '<p>';
    // work out how many pages there are in total
    $total_num_pages = (int)ceil(intval($messages['total'])/10);
    // create previous button
    if ( $page !== 1 && $page <= $total_num_pages )
    {
        $html .= '<a href="'.trailingslashit(home_url()). $query_string .'pmailer_cam_show_page='.($page-1).'" >&laquo;</a> ';
    }
    // display which page user is on
    $html .= 'page ' . $page . ' of ' . $total_num_pages;
    // create next button
    if ( $page !== $total_num_pages && $page < $total_num_pages )
    {
    	$html .= ' <a href="'.trailingslashit(home_url()). $query_string . 'pmailer_cam_show_page='.($page+1).'" >&raquo;</a>';
    }
    $html .= '</p>';

    // get date format
    $date_format = get_option('date_format');

    // build list of messages
    foreach ( $messages['data'] as $message )
    {
    	$html .= '<a href="'.trailingslashit(home_url()). '?pmailer_cam_view_message='.$message['message_id'].'" target="_blank"><strong>' . date($date_format, (int)$message['message_send_date']) . '</strong></a> <i>sent on ' . $message['message_subject'] . '</i><br />' . "\n";
    }

    return $html;

}

// add short code only if pmailer campaign api details are valid
if ( get_option('pmailer_cam_valid') == 'yes' )
{
	add_shortcode('pmailer_campaign_paginator', 'pmailer_cam_paginator_short_tag');
}

function pmailer_cab_display_message()
{
    if ( isset($_GET['pmailer_cam_view_message']) === true )
    {
    	$_GET['pmailer_cam_view_message'] = (int)$_GET['pmailer_cam_view_message'];
	    $pmailerApi = new PMailerSubscriptionApiV1_0(get_option('pmailer_cam_url'), get_option('pmailer_cam_api_key'));
	    try
	    {
	        $message = $pmailerApi->getMessage($_GET['pmailer_cam_view_message']);
	    }
	    catch ( PMailerSubscriptionException $e )
	    {
	        // display error message
	        die('The following error occurred: ' . $e->getMessage());
	    }
	    die($message['message_html_original']);
    }
}

add_action('init', 'pmailer_cab_display_message');
