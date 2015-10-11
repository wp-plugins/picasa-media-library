<?php
/*
  Plugin Name: WP Picasa Media Library
  Plugin URI: http://dunghv.com/downloads/wordpress-picasa-media-library
  Description: Get all albums and images from a Google+ or Picasa user, see a preview, insert into content, save to media library or set as featured image very easy.
  Version: 2.2
  Author: Dunghv
  Author URI: http://dunghv.com
 */

register_activation_hook( __FILE__, 'vpml_activate' );
add_action( 'admin_init', 'vpml_redirect' );

function vpml_activate() {
	add_option( 'vpml_do_activation_redirect', true );
}

function vpml_redirect() {
	if ( get_option( 'vpml_do_activation_redirect', false ) ) {
		delete_option( 'vpml_do_activation_redirect' );
		wp_redirect( 'admin.php?page=vpml-welcome' );
	}
}

add_action( 'admin_menu', 'vpml_menu_pages' );

function vpml_menu_pages() {
	add_menu_page( 'Picasa', 'Picasa', 'manage_options', 'vpml-welcome', 'vpml_menu_page_welcome', 'dashicons-camera' );
	add_submenu_page( 'vpml-welcome', 'About', 'About', 'manage_options', 'vpml-welcome' );
	add_submenu_page( 'vpml-welcome', 'Settings', 'Settings', 'manage_options', 'vpml-settings', 'vpml_menu_page_settings' );
}

function vpml_menu_page_welcome() {
	?>
	<div class="wrap vpml-welcome">
		<h1>Welcome to Picasa Media Library</h1>

		<div class="about-text">
			Get all albums and images from a Google+ or Picasa user, see a preview, insert into content, save to media
			library or set as featured image very easy.
		</div>
		<br/>
		<iframe width="560" height="315" src="https://www.youtube.com/embed/0SM9nuh4fhI" frameborder="0"
		        allowfullscreen></iframe>
		<p class="vpml-thank-you">Thank you for choosing Picasa Media Library,<br><strong>Dunghv</strong><br/>Email:
			dunghv26@gmail.com<br/>Website: <a
				href="http://dunghv.com" target="_blank">http://dunghv.com</a></p>
	</div>
	<?php
}

function vpml_menu_page_settings() {
	?>
	<div class="wrap vpml-settings">
		<h1>Settings</h1>

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
	</div>
	<?php
}

add_action( 'admin_enqueue_scripts', 'vpml_enqueue_scripts' );

if ( get_option( 'vpml_frontend', 0 ) == 1 ) {
	add_action( 'wp_enqueue_scripts', 'vpml_enqueue_scripts' );
}

function vpml_enqueue_scripts( $hook ) {
	wp_enqueue_script( 'colorbox', plugin_dir_url( __FILE__ ) . '/js/jquery.colorbox.js', array( 'jquery' ) );
	wp_enqueue_script( 'cookie', plugin_dir_url( __FILE__ ) . '/js/jquery.cookie.js', array( 'jquery' ) );
	wp_enqueue_style( 'colorbox', plugins_url( 'css/colorbox.css', __FILE__ ) );
}

function vpml_add_button( $editor_id ) {
	echo ' <a href="#vpml_popup" id="vpml-btn" data-editor="' . $editor_id . '" class="vpml-btn button add_media" title="Picasa"><span class="dashicons dashicons-camera vpml-dashicons"></span> Picasa</a><input type="hidden" id="vpml_featured_url" name="vpml_featured_url" value="" /> ';
}

add_action( 'media_buttons', 'vpml_add_button' );

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
			$vpmlfurl = sanitize_text_field( $_POST['vpml_featured_url'] );
			vpml_save_featured( $vpmlfurl );
		}
	}
}

add_action( 'save_post', 'vpml_save_postdata', 10, 3 );

function vpml_save_featured( $vurl ) {
	global $post;
	$filename = pathinfo( $vurl, PATHINFO_FILENAME );

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
		$thumbid = media_handle_sideload( $file_array, $post->ID, $desc = null );
		if ( is_wp_error( $thumbid ) ) {
			@unlink( $file_array['tmp_name'] );

			return $thumbid;
		}
	}
	set_post_thumbnail( $post, $thumbid );

}

add_action( 'admin_footer', 'vpml_popup_content' );

if ( get_option( 'vpml_frontend', 0 ) == 1 ) {
	add_action( 'wp_footer', 'vpml_popup_content', 100 );
}

