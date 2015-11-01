<?php
/*
  Plugin Name: WP Picasa Media Library
  Plugin URI: http://wpclever.net
  Description: Get all albums and photos from a Google+ or Picasa user, see a preview, insert into the content, save to media library or set as featured image very easy & quickly.
  Version: 3.0
  Author: WPclever
  Author URI: http://wpclever.net/about
 */

define( 'VPML_VERSION', '3.0' );
define( 'VPML_PRO_URL', 'http://codecanyon.net/item/wp-picasa-quick-insert/13469529' );

register_activation_hook( __FILE__, 'vpml_activate' );
add_action( 'admin_init', 'vpml_redirect' );

function vpml_activate() {
	add_option( 'vpml_do_activation_redirect', true );
}

function vpml_redirect() {
	if ( get_option( 'vpml_do_activation_redirect', false ) ) {
		delete_option( 'vpml_do_activation_redirect' );
		wp_redirect( 'admin.php?page=vpml&tab=about' );
	}
}

add_action( 'admin_menu', 'vpml_menu' );

function vpml_menu() {
	add_menu_page( 'Picasa', 'Picasa', 'manage_options', 'vpml', 'vpml_menu_pages', 'dashicons-camera' );
}

function vpml_menu_pages() {
	$vpml_active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'settings';
	?>
	<div class="wrap vpml-welcome">
		<h1>Welcome to WP Picasa Media Library</h1>

		<div class="about-text">
			Get all albums and photos from a Google+ or Picasa user, see a preview, insert into the content, save to
			media
			library or set as featured image very easy & quickly.
		</div>
		<h2 class="nav-tab-wrapper">
			<a href="?page=vpml&amp;tab=settings"
			   class="nav-tab <?php echo $vpml_active_tab == 'settings' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Settings' ); ?></a>
			<a href="?page=vpml&amp;tab=about"
			   class="nav-tab <?php echo $vpml_active_tab == 'about' ? 'nav-tab-active' : ''; ?>"><?php _e( 'How to use?' ); ?></a>
			<a href="?page=vpml&amp;tab=support"
			   class="nav-tab <?php echo $vpml_active_tab == 'support' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Support' ); ?></a>
			<a href="?page=vpml&amp;tab=getpro"
			   class="nav-tab <?php echo $vpml_active_tab == 'getpro' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Get PRO version!' ); ?></a>
		</h2>
		<br/>
		<?php if ( $vpml_active_tab == 'about' ) { ?>
			<iframe width="560" height="315" src="https://www.youtube.com/embed/0SM9nuh4fhI" frameborder="0"
			        allowfullscreen></iframe>
		<?php } elseif ( $vpml_active_tab == 'settings' ) { ?>
			<form method="post" action="options.php" novalidate="novalidate">
				<?php wp_nonce_field( 'update-options' ) ?>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="vpml_frontend">Front-end editor</label></th>
						<td>
							<input name="vpml_frontend" type="checkbox" id="vpml_frontend"
							       value="1" <?php checked( '1', get_option( 'vpml_frontend' ) ); ?>/>

							<p class="description">Check this option if you want use Picasa Media Library for front-end
								editor.</p>
						</td>
					</tr>
				</table>
				<p class="submit">
					<input type="hidden" name="action" value="update"/>
					<input type="hidden" name="page_options"
					       value="vpml_frontend"/>
					<input type="submit" name="submit" id="submit" class="button button-primary"
					       value="Save Changes"/>
				</p>
			</form>
		<?php } elseif ( $vpml_active_tab == 'support' ) { ?>
			Thank you for choosing WP Picasa Media Library,<br/>
			Feel free to contact me via email: <strong>wpcleverdotnet@gmail.com</strong><br/>
			More infomation: <a href="http://wpclever.net">http://wpclever.net</a><br/>
		<?php } elseif ( $vpml_active_tab == 'getpro' ) { ?>
			Get PRO version at: <a href="<?php echo VPML_PRO_URL; ?>"><?php echo VPML_PRO_URL; ?></a>
		<?php } ?>
	</div>
	<?php
}

add_action( 'admin_enqueue_scripts', 'vpml_enqueue_scripts' );

if ( get_option( 'vpml_frontend', 0 ) == 1 ) {
	add_action( 'wp_enqueue_scripts', 'vpml_enqueue_scripts' );
}

