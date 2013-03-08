<?php
/**
 * Template Name: Profile Template, No sideboar
 * Description: A full-width profile template with no sidebar
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
		// Delete users previous youtube accounts
		$wpdb->query( 
			"
	                DELETE FROM wp_usermeta
			WHERE user_id = $user_ID
			AND meta_key LIKE 'youtube%'
			"
		);
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
?>
		<div id="content"  class="clearfix">
			<div id="main"  class="clearfix" role="main">
				<?php while ( have_posts() ) : the_post(); ?>

				<?php get_template_part( 'content', 'page' ); ?>

				<?php comments_template( '', true ); ?>

				<?php endwhile; // end of the loop. ?>

<?php if ( is_user_logged_in() ) {?>
 
<form name="profile" action="" method="post" enctype="multipart/form-data">
  <?php wp_nonce_field('update-profile_' . $user_ID) ?>
  <input type="hidden" name="from" value="profile" />
  <input type="hidden" name="action" value="update" />
  <input type="hidden" name="checkuser_id" value="<?php echo $user_ID ?>" />
  <input type="hidden" name="dashboard_url" value="<?php echo get_option("dashboard_url"); ?>" />
  <input type="hidden" name="user_id" id="user_id" value="<?php echo $user_id; ?>" />
  <input type="hidden" name="cb_referer" id="cb_referer" value="<?php 
  global $hop;
  $cb_referer = esc_attr( get_the_author_meta( 'cb_referer', $userdata->ID )) ;
  if(strlen($cb_referer)<=0){
	  if(strlen($hop)>0){
		$cb_referer = $hop;
        }
        else{
             $cb_referer = "cq2smooth";
        }
  }
echo $cb_referer; ?>">
  <table width="100%" cellspacing="0" cellpadding="0" border="0" id="profileTable">
	<?php if ( isset($_GET['updated']) ):
$d_url = $_GET['d'];?>
	<tr>
	  <td align="center" colspan="2"><span style="color: #FF0000; font-size: 11px;">Your profile changed successfully</span></td>
	</tr>
	<?php elseif($errmsg!=""): ?>
	<tr>
	  <td align="center" colspan="2"><span style="color: #FF0000; font-size: 11px;"><?php echo $errmsg;?></span></td>
	</tr>
	<?php endif;?>
	<tr>
		<td colspan="2" align="center" class="white"><h2>Update profile</h2><br></td>
	</tr>
	<tr>
	  <td class="white">First Name</td>
	  <td><input type="text" name="first_name" id="first_name" value="<?php echo $userdata->first_name ?>" style="width: 300px;" /></td>
	</tr>
	<tr>
	  <td class="white">Last Name</td>
	  <td><input type="text" name="last_name" class="mid2" id="last_name" value="<?php echo $userdata->last_name ?>" style="width: 300px;" /></td>
	</tr>
	<tr>
	  <td class="white">Email <span style="color: #F00">*</span></td>
	  <td><input type="text" name="email" class="mid2" id="email" value="<?php echo $userdata->user_email ?>" style="width: 300px;" /></td>
	</tr>
	<tr>
	  <td class="white">New Password </td>
	  <td><input type="password" name="pass1" class="mid2" id="pass1" value="" style="width: 300px;" /></td>
	</tr>
	<tr>
	  <td class="white">New Password Confirm </td>
	  <td><input type="password" name="pass2" class="mid2" id="pass2" value="" style="width: 300px;" /></td>
	</tr>
	<tr>
	  <td align="right" colspan="2" class="white"><span style="color: #F00">*</span> <span style="padding-right:40px;">mandatory fields</span></td>
	</tr>
	<tr>
		<td class="white">ClickBank Username <span style="color: #F00">*</span></td>
		<td><input type="text" name="clickbank" id="clickbank" value="<?php echo esc_attr( get_the_author_meta( 'clickbank', $userdata->ID ) ); ?>" style="width: 300px;" /></td>
	</tr>
	<tr>
		<td class="white">ClickBank Clerk API Key <span style="color: #F00">*</span></td>
		<td><input type="text" name="clickbank_clerk_api_key" id="clickbank_clerk_api_key" value="<?php echo esc_attr( get_the_author_meta( 'clickbank_clerk_api_key', $userdata->ID ) ); ?>" style="width: 300px;" /></td>
	</tr>
	<?php 
	show_youtube_fields($userdata);
    ?>

     <tr>
	  <td align="center" colspan="2"><br><input type="submit" value="Update" /></td>
	</tr>
  </table>
  <input type="hidden" name="action" value="update" />
</form>

<?php } ?> 

			</div><!-- #main -->
		</div><!-- #content -->



 
<?php get_footer(); ?>