function vpml_popup_content() {
	?>
	<style>
		#vpml_popup {
			font-size: 13px !important;
			font-family: "Helvetica", helvetica, arial, sans-serif !important;
			color: #111 !important;
		}

		.vpml-dashicons {
			vertical-align: middle !important;
		}

		.vpml-container {
			width: 660px;
			display: inline-block;
			margin-top: 10px;
			height: 318px;
			overflow-x: hidden;
			overflow-y: auto;
		}

		.vpml-item {
			position: relative;
			display: inline-block;
			width: 150px;
			height: 150px;
			text-align: center;
			border: 1px solid #ddd;
			float: left;
			margin-right: 3px;
			margin-bottom: 3px;
			padding: 2px;
			background: #fff;
			box-sizing: content-box !important;
			background-size: cover;
			background-repeat: no-repeat;
			background-position: center;
		}

		.vpml-input, .vpml-select {
			padding: 0 6px !important;
			border-color: #DDD !important;
			box-shadow: none !important;
			border-radius: 2px !important;
			border: 1px solid #DDD !important;
			background-color: #fff !important;
			color: #32373c !important;
			float: left !important;
			margin-right: 2px !important;
			outline: none !important;
			font-size: 13px !important;
			line-height: 28px !important;
			height: 28px !important;
		}

		.vpml-input {
			width: 100%;
		}

		.vpml-input-small {
			width: 60px !important;
		}

		.vpml-input-normal {
			width: 345px !important;
		}

		.vpml-textarea {
			padding: 6px !important;
			border-color: #DDD !important;
			box-shadow: none !important;
			border-radius: 2px !important;
			border: 1px solid #DDD !important;
			background-color: #fff !important;
			color: #32373c !important;
			float: left !important;
			margin-right: 2px !important;
			outline: none !important;
			font-size: 13px !important;
			width: 100%;
		}

		.vpml-button {
			padding: 0 6px !important;
			border-color: #00a0d2 !important;
			box-shadow: none !important;
			border-radius: 2px !important;
			border: 1px solid #00a0d2 !important;
			background-color: #00a0d2 !important;
			color: #fff !important;
			float: left !important;
			margin-right: 2px !important;
			cursor: pointer !important;
			outline: none !important;
			font-size: 13px !important;
			line-height: 26px !important;
			height: 28px !important;
		}

		.vpml-button-disable {
			padding: 0 6px !important;
			border-color: #dedede !important;
			box-shadow: none !important;
			border-radius: 2px !important;
			border: 1px solid #dedede !important;
			background-color: #dedede !important;
			color: #555 !important;
			float: left !important;
			margin-right: 2px !important;
			cursor: pointer !important;
			outline: none !important;
			font-size: 13px !important;
			line-height: 26px !important;
			height: 28px !important;
		}

		.vpml-table {
			display: table !important;
			border-collapse: separate !important;
			border-spacing: 2px !important;
			border-color: grey !important;
			vertical-align: middle !important;
		}

		.vpml-tr {
			display: table-row !important;
			vertical-align: middle !important;
			border-color: inherit !important;
		}

		.vpml-td {
			display: table-cell !important;
			vertical-align: middle !important;
		}

		.vpml-checkbox {
			border: 1px solid #DDDDDD;
			background: #fff;
			color: #32373c;
			clear: none;
			cursor: pointer;
			display: inline-block;
			line-height: 0;
			height: 16px;
			margin: -4px 4px 0 0;
			outline: 0;
			padding: 0 !important;
			text-align: center;
			vertical-align: middle;
			width: 16px;
			min-width: 16px;
			border-radius: 2px !important;
			-webkit-appearance: none;
			box-shadow: none !important;
			-webkit-transition: .05s border-color ease-in-out;
			transition: .05s border-color ease-in-out;
		}

		.vpml-button:hover {
			opacity: 0.7;
		}

		.vpml-item img {
			max-width: 150px;
			max-height: 150px;
		}

		.vpml-use-image {
			width: 100%;
			display: none;
		}

		.vpml-item span {
			position: absolute;
			bottom: 2px;
			right: 2px;
			padding: 0 4px;
			color: #fff;
			font-size: 10px;
			background: rgba(0, 0, 0, 0.65);
			z-index: 10;
		}

		.vpml-page {
			height: 28px;
			line-height: 28px;
		}

		.vpml-item-overlay {
			width: 150px;
			height: 150px;
			background: #000;
			position: absolute;
			top: 2px;
			left: 2px;
			z-index: 997;
			opacity: 0.7;
			filter: alpha(opacity=70);
			display: none
		}

		.vpml-item-link {
			display: none;
			position: absolute;
			top: 50px;
			width: 150px;
			text-align: center;
			z-index: 998
		}

		.vpml-item-link a {
			display: inline-block;
			background: #fff;
			padding: 0 10px;
			height: 24px;
			line-height: 24px;
			margin-bottom: 5px;
			text-decoration: none;
			width: 120px;
			font-size: 12px;
			outline: none !important;
		}

		.vpml-p {
			margin-top: 10px;
		}

		.vpml-item:hover > .vpml-item-overlay {
			display: block
		}

		.vpml-item:hover > .vpml-item-link {
			display: block
		}

		.vpml-item-single {
			width: 100%;
			height: auto;
			text-align: center;
		}

		.vpml-item-single img {
			max-width: 100%;
			height: auto;
		}

		.vpml-loading {
			display: inline-block;
			height: 20px;
			line-height: 20px;
			min-width: 20px;
			padding-left: 25px;
			background: url("<?php echo plugin_dir_url(__FILE__) . '/images/loading.gif'; ?>") no-repeat;
		}
	</style>
	<div style='display:none'>
		<div id="vpml_popup" style="width: 954px; height: 570px; padding: 10px; position: relative; overflow: hidden">
			<div style="width: 660px; float: left;">
				<div style="width: 100%; display: inline-block; margin-top: 5px; height:28px; line-height: 28px;"><input
						type="text" id="vpml-user" name="vpml-user" value="" size="20"
						placeholder="google username or id"
						class="vpml-input vpml-input-normal"/>
					<input type="button" id="vpml-search" class="vpml-button" value="Get album(s) of this user"/> <span
						id="vpml-spinner" style="display:none" class="vpml-loading"> </span></div>
				<div id="vpml-container" class="vpml-container"><br/><br/>Enter Google username or id to start! Example:
					116819034451508671546
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
						<input type="hidden" id="vpml-eid" value=""/>
						<input type="hidden" id="vpml-url" value=""/>
						<input type="button" id="vpml_insert" class="vpml-button"
						       value="Insert"/>
						<a href="http://dunghv.com/downloads/wordpress-picasa-media-library"
						   target="_blank"
						   onclick="return confirm('This feature only available in Pro version!\nBuy it on http://dunghv.com now?')">
							<input type="button" class="vpml-button-disable" value="Save & Insert"/></a>
						<input type="button" id="vpml-featured" class="vpml-button"
						       value="Set featured image"/>

						<div style="margin-top:5px;display:inline-block">
							<span class="vpml-loading" id="vpml-note" style="display:none">Saving image to Media Library...</span>
							<span id="vpml-error"></span>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<script>
		function vpml_insertatcaret(areaId, text) {
			var txtarea = document.getElementById(areaId);
			var scrollPos = txtarea.scrollTop;
			var strPos = 0;
			var br = ((txtarea.selectionStart || txtarea.selectionStart == '0') ?
				"ff" : (document.selection ? "ie" : false));
			if (br == "ie") {
				txtarea.focus();
				var range = document.selection.createRange();
				range.moveStart('character', -txtarea.value.length);
				strPos = range.text.length;
			}
			else if (br == "ff")
				strPos = txtarea.selectionStart;

			var front = (txtarea.value).substring(0, strPos);
			var back = (txtarea.value).substring(strPos, txtarea.value.length);
			txtarea.value = front + text + back;
			strPos = strPos + text.length;
			if (br == "ie") {
				txtarea.focus();
				var range = document.selection.createRange();
				range.moveStart('character', -txtarea.value.length);
				range.moveStart('character', strPos);
				range.moveEnd('character', 0);
				range.select();
			}
			else if (br == "ff") {
				txtarea.selectionStart = strPos;
				txtarea.selectionEnd = strPos;
				txtarea.focus();
			}
			txtarea.scrollTop = scrollPos;
		}
		jQuery("#vpml-search").click(function () {
			vuser = jQuery("#vpml-user").val();
			jQuery.cookie("vpml-user", vuser, {expires: 365});
			vpml_showalbums("http://picasaweb.google.com/data/feed/api/user/" + vuser + "?kind=album&access=public&alt=json");
		});
		jQuery(document).ready(function () {
			cuser = jQuery.cookie("vpml-user");
			if (cuser) {
				jQuery("#vpml-user").val(cuser);
			}
		});
		jQuery('.vpml-btn').live('click', function () {
			eid = jQuery(this).attr('data-editor');
			jQuery('#vpml-eid').val(eid)
		});
		jQuery("#vpml-btn").colorbox({
			inline: true,
			scrolling: false,
			fixed: true,
			width: "684px",
			height: "450px"
		});
		jQuery("#vpml_insert").live("click", function () {
			if (jQuery('#vpml-url').val() != '') {
				vinsert = '';
				valign = '';
				valign2 = '';
				eid = jQuery('#vpml-eid').val();
				if (jQuery('#vpmlalign').val() != '') {
					valign = ' align="' + jQuery('#vpmlalign').val() + '"';
					valign2 = ' class="' + jQuery('#vpmlalign').val() + '"';
				}
				if (jQuery('textarea#vpml-caption').val() != '') {
					vinsert = '[caption id="" ' + valign + ']';
				}
				if (jQuery('#vpmllink').val() == 1) {
					vinsert += '<a href="' + jQuery('#vpml-site').val() + '" title="' + jQuery('#vpml-title').val() + '"';
				}
				if (jQuery('#vpmllink').val() == 2) {
					vinsert += '<a href="' + jQuery('#vpml-url').val() + '" title="' + jQuery('#vpml-title').val() + '"';
				}
				if (jQuery('#vpmlblank').is(':checked')) {
					vinsert += ' target="_blank"';
				}
				if (jQuery('#vpmlnofollow').is(':checked')) {
					vinsert += ' rel="nofollow"';
				}
				if (jQuery('#vpmllink').val() != 0) {
					vinsert += '>';
				}
				vinsert += '<img ' + valign2 + ' src="' + jQuery('#vpml-url').val() + '" width="' + jQuery('#vpml-width').val() + '" height="' + jQuery('#vpml-height').val() + '" title="' + jQuery('#vpml-title').val() + '" alt="' + jQuery('#vpml-title').val() + '"/>';
				if (jQuery('#vpmllink').val() != 0) {
					vinsert += '</a>';
				}
				if (jQuery('textarea#vpml-caption').val() != '') {
					vinsert += ' ' + jQuery('textarea#vpml-caption').val() + '[/caption]';
				}
				if (!tinyMCE.activeEditor || tinyMCE.activeEditor.isHidden()) {
					vpml_insertatcaret('content', vinsert);
				} else {
					tinyMCE.activeEditor.execCommand('mceInsertContent', 0, vinsert);
				}
				jQuery.colorbox.close();
			} else {
				alert('Have an error! Please try again!');
			}
		});
		jQuery("#vpml-featured").live("click", function () {
			vffurl = jQuery('#vpml-url').val();
			jQuery('#vpml_featured_url').val(vffurl);
			jQuery('#postimagediv div.inside img').remove();
			jQuery('#postimagediv div.inside').prepend('<img src="' + vffurl + '" width="270"/>');
			jQuery.colorbox.close();
		});
		jQuery("#remove-post-thumbnail").live("click", function () {
			jQuery('#vpml_featured_url').val('');
		});
		jQuery(".vpml-item-use").live("click", function () {
			jQuery.colorbox.resize({width: "980px", height: "450px"});
			jQuery("#vpml-use-image").show();
			jQuery('#vpml-title').val(jQuery(this).attr('vpmltitle'));
			jQuery('#vpml-width').val(jQuery(this).attr('vpmlwidth'));
			jQuery('#vpml-height').val(jQuery(this).attr('vpmlheight'));
			jQuery('#vpml-url').val(jQuery(this).attr('vpmlurl'));
			jQuery('#vpml-view').html('<img src="' + jQuery(this).attr('vpmltburl') + '"/>');
			jQuery('#vpml-error').html('');
		});
		jQuery(".vpml-album-view").live("click", function () {
			valbum_id = jQuery(this).attr('rel');
			valbum_num = jQuery(this).attr('num');
			vuser = jQuery("#vpml-user").val();
			valbum_json = "http://picasaweb.google.com/data/feed/api/user/" + vuser + "/album/" + valbum_id + "?kind=photo&alt=json&max-results=8&imgmax=1600";
			jQuery("#vcpage").val(valbum_json);
			jQuery("#vcnum").val(valbum_num);
			vpml_showphotos(valbum_json, valbum_num, 0);
		});
		jQuery("#vpml-page a").live("click", function () {
			palbum_json = jQuery("#vcpage").val();
			palbum_num = jQuery("#vcnum").val();
			palbum_start = jQuery(this).attr("rel");
			vpml_showphotos(palbum_json, palbum_num, palbum_start);
		});
		jQuery("#vpml-page-select").live("change", function () {
			palbum_json = jQuery("#vcpage").val();
			palbum_num = jQuery("#vcnum").val();
			palbum_start = jQuery(this).val();
			vpml_showphotos(palbum_json, palbum_num, palbum_start);
		});
		function vpml_showalbums(jurl) {
			jQuery('#vpml-page').html('');
			jQuery("#vpml-use-image").hide();
			jQuery('#vpml-spinner').show();
			jQuery('#vpml-container').html("");
			jQuery.getJSON(jurl, function (data) {
				if (data.feed.entry) {
					jQuery('#vpml-spinner').hide();
					jQuery.each(data.feed.entry, function (i, element) {
						valbum_thumb = element["media$group"]["media$content"][0];
						valbum_title = element.title["$t"];
						valbum_num = element["gphoto$numphotos"]["$t"];
						valbum_id = element["gphoto$name"]["$t"];
						jQuery('#vpml-container').append('<div class="vpml-item" rel="' + valbum_thumb.url + '"><div class="vpml-item-link"><a class="vpml-album-view" href="javascript: void(0);" num="' + valbum_num + '" rel="' + valbum_id + '" title="View all images in this album">View all</a></div><div class="vpml-item-overlay"></div><span style="width:142px">Album: ' + valbum_title + '<br/>' + valbum_num + ' photos</span></div> ');
					});

					jQuery('.vpml-item').each(function () {
						imageUrl = jQuery(this).attr('rel');
						jQuery(this).css('background-image', 'url(' + imageUrl + ')');
					});
				} else {
					jQuery('#vpml-spinner').hide();
					jQuery('#vpml-container').html('No result! Please try again!');
				}
			}).fail(function () {
				jQuery('#vpml-spinner').hide();
				jQuery('#vpml-container').html('User not found! Please try again!');
			});
		}
		function vpml_showphotos(jurl, jnum, jpage) {
			jQuery('#vpml-spinner').show();
			jQuery('#vpml-container').html("");
			jalbumurl = jurl + "&start-index=" + (jpage * 8 + 1);
			jQuery.getJSON(jalbumurl, function (data) {
				if (data.feed.entry) {
					jQuery('#vpml-spinner').hide();
					jQuery.each(data.feed.entry, function (i, element) {
						vimage = element["media$group"]["media$content"][0];
						vtitle = element.title["$t"];
						jQuery('#vpml-container').append('<div class="vpml-item" rel="' + vimage.url + '"><div class="vpml-item-link"><a href="' + vimage.url + '" target="_blank" title="View this image in new windows">View</a><a class="vpml-item-use" vpmltburl="' + vimage.url + '" vpmlurl="' + vimage.url + '" vpmlthumb="' + vimage.url + '" vpmltitle="' + vtitle + '" vpmlwidth="' + vimage.width + '" vpmlheight="' + vimage.height + '" href="javascript: void(0);">Use this image</a></div><div class="vpml-item-overlay"></div><span>' + vimage.width + ' x ' + vimage.height + '</span></div> ');
					});

					jQuery('.vpml-item').each(function () {
						imageUrl = jQuery(this).attr('rel');
						jQuery(this).css('background-image', 'url(' + imageUrl + ')');
					});

					var vpages = ' &nbsp; ' + jnum + ' images / ' + (Math.floor(jnum / 8) + 1) + ' pages ';
					var vselect = '<select name="vpml-page-select" id="vpml-page-select" class="vpml-select">';
					for (var j = 0; j < jnum / 8; j++) {
						vselect += '<option value="' + j + '"';
						if (j == jpage) {
							vselect += 'selected';
						}
						vselect += '>' + (j + 1) + '</option>';
					}
					;
					vselect += '</select>';
					jQuery('#vpml-page').html(vpages + vselect);
				} else {
					jQuery('#vpml-spinner').hide();
					jQuery('#vpml-container').html('No result! Please try again!');
					jQuery('#vpml-page').html('');
				}
			});
		}
	</script>
	<?php
}

?>