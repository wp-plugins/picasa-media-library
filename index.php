<?php
/*
  Plugin Name: Picasa Media Library
  Plugin URI: http://dunghv.com
  Description: Get all albums and images from a Google+ or Picasa user, see a preview, insert into content, save to media library or set as featured image very easy.
  Version: 1.0.2
  Author: Baby2j
  Author URI: http://dunghv.com
 */

add_action('admin_enqueue_scripts', 'vpml_enqueue_scripts');

function vpml_enqueue_scripts($hook) {
    wp_enqueue_script('colorbox', plugin_dir_url(__FILE__) . '/js/jquery.colorbox.js', array('jquery'));
    wp_enqueue_script('cookie', plugin_dir_url(__FILE__) . '/js/jquery.cookie.js', array('jquery'));
    wp_enqueue_style('colorbox', plugins_url('css/colorbox.css', __FILE__));
}

add_action('media_buttons_context', 'vpml_add_button');

function vpml_add_button($context) {
    $context .= '<a href="#vpml_popup" id="vpml-btn" class="button add_media" title="Picasa"><span class="wp-media-buttons-icon"></span> Picasa</a><input type="hidden" id="vpml_featured_url" name="vpml_featured_url" value="" />';
    return $context;
}

add_action('admin_footer', 'vpml_admin_footer');

function vpml_admin_footer() {
    ?>
    <style>
        .vpml-container{
            width: 640px;
            display: inline-block;
            max-height:540px;
            overflow:auto;
            margin-top: 10px;
        }
        .vpml-item{
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
        }
        .vpml-item img{
            max-width: 150px;
            max-height: 150px;
        }
        .vpml-use-image{
            width: 100%;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #dedede;
            display: none;
        }
        .vpml-item span{
            position: absolute;
            bottom: 2px;
            right: 2px;
            background: #000;
            padding: 0 4px;
            color: #fff;
            font-size: 10px;
        }
        .vpml-page{
            text-align: center;
        }
        .vpml-item-overlay{width: 150px;height: 150px;background: #000; position: absolute; top: 2px; left: 2px; z-index: 997; opacity:0.7; filter:alpha(opacity=70); display: none}
        .vpml-item-link{display: none; position: absolute; top: 50px; width: 100%; text-align: center; z-index: 998}
        .vpml-item-link a{
            display: inline-block;
            background: #fff;
            padding: 0 10px;
            height: 24px;
            line-height: 24px;
            margin-bottom: 5px;
            text-decoration: none;
            width: 90px;
            font-size: 12px;
        }
        .vpml-item:hover > .vpml-item-overlay{display: block}
        .vpml-item:hover > .vpml-item-link{display: block}
        .vpml-loading{display: inline-block; height: 20px; line-height: 20px; min-width:20px; padding-left: 25px; background: url("<?php echo plugin_dir_url(__FILE__) . '/images/spinner.gif'; ?>") no-repeat;}
    </style>
    <div style='display:none'>
        <div id="vpml_popup" style="width: 640px; height: 585px; padding: 10px; overflow: hidden">
            <div style="width:98%; display: inline-block; margin-top: 5px; height:28px; line-height: 28px;"><input type="text" id="vpml-user" name="vpml-user" value="" size="20" placeholder="google username or id"/> <input type="button" id="vpml-search" class="button" value="Get album(s) of this user"/> <span id="vpml-spinner" style="display:none" class="vpml-loading"> </span></div>
            <div id="vpml-container" class="vpml-container"><br/><br/>Enter Google username or id to start!</div>
            <div id="vpml-page" class="vpml-page"></div><input type="hidden" id="vcpage" name="vcpage" value=""/><input type="hidden" id="vcnum" name="vcnum" value=""/>
            <div id="vpml-use-image" class="vpml-use-image">
                <div class="vpml-item" id="vpml-view" style="margin-right: 20px;"></div>
                Title: <input type="text" id="vpml-title" size="42" value=""><br/><br/>
                Width: <input type="text" id="vpml-width" size="8" value="0"> x Height: <input type="text" id="vpml-height" size="8" value="0"><br/><br/>
                <input type="hidden" id="vpml-url" value="">
                <input type="button" id="vpml_insert" class="button button-primary" value="Insert into post"> <a href="http://dunghv.com" target="_blank" title="Only available in full version"><input type="button" id="vpml-save" class="button button-disabled" value="Save & Insert"/></a> <a href="http://dunghv.com" target="_blank" title="Only available in full version"><input type="button" id="vpml-featured" class="button button-disabled" value="Set featured image"/></a>
                <div style="margin-top:5px;display:inline-block"><span class="vpml-loading" id="vpml-note" style="display:none">Saving image to Media Library...</span> <span id="vpml-error"></span></div>
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
        jQuery("#vpml-search").click(function() {
            vuser = jQuery("#vpml-user").val();
            jQuery.cookie("vpml-user", vuser, {expires: 365});
            vpml_showalbum("http://picasaweb.google.com/data/feed/api/user/" + vuser + "?kind=album&access=public&alt=json");
        });
        jQuery(document).ready(function() {
            cuser = jQuery.cookie("vpml-user");
            if (cuser) {
                jQuery("#vpml-user").val(cuser);
            }
        });
        jQuery("#vpml-btn").colorbox({inline: true, scrolling: false, fixed: true, width: "670px"});
        jQuery("#vpml_insert").live("click", function() {
            if (jQuery('#vpml-url').val() != '') {
                vinsert = '<img src="' + jQuery('#vpml-url').val() + '" width="' + jQuery('#vpml-width').val() + '" height="' + jQuery('#vpml-height').val() + '" title="' + jQuery('#vpml-title').val() + '" alt="' + jQuery('#vpml-title').val() + '"/>';
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
        jQuery(".vpml-item-use").live("click", function() {
            jQuery("#vpml-use-image").show();
            jQuery('#vpml-title').val(jQuery(this).attr('vpmltitle'));
            jQuery('#vpml-width').val(jQuery(this).attr('vpmlwidth'));
            jQuery('#vpml-height').val(jQuery(this).attr('vpmlheight'));
            jQuery('#vpml-url').val(jQuery(this).attr('vpmlurl'));
            jQuery('#vpml-view').html('<img src="' + jQuery(this).attr('vpmltburl') + '"/>');
            jQuery('#vpml-error').html('');
        });
        jQuery(".vpml-album-view").live("click", function() {
            valbum_id = jQuery(this).attr('rel');
            valbum_num = jQuery(this).attr('num');
            vuser = jQuery("#vpml-user").val();
            valbum_json = "http://picasaweb.google.com/data/feed/api/user/" + vuser + "/album/" + valbum_id + "?kind=photo&alt=json&max-results=8&imgmax=1600";
            jQuery("#vcpage").val(valbum_json);
            jQuery("#vcnum").val(valbum_num);
            vpml_showphotos(valbum_json, valbum_num, 0);
        });
        jQuery("#vpml-page a").live("click", function() {
            palbum_json = jQuery("#vcpage").val();
            palbum_num = jQuery("#vcnum").val();
            palbum_start = jQuery(this).attr("rel");
            vpml_showphotos(palbum_json, palbum_num, palbum_start);
        });
        jQuery("#vpml-page-select").live("change", function() {
            palbum_json = jQuery("#vcpage").val();
            palbum_num = jQuery("#vcnum").val();
            palbum_start = jQuery(this).val();
            vpml_showphotos(palbum_json, palbum_num, palbum_start);
        });
        function vpml_showalbum(jurl) {
            jQuery('#vpml-page').html('');
            jQuery("#vpml-use-image").hide();
            jQuery('#vpml-spinner').show();
            jQuery('#vpml-container').html("");
            jQuery.getJSON(jurl, function(data) {
                if (data.feed.entry) {
                    jQuery('#vpml-spinner').hide();
                    jQuery.each(data.feed.entry, function(i, element) {
                        valbum_thumb = element["media$group"]["media$content"][0];
                        valbum_title = element.title["$t"];
                        valbum_num = element["gphoto$numphotos"]["$t"];
                        valbum_id = element["gphoto$name"]["$t"];
                        jQuery('#vpml-container').append('<div class="vpml-item"><div class="vpml-item-link"><a class="vpml-album-view" href="javascript: void(0);" num="' + valbum_num + '" rel="' + valbum_id + '" title="View all images in this album">View all</a></div><div class="vpml-item-overlay"></div><img src="' + valbum_thumb.url + '"><span style="width:142px">Album: ' + valbum_title + '<br/>' + valbum_num + ' photos</span></div> ');
                    });
                } else {
                    jQuery('#vpml-spinner').hide();
                    jQuery('#vpml-container').html('No result! Please try again!');
                }
            }).fail(function() {
                jQuery('#vpml-spinner').hide();
                jQuery('#vpml-container').html('User not found! Please try again!');
            });
        }
        function vpml_showphotos(jurl, jnum, jpage) {
            jQuery('#vpml-spinner').show();
            jQuery('#vpml-container').html("");
            jalbumurl = jurl + "&start-index=" + (jpage * 8 + 1);
            jQuery.getJSON(jalbumurl, function(data) {
                if (data.feed.entry) {
                    jQuery('#vpml-spinner').hide();
                    jQuery.each(data.feed.entry, function(i, element) {
                        vimage = element["media$group"]["media$content"][0];
                        vtitle = element.title["$t"];
                        jQuery('#vpml-container').append('<div class="vpml-item"><div class="vpml-item-link"><a href="' + vimage.url + '" target="_blank" title="View this image in new windows">View</a><a class="vpml-item-use" vpmltburl="' + vimage.url + '" vpmlurl="' + vimage.url + '" vpmlthumb="' + vimage.url + '" vpmltitle="' + vtitle + '" vpmlwidth="' + vimage.width + '" vpmlheight="' + vimage.height + '" href="javascript: void(0);">Use this image</a></div><div class="vpml-item-overlay"></div><img src="' + vimage.url + '"><span>' + vimage.width + ' x ' + vimage.height + '</span></div> ');
                    });
                    var vpages = jnum + ' images / ' + (Math.floor(jnum / 8) + 1) + ' pages ';
                    var vselect = '<select name="vpml-page-select" id="vpml-page-select">';
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