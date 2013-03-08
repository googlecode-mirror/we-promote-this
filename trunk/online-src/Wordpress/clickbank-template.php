<?php
/**
 * Template Name: ClickBank Template, No sideboar
 * Description: A full-width clickbank profile template with no sidebar
 * Add this to the current theme's root folder
 */
$wpdb->hide_errors(); nocache_headers();
 
global $userdata; get_currentuserinfo();
 
if(!empty($_POST['action'])){
 
	require_once(ABSPATH . 'wp-admin/includes/user.php');
	require_once(ABSPATH . WPINC . '/registration.php');
 
	check_admin_referer('update-profile_' . $user_ID);
 
	$errors = edit_user($user_ID);
 
	if ( is_wp_error( $errors ) ) {
		foreach( $errors->get_error_messages() as $message )
			$errmsg = "$message";
	}
 
	if($errmsg == '')
	{
		do_action('personal_options_update',$user_ID);
		$d_url = $_POST['dashboard_url'];
		wp_redirect( get_option("siteurl").'?page_id='.$post->ID.'&updated=true' );
	}
	else{
		$errmsg = '<div class="box-red">' . $errmsg . '</div>';
		$errcolor = 'style="background-color:#FFEBE8;border:1px solid #CC0000;"';
 
	}
}
 
get_header(); 
 
get_currentuserinfo();
?>

		<div id="content" class="clearfix">
			
			<div id="main" class="clearfix" role="main">

<?php if ( is_user_logged_in() ) {?>

 
<form name="profile" action="" method="post" enctype="multipart/form-data">
  <?php wp_nonce_field('update-profile_' . $user_ID) ?>
  <input type="hidden" name="from" value="profile" />
  <input type="hidden" name="action" value="update" />
  <input type="hidden" name="checkuser_id" value="<?php echo $user_ID ?>" />
  <input type="hidden" name="dashboard_url" value="<?php echo get_option("dashboard_url"); ?>" />
  <input type="hidden" name="user_id" id="user_id" value="<?php echo $user_id; ?>" />
  <input type="hidden" name="email" id="email" value="<?php echo $userdata->user_email ?>" />
  <table width="100%" cellspacing="0" cellpadding="0" border="0">
	<?php if ( isset($_GET['updated']) ):
$d_url = $_GET['d'];?>
	<tr>
	  <td align="center" colspan="2"><span style="color: #FF0000; font-size: 11px;">Your clickbank info update successfully</span></td>
	</tr>
	<?php elseif($errmsg!=""): ?>
	<tr>
	  <td align="center" colspan="2"><span style="color: #FF0000; font-size: 11px;"><?php echo $errmsg;?></span></td>
	</tr>
	<?php endif;?>
	<tr>
		<td colspan="2" align="center" class="white"><h2>Update ClickBank Info</h2><br></td>
	</tr>
	<tr>
		<td class="white">ClickBank Username</td>
		<td><input type="text" name="clickbank" id="clickbank" value="<?php echo esc_attr( get_the_author_meta( 'clickbank', $userdata->ID ) ); ?>" style="width: 300px;" /></td>
	</tr>
	<tr>
		<td  class="white">ClickBank Clerk API Key</td>
		<td><input type="text" name="clickbank_clerk_api_key" id="clickbank_clerk_api_key" value="<?php echo esc_attr( get_the_author_meta( 'clickbank_clerk_api_key', $userdata->ID ) ); ?>" style="width: 300px;" /></td>
	</tr>
	<tr>
	  <td align="center" colspan="2"><input type="submit" value="Update" /></td>
	</tr>
  </table>
  <input type="hidden" name="action" value="update" />
</form>

<?php } ?> 

<?php while ( have_posts() ) : the_post(); ?>

				<?php get_template_part( 'content', 'page' ); ?>

				<?php comments_template( '', true ); ?>

				<?php endwhile; // end of the loop. ?>


			</div><!-- #content -->

		</div><!-- #container -->
 
<?php get_footer(); ?>