function vpml_enqueue_scripts() {
	//styles
	wp_enqueue_style( 'colorbox', plugins_url( 'css/colorbox.css', __FILE__ ) );
	wp_enqueue_style( 'vpml-css', plugins_url( 'css/vpml.css', __FILE__ ) );

	//js
	wp_enqueue_script( 'colorbox', plugin_dir_url( __FILE__ ) . '/js/jquery.colorbox.js', array( 'jquery' ), VPML_VERSION, true );
	wp_enqueue_script( 'cookie', plugin_dir_url( __FILE__ ) . '/js/jquery.cookie.js', array( 'jquery' ), VPML_VERSION, true );
	wp_enqueue_script( 'vpml-js', plugin_dir_url( __FILE__ ) . 'js/vpml.js', array( 'jquery' ), VPML_VERSION, true );
}

add_action( 'media_buttons', 'vpml_add_button' );
function vpml_add_button( $editor_id ) {
	echo ' <a href="#vpml_popup" id="vpml-btn" data-editor="' . $editor_id . '" class="vpml-btn button add_media" title="Picasa"><span class="dashicons dashicons-camera vpml-dashicons"></span> Picasa</a><input type="hidden" id="vpml_featured_url" name="vpml_featured_url" value="" /><input type="hidden" id="vpml_featured_title" name="vpml_featured_title" value="" /><input type="hidden" id="vpml_featured_caption" name="vpml_featured_caption" value="" /><input type="hidden" id="vpml_featured_filename" name="vpml_featured_filename" value="" /> ';
}

add_action( 'save_post', 'vpml_save_postdata', 10, 3 );
function vpml_save_postdata( $post_id, $post ) {
	if ( isset( $post->post_status ) && 'auto-draft' == $post->post_status ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( ! empty( $_POST['vpml_featured_url'] ) ) {
		if ( strstr( $_SERVER['REQUEST_URI'], 'wp-admin/post-new.php' ) || strstr( $_SERVER['REQUEST_URI'], 'wp-admin/post.php' ) ) {
			if ( 'page' == $_POST['post_type'] ) {
				if ( ! current_user_can( 'edit_page', $post_id ) ) {
					return;
				}
			} else {
				if ( ! current_user_can( 'edit_post', $post_id ) ) {
					return;
				}
			}
			$vpmlfurl      = sanitize_text_field( $_POST['vpml_featured_url'] );
			$vpmlftitle    = sanitize_text_field( $_POST['vpml_featured_title'] );
			$vpmlfcaption  = sanitize_text_field( $_POST['vpml_featured_caption'] );
			$vpmlffilename = sanitize_text_field( $_POST['vpml_featured_filename'] );
			vpml_save_featured( $vpmlfurl, $vpmlftitle, $vpmlfcaption, $vpmlffilename, $post_id );
		}
	}
}

function vpml_clean( $string ) {
	$string = str_replace( ' ', '-', $string );

	return preg_replace( '/[^A-Za-z0-9\-]/', '-', $string );
}

function vpml_save_featured( $vurl, $vtitle, $vcaption, $vfilename, $vpid ) {
	$thumbid  = 0;
	$filename = sanitize_title( vpml_clean( pathinfo( $vurl, PATHINFO_FILENAME ) ) );
	if ( ( $vfilename == '1' ) && ( $vtitle != '' ) ) {
		$filename = sanitize_title( $vtitle );
	}
	require_once( ABSPATH . 'wp-admin/includes/file.php' );
	require_once( ABSPATH . 'wp-admin/includes/media.php' );
	@set_time_limit( 300 );
	if ( ! empty( $vurl ) ) {
		$tmp                    = download_url( $vurl );
		$ext                    = pathinfo( $vurl, PATHINFO_EXTENSION );
		$file_array['name']     = $filename . '.' . $ext;
		$file_array['tmp_name'] = $tmp;
		if ( is_wp_error( $tmp ) ) {
			@unlink( $file_array['tmp_name'] );
			$file_array['tmp_name'] = '';
		}
		$thumbid   = media_handle_sideload( $file_array, $vpid, $desc = null );
		$thumb_arr = array(
			'ID'           => $thumbid,
			'post_title'   => $vtitle,
			'post_excerpt' => $vcaption,
			'post_content' => $vcaption,
		);
		wp_update_post( $thumb_arr );
		update_post_meta( $thumbid, '_wp_attachment_image_alt', $vtitle );
		if ( is_wp_error( $thumbid ) ) {
			@unlink( $file_array['tmp_name'] );

			return $thumbid;
		}
	}
	set_post_thumbnail( $vpid, $thumbid );
}

add_action( 'admin_footer', 'vpml_popup_content' );

if ( get_option( 'vpml_frontend', 0 ) == 1 ) {
	add_action( 'wp_footer', 'vpml_popup_content', 100 );
}

function vpml_popup_content() {
	?>
	<div style='display:none'>
		<div id="vpml_popup" style="width: 954px; height: 570px; padding: 10px; position: relative; overflow: hidden">
			<div style="width: 660px; float: left;">
				<div style="width: 100%; display: inline-block; height:28px; line-height: 28px;"><input
						type="text" id="vpml-user" name="vpml-user" value="" size="20"
						placeholder="google username or id"
						class="vpml-input vpml-input-normal"/>
					<input type="button" id="vpml-search" class="vpml-button"
					       value="Get all albums of this user"/> <span
						id="vpml-spinner" style="display:none" class="vpml-loading"> </span></div>
				<div id="vpml-container" class="vpml-container">Please enter Google Username or ID to start!
					<br/>Example: <strong>clip360net</strong> <u>or</u> <strong>116819034451508671546</strong>
				</div>
				<div id="vpml-page" class="vpml-page"></div>
				<input type="hidden" id="vcpage" name="vcpage" value=""/><input type="hidden" id="vcnum" name="vcnum"
				                                                                value=""/>
			</div>
			<div
				style="width: 280px; height: 500px; position: absolute; top: 0; right: 0; padding: 10px; border-left: 1px solid #ddd;background: #fcfcfc; box-sizing: content-box !important;">
				<div id="vpml-use-image" class="vpml-use-image">
					<div class="vpml-right" style="height: 360px; overflow-y: auto; overflow-x: hidden">
						<table class="vpml-table">
							<tr class="vpml-tr">
								<td colspan="2" class="vpml-td">
									<div class="vpml-item-single" id="vpml-view" style="margin-right: 20px;"></div>
								</td>
							</tr>
							<tr class="vpml-tr">
								<td class="vpml-td">Title</td>
								<td class="vpml-td"><input type="text" id="vpml-title" value="" class="vpml-input"
								                           placeholder="title"/>
								</td>
							</tr>
							<tr class="vpml-tr">
								<td class="vpml-td">Caption</td>
								<td class="vpml-td"><textarea id="vpml-caption" name="vpml-caption"
								                              class="vpml-textarea"></textarea>
								</td>
							</tr>
							<tr class="vpml-tr">
								<td class="vpml-td">File name</td>
								<td class="vpml-td">
									<select name="vpml-filename" id="vpml-filename" class="vpml-select">
										<option value="0">Keep original file name</option>
										<option value="1">Generate from title</option>
									</select>
								</td>
							</tr>
							<tr class="vpml-tr">
								<td class="vpml-td">Size</td>
								<td class="vpml-td"><input type="text" id="vpml-width"
								                           class="vpml-input vpml-input-small"
								                           placeholder="width"/> <input type="text" id="vpml-height"
								                                                        class="vpml-input vpml-input-small"
								                                                        placeholder="height"/>
								</td>
							</tr>
							<tr class="vpml-tr">
								<td class="vpml-td">Alignment</td>
								<td class="vpml-td">
									<select name="vpmlalign" id="vpmlalign" class="vpml-select">
										<option value="alignnone">None</option>
										<option value="alignleft">Left</option>
										<option value="alignright">Right</option>
										<option value="aligncenter">Center</option>
									</select>
								</td>
							</tr>
							<tr class="vpml-tr">
								<td class="vpml-td">Link to</td>
								<td class="vpml-td">
									<select name="vpmllink" id="vpmllink" class="vpml-select">
										<option value="0">None</option>
										<option value="2">Original image</option>
									</select>
								</td>
							</tr>
							<tr class="vpml-tr">
								<td class="vpml-td">&nbsp;</td>
								<td class="vpml-td"><input name="vpmlblank" id="vpmlblank" type="checkbox"
								                           class="vpml-checkbox"/> Open
									new
									windows
								</td>
							</tr>
							<tr class="vpml-tr">
								<td class="vpml-td">&nbsp;</td>
								<td class="vpml-td"><input name="vpmlnofollow" id="vpmlnofollow" type="checkbox"
								                           class="vpml-checkbox"/>
									Rel
									nofollow
								</td>
							</tr>
						</table>
					</div>
					<div class="vpml-p">
						<input type="hidden" id="vpml-site" value=""/>
						<input type="hidden" id="vpml-url" value=""/>
						<input type="hidden" id="vpml-eid" value=""/>
						<input type="button" id="vpmlinsert" class="vpml-button"
						       value="Insert"/>
						<a href="<?php echo VPML_PRO_URL; ?>"
						   target="_blank"
						   onclick="return confirm('This feature only available in Pro version!\nBuy it now?')">
							<input type="button" id="vpmlsave2" class="vpml-button-disable" value="Save&Insert"/></a>
						<input type="button" id="vpmlfeatured" class="vpml-button"
						       value="SetFeatured"/>

						<div style="margin-top:5px;display:inline-block">
							<span class="vpml-loading-text" id="vpmlnote" style="display:none">Saving image to Media Library...</span>
							<span id="vpml-error"></span>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}

?>