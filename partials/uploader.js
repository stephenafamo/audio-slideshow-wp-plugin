function delete_template(event, prefix = "", suffix = "")
{
    var nothing = '';
    document.getElementById(prefix + 'agadyn_custom_template_path' + suffix ).value = nothing;
    document.getElementById(prefix + 'agadyn_custom_template_id' + suffix ).value = nothing;
}

function delete_slide(event, identifier)
{
    event.preventDefault();
    jQuery("#" + identifier).remove()
}

function select_media_template(event, identifier, type)
{
	var file_frame;
    var wp_media_post_id = wp.media.model.settings.post.id; 

    event.preventDefault();
    // If the media frame already exists, reopen it.
    if ( file_frame ) {
        // Set the post ID to what we want
        file_frame.uploader.uploader.param( 'post_id', set_to_post_id );
        // Open frame
        file_frame.open();
        return;
    }
    // Create the media frame.
    file_frame = wp.media.frames.file_frame = wp.media({
        title: 'Select media',
        button: {
            text: 'Select',
        },
        multiple: false // Set to true to allow multiple files to be selected
    });
    // When an image is selected, run a callback.
    file_frame.on( 'select', function() {
        // We set multiple to false so only get one image from the uploader
        attachment = file_frame.state().get('selection').first().toJSON();
        // Do something with attachment.id and/or attachment.url here
        console.log(type)
        if (type === 'audio') {
            document.getElementById(identifier).value = attachment.url
        } else {
            insertAtCursor(document.getElementById(identifier),'<img src="' + attachment.url + '" alt="' + attachment.title + '"/>');
        }
        // Restore the main post ID
        wp.media.model.settings.post.id = wp_media_post_id;
    });
    // Finally, open the modal
    file_frame.open();
}

function insertAtCursor(myField, myValue) {
    //IE support
    if (document.selection) {
        myField.focus();
        sel = document.selection.createRange();
        sel.text = myValue;
    }
    //MOZILLA and others
    else if (myField.selectionStart || myField.selectionStart == '0') {
        var startPos = myField.selectionStart;
        var endPos = myField.selectionEnd;
        myField.value = myField.value.substring(0, startPos)
        + myValue
        + myField.value.substring(endPos, myField.value.length);
    } else {
        myField.value += myValue;
    }
}

function insertSlide(event, parent, unique_prefix, unique_suffix)
{
    theKey = window.key
    unique_prefix += window.key
    event.preventDefault();
    data = '\
            <div style="padding-bottom: 5px; padding-top: 5px;" id="slide_' + theKey + '_div">\
                <div style="padding-bottom: 5px; padding-bottom: 5px;">\
                    <label for="' + unique_prefix + '' + unique_suffix + '[time]">Time</label>\
                </div>\
                <div style="padding-bottom: 5px; padding-top: 5px;">\
                    <input name="' + unique_prefix + '' + unique_suffix + '[time]"\
                            id="' + unique_prefix + '' + unique_suffix + '[time]"\
                            type="number" value="" > \
                </div>\
                <div style="padding-bottom: 5px; padding-top: 5px;">\
                    <label for="' + unique_prefix + '' + unique_suffix + '[markup]">HTML for slide</label>\
                </div>\
                <div style="padding-bottom: 5px; padding-top: 5px;">\
                    <textarea name="' + unique_prefix + '' + unique_suffix + '[markup]" \
                            id="' + unique_prefix + '' + unique_suffix + '[markup]"\
                            rows="10"\
                            value="" ></textarea>\
                </div>\
                <input id="upload_image_buttom" type="button" class="button button-primary" value="Select/Upload image" \
                onclick="select_media_template(event, \'' + unique_prefix + unique_suffix + '[markup]\')"/>\
                <input id="upload_template_button" type="button" class="button-secondary delete" value="Delete Slide"\
                onclick="delete_slide(event, \'slide_' + theKey + '_div\')"/>\
            </div>';
    window.key++
    jQuery("#" + parent).append(data)

}