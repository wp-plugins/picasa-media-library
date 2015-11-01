var vpml_imgs = {};
var vpml_selected = new Array();
var vpml_opened = false;
var vpml_current = '';

function vpml_escapehtml(html) {
    var fn = function (tag) {
        var charsToReplace = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&#34;'
        };
        return charsToReplace[tag] || tag;
    }
    return String(html).replace(/[&<>"]/g, fn);
}

function vpml_isurl(str) {
    var pattern = new RegExp('^(https?:\\/\\/)?' + // protocol
        '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.?)+[a-z]{2,}|' + // domain name
        '((\\d{1,3}\\.){3}\\d{1,3}))' + // OR ip (v4) address
        '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*' + // port and path
        '(\\?[;&a-z\\d%_.~+=-]*)?' + // query string
        '(\\#[-a-z\\d_]*)?$', 'i'); // fragment locator
    return pattern.test(str);
}

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

jQuery(document).ready(function (jQuery) {
    jQuery('.vpml-btn').live('click', function () {
        if (vpml_opened) {
            jQuery.colorbox({
                width: "980px",
                height: "450px",
                inline: true,
                href: "#vpml_popup",
                scrolling: false,
                fixed: true
            });
        } else {
            jQuery.colorbox({
                width: "680px",
                height: "450px",
                inline: true,
                href: "#vpml_popup",
                scrolling: false,
                fixed: true
            });
        }
    });
});

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

jQuery("#vpmlinsert").live("click", function () {
    for (var i = 0; i < vpml_selected.length; i++) {
        vinsert = '';
        valign = '';
        valign2 = '';
        eid = jQuery('#vpml-eid').val();
        if (jQuery('#vpmlalign').val() != '') {
            valign = ' align="' + vpml_escapehtml(jQuery('#vpmlalign').val()) + '"';
            valign2 = ' class="' + vpml_escapehtml(jQuery('#vpmlalign').val()) + '"';
        }
        var cid = vpml_selected[i];
        if (vpml_imgs[cid].img_caption != '') {
            vinsert = '[caption id="" ' + valign + ']';
        }
        if (jQuery('#vpmllink').val() == 1) {
            vinsert += '<a href="' + vpml_escapehtml(vpml_imgs[cid].img_site) + '" title="' + vpml_escapehtml(vpml_imgs[cid].img_title) + '"';
        }
        if (jQuery('#vpmllink').val() == 2) {
            vinsert += '<a href="' + vpml_escapehtml(vpml_imgs[cid].img_full) + '" title="' + vpml_escapehtml(vpml_imgs[cid].img_title) + '"';
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
        vinsert += '<img ' + valign2 + ' src="' + vpml_escapehtml(vpml_imgs[cid].img_full) + '" width="' + vpml_escapehtml(vpml_imgs[cid].img_width) + '" height="' + vpml_escapehtml(vpml_imgs[cid].img_height) + '" title="' + vpml_escapehtml(vpml_imgs[cid].img_title) + '" alt="' + vpml_escapehtml(vpml_imgs[cid].img_title) + '"/>';
        if (jQuery('#vpmllink').val() != 0) {
            vinsert += '</a>';
        }
        if (vpml_imgs[cid].img_caption != '') {
            vinsert += ' ' + vpml_escapehtml(vpml_imgs[cid].img_caption) + '[/caption]';
        }
        vinsert += '\n';
        if (!tinyMCE.activeEditor || tinyMCE.activeEditor.isHidden()) {
            vpml_insertatcaret(eid, vinsert);
        } else {
            tinyMCE.activeEditor.execCommand('mceInsertContent', 0, vinsert);
        }
    }
    jQuery.colorbox.close();
});

jQuery("#vpmlfeatured").live("click", function () {
    jQuery('#vpml_featured_url').val(jQuery('#vpml-url').val());
    jQuery('#vpml_featured_title').val(jQuery('#vpml-title').val());
    jQuery('#vpml_featured_caption').val(jQuery('#vpml-caption').val());
    jQuery('#vpml_featured_filename').val(jQuery('#vpml-filename').val());
    jQuery('#postimagediv div.inside img').remove();
    jQuery('#postimagediv div.inside').prepend('<img src="' + jQuery('#vpml-url').val() + '" width="270"/>');
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

jQuery(".vpml-item-overlay").live("click", function (event) {
    var checkbox = jQuery(this).parent().find(':checkbox');
    var checkbox_id = jQuery(this).attr('rel');

    jQuery.colorbox.resize({width: "980px", height: "450px"});
    vpml_opened = true;
    vpml_current = checkbox_id;

    if (event.ctrlKey) {

        if (!checkbox.is(':checked')) {
            vpml_selected.push(checkbox_id);
        } else {
            vpml_selected.splice(vpml_selected.indexOf(checkbox_id), 1);
        }

        checkbox.attr('checked', !checkbox.is(':checked'));
    } else {
        if (!checkbox.is(':checked')) {
            vpml_selected = [checkbox_id];
            jQuery('input:checkbox').removeAttr('checked');
            checkbox.attr('checked', !checkbox.is(':checked'));
        }
    }
    jQuery("#vpml-use-image").show();
    jQuery('#vpml-title').val(vpml_imgs[checkbox_id].img_title);
    jQuery('#vpml-caption').val(vpml_imgs[checkbox_id].img_caption);
    jQuery('#vpml-width').val(vpml_imgs[checkbox_id].img_width);
    jQuery('#vpml-height').val(vpml_imgs[checkbox_id].img_height);
    jQuery('#vpml-site').val(vpml_imgs[checkbox_id].img_site);
    jQuery('#vpml-url').val(vpml_imgs[checkbox_id].img_full);
    jQuery('#vpml-view').html('<img src="' + vpml_imgs[checkbox_id].img_full + '"/>');
    jQuery('#vpmlerror').html('');
    jQuery('#vpmlinsert').val('Insert (' + vpml_selected.length + ')');
    jQuery('#vpmlsave2').val('Save&Insert (' + vpml_selected.length + ')');
});

function vpml_showalbums(jurl) {
    vpml_imgs = {};
    vpml_selected = [];
    jQuery.colorbox.resize({width: "680px", height: "450px"});
    vpml_opened = false;
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
                jQuery('#vpml-container').append('<div class="vpml-album" bg="' + valbum_thumb.url + '"><div class="vpml-album-link"><a class="vpml-album-view" href="javascript: void(0);" num="' + valbum_num + '" rel="' + valbum_id + '" title="View all photos in this album">View all photos</a></div><div class="vpml-album-overlay"></div><span>Album: ' + valbum_title + '<br/>' + valbum_num + ' photos</span></div> ');
            });

            jQuery('.vpml-album').each(function () {
                imageUrl = jQuery(this).attr('bg');
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
    vpml_imgs = {};
    vpml_selected = [];
    jQuery.colorbox.resize({width: "680px", height: "450px"});
    vpml_opened = false;
    jQuery('#vpml-spinner').show();
    jQuery('#vpml-container').html("");
    jalbumurl = jurl + "&start-index=" + (jpage * 8 + 1);
    jQuery.getJSON(jalbumurl, function (data) {
        if (data.feed.entry) {
            jQuery('#vpml-spinner').hide();
            jQuery.each(data.feed.entry, function (i, element) {
                vimage = element["media$group"]["media$content"][0];
                img_id = element["gphoto$id"]["$t"];
                img_ext = vimage.url.split('.').pop().toUpperCase().substring(0, 4);
                img_site = vimage.url;
                img_thumb = vimage.url;
                img_full = vimage.url;
                img_width = vimage.width;
                img_height = vimage.height;
                img_title = String(element["title"]["$t"]);

                jQuery('#vpml-container').append('<div class="vpml-item" bg="' + img_full + '"><div class="vpml-item-overlay" rel="' + img_id + '"></div><div class="vpml-check"><input type="checkbox" value="' + img_id + '"/></div><span>' +
                    img_ext + ' | ' + img_width + 'x' + img_height + '</span></div>'
                )

                vpml_imgs[img_id] = {
                    img_ext: img_ext,
                    img_site: img_site,
                    img_thumb: img_thumb,
                    img_full: img_full,
                    img_width: img_width,
                    img_height: img_height,
                    img_title: img_title,
                    img_caption: ''
                };
            });

            jQuery('.vpml-item').each(function () {
                imageUrl = jQuery(this).attr('bg');
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

//change value
function vpml_change_value(img_id, img_field, img_value) {
    vpml_imgs[img_id][img_field] = img_value;
}

jQuery("#vpml-title").change(function () {
    vpml_change_value(vpml_current, 'img_title', jQuery(this).val());
});

jQuery("#vpml-caption").change(function () {
    vpml_change_value(vpml_current, 'img_caption', jQuery(this).val());
});

jQuery("#vpml-width").change(function () {
    vpml_change_value(vpml_current, 'img_width', jQuery(this).val());
});

jQuery("#vpml-height").change(function () {
    vpml_change_value(vpml_current, 'img_height', jQuery(this).val());
});