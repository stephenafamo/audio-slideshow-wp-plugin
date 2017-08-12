<?php
/*
Plugin Name: Audio Slideshow
Plugin URI: http://www.github.com/stephenafamo/audio-slideshow
Description: Create a slideshow that is tied to an audio file.
Version: 1.0.0
Author: Stephen Afam-Osemene
Author URI: https://www.stephenafamo.com/
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
*/

if (!class_exists("AudioSlideshow")) {

    class AudioSlideshow {

        public function __construct ($attributes = [])
        {
            foreach ($attributes as $key => $attribute) {
                $this->$key = $attribute;
            }

            $this->options = get_option($this->plugin_name);
            $this->options['post_types'] = [];

            // add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_action_links'] );
            add_filter( 'mime_types', [$this, 'custom_upload_mimes'], 1);
            add_filter( 'add_meta_boxes', [$this, 'add_custom_meta_box']);
            add_action( 'save_post', [$this, 'save_custom_meta_box'], 10, 2);    
            // add_action( 'admin_menu', [ $this, 'add_settings_menu'] );
            // add_action( 'admin_init', [ $this, 'options_update'] );
            add_action( 'admin_enqueue_scripts', [ $this, 'media_lib_uploader_enqueue']);
            add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend'] );
            add_action( 'init',  [ $this, 'slideshow_init']);
            add_action( 'edit_form_after_title',  [ $this,  'embedding_instructions'] );
            add_filter( 'gettext', [ $this,  'change_publish_button'], 10, 2 );

            add_shortcode( 'audio_slideshow', [$this, 'shortcode_parser']); 
        }

        public function add_action_links( $links ) 
        {
            /*
            *  Documentation : https://codex.wordpress.org/Plugin_API/Filter_Reference/plugin_action_links_(plugin_file_name)
            */
            $settings_link = array(
                '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_name ) . '">Settings</a>',
                );
            return array_merge(  $settings_link, $links );
        }

        public function custom_upload_mimes( $existing_mimes ) 
        {
            // add webm to the list of mime types
            if (!in_array("application/javascript", $existing_mimes))
                $existing_mimes['js'] = 'application/javascript';
            // return the array back to the function with our added mime type
            return $existing_mimes;
        }

        public function embedding_instructions( $post ) 
        { 
            if ($post->post_type !== 'slideshow' && $post->id !== null) return;
            ?>
            <div class="after-title-help postbox">
                <h3>Using the slideshow</h3>
                <div class="inside">
                    <p>Embed this in any post by pasting the following shortcode.</p>
                    <pre>[audio_slideshow id=<?= $post->ID ?>]</pre>
                </div><!-- .inside -->
            </div><!-- .postbox -->
            <?php 
        }

        public function add_custom_meta_box()
        {
            $post_types = apply_filters ( 'hppp_post_types', $this->options['post_types']);
            add_meta_box( 
                'agadyn_custom_template', 
                'Sldeshow Details', 
                array($this, 'custom_meta_box_markup'),
                'slideshow',
                'normal',
                'high');

        }

        public function custom_meta_box_markup($object) 
        {
            $audio_slideshow_audio = get_post_meta($object->ID, "_audio_slideshow_audio", true);
            $audio_slideshow_audio_type = get_post_meta($object->ID, "_audio_slideshow_audio_type", true);
            $audio_slideshow_slides = get_post_meta($object->ID, "_audio_slideshow_slides", true);
            $slides_div = "slides_div";

            wp_nonce_field(basename(__FILE__), "meta-box-nonce");
            ?>
            <h3>Audio</h3>
            <div style="padding-bottom: 5px; padding-top: 5px;">
                <div style="padding-bottom: 5px; padding-top: 5px;">
                    <label for="audio_slideshow_audio">Url for audio</label>
                </div>
                <div style="padding-bottom: 5px; padding-top: 5px;">
                    <input name="audio_slideshow_audio" 
                    id="audio_slideshow_audio" 
                    type="text" value="<?= $audio_slideshow_audio ?>" > 
                </div>
                <input id="upload_template_button" type="button" class="button button-primary" value="<?php _e( 'Select/Upload audio' ); ?>" 
                onclick="select_media_template(event, <?php echo "'audio_slideshow_audio'"; ?>, 'audio')"/>
                <div style="padding-bottom: 5px; padding-top: 15px;">
                    <label for="audio_slideshow_audio_type">Audio Type</label>
                </div>
                <div style="padding-bottom: 5px; padding-top: 5px;">
                    <select name="audio_slideshow_audio_type" id="audio_slideshow_audio_type" >
                        <option value="audio/mpeg"<?php if($audio_slideshow_audio_type == "audio/mpeg") echo "selected" ?> >MP3</option>
                        <option value="audio/wav"<?php if($audio_slideshow_audio_type == "audio/wav") echo "selected" ?> >WAV</option>
                        <option value="audio/ogg"<?php if($audio_slideshow_audio_type == "audio/ogg") echo "selected" ?> >OGG</option>
                    </select>
                </div>
            </div>
            <h3>Slides</h3>
            <div id="<?= $slides_div ?>">

            <?php

            if(is_array($audio_slideshow_slides)) {
                foreach ($audio_slideshow_slides as $key => $slide) {
                    echo $this->slides_insert_markup ( $slide, "audio_slideshow_slides[$key", "]", $key);
                }
                $key++;
            } else $key = 0;
            ?> 
            </div> 
            <div style="padding-bottom: 5px; padding-top: 25px;" > 
            <script type="text/javascript">
                window.key = <?= ++$key ?>;
            </script>

            <input id="upload_template_button" type="button" class="button button-primary" value="<?php _e( 'ADD SLIDE' ); ?>" 
            onclick="insertSlide(event, <?= "'$slides_div', 'audio_slideshow_slides[', ']')" ?>"/>
            </div> 
            <?php
        }

        public function slides_insert_markup ($default_values = [], $unique_prefix = null, $unique_suffix = null, $key)
        {
            if (!$default_values) $default_values = [];
            ?>

            <div style="padding-bottom: 5px; padding-top: 5px;" id="slide_<?= $key ?>_div">
                <div style="padding-bottom: 5px; padding-bottom: 5px;">
                    <label for="<?= $unique_prefix; ?><?= $unique_suffix; ?>[time]">Time</label>
                </div>
                <div style="padding-bottom: 5px; padding-top: 5px;">
                    <input name="<?= $unique_prefix; ?><?= $unique_suffix; ?>[time]"
                            id="<?= $unique_prefix; ?><?= $unique_suffix; ?>[time]"
                            type="number" value="<?= $default_values['time'] ?>" > 
                </div>
                <div style="padding-bottom: 5px; padding-top: 5px;">
                    <label for="<?= $unique_prefix; ?><?= $unique_suffix; ?>[markup]">HTML for slide</label>
                </div>
                <div style="padding-bottom: 5px; padding-top: 5px;">
                    <textarea name="<?= $unique_prefix; ?><?= $unique_suffix; ?>[markup]" 
                            id="<?= $unique_prefix; ?><?= $unique_suffix; ?>[markup]"
                            rows="10"
                            value="" ><?= $default_values['markup'] ?></textarea>
                </div>
                <input id="upload_image_buttom" type="button" class="button button-primary" value="<?php _e( 'Select/Upload image' ); ?>" 
                onclick="select_media_template(event, <?= "'{$unique_prefix}{$unique_suffix}[markup]'"; ?>)"/>

                <input id="upload_template_button" type="button" class="button-secondary delete" value="<?php _e( 'Delete Slide' ); ?>" 
                onclick="delete_slide(event, <?= "'slide_{$key}_div'"; ?>)"/>

            </div>

            <?php
        }

        public function save_custom_meta_box($post_id, $post)
        {

            if (!isset($_POST["meta-box-nonce"]) || !wp_verify_nonce($_POST["meta-box-nonce"], basename(__FILE__)))
                return $post_id;

            if(!current_user_can("edit_post", $post_id))
                return $post_id;

            if(defined("DOING_AUTOSAVE") && DOING_AUTOSAVE)
                return $post_id;

            if(isset($_POST["audio_slideshow_audio"]))
            {
                $audio_slideshow_audio = $_POST["audio_slideshow_audio"];
            }   
            update_post_meta($post_id, "_audio_slideshow_audio", $audio_slideshow_audio);

            if(isset($_POST["audio_slideshow_audio_type"]))
            {
                $audio_slideshow_audio_type = $_POST["audio_slideshow_audio_type"];
            }   
            update_post_meta($post_id, "_audio_slideshow_audio_type", $audio_slideshow_audio_type);

            if(isset($_POST["audio_slideshow_slides"]) && is_array($_POST["audio_slideshow_slides"]))
            {
                $audio_slideshow_slides = array_values($_POST["audio_slideshow_slides"]);
            }   
            update_post_meta($post_id, "_audio_slideshow_slides", $audio_slideshow_slides);
        }

        public function change_publish_button( $translation, $text ) 
        {
            if ( 'slideshow' == get_post_type() && ($text == 'Publish' || $text == 'Update' ))
                return 'Save';

            return $translation;
        }

        public function add_settings_menu()
        {
            add_options_page(
                'Audio Slideshow Settings',
                'Audio Slideshow',
                'manage_options',
                $this->plugin_name,
                [ $this, 'settings_page']
                );
        }

        function  settings_page() 
        {
            include_once( 'partials/settings-page-display.php' );
        }

        function options_update() 
        {
            register_setting($this->plugin_name, $this->plugin_name, array($this, 'validate_options'));
        }

        public function validate_options($input) 
        {       
            $valid = [];

            foreach ($input['post_types'] as $key => $post_type) 
            {
                $valid['post_types'][] = filter_var($post_type, FILTER_SANITIZE_STRING);
            }

            foreach ($input['default_settings'] as $post_type => $settings) 
            {
                $agadyn_custom_template_id = '';
                $agadyn_custom_template_path = '';
                $agadyn_custom_template_options = '';
                if(isset($settings["agadyn_custom_template_id"]))
                {
                    $agadyn_custom_template_id = $settings["agadyn_custom_template_id"];
                    $agadyn_custom_template_path = get_post_meta( $settings["agadyn_custom_template_id"], '_wp_attached_file', true );

                }
                if(isset($settings["agadyn_custom_template_options"]))
                {
                    $agadyn_custom_template_options = $settings["agadyn_custom_template_options"];
                }   
                $valid['default_settings'][$post_type]['agadyn_custom_template_id'] = $agadyn_custom_template_id;
                $valid['default_settings'][$post_type]['agadyn_custom_template_path'] = $agadyn_custom_template_path;
                $valid['default_settings'][$post_type]['agadyn_custom_template_options'] = $agadyn_custom_template_options;
            }

            // var_dump($input, $valid);

            return $valid;
        }

        /* Add the media uploader script */
        function media_lib_uploader_enqueue() 
        {
            wp_enqueue_media();
            wp_register_script( 'audio-slideshow-media-lib-uploader-js', plugins_url( 'partials/uploader.js' , __FILE__ ), array('jquery') );
            wp_enqueue_script( 'audio-slideshow-media-lib-uploader-js' );
        }

        function enqueue_frontend() 
        {
            wp_register_script( 'audio-slideshow-js', plugins_url( 'partials/audio-slideshow-0.1.0.js' , __FILE__ ), [],  '0.1.0', true);
            wp_enqueue_script( 'audio-slideshow-js' );
        }

        function slideshow_init() {
            $labels = array(
                'name'               => _x( 'Slideshows', 'post type general name', 'your-plugin-textdomain' ),
                'singular_name'      => _x( 'Slideshow', 'post type singular name', 'your-plugin-textdomain' ),
                'menu_name'          => _x( 'Slideshows', 'admin menu', 'your-plugin-textdomain' ),
                'name_admin_bar'     => _x( 'Slideshow', 'add new on admin bar', 'your-plugin-textdomain' ),
                'add_new'            => _x( 'Add New', 'slideshow', 'your-plugin-textdomain' ),
                'add_new_item'       => __( 'Add New Slideshow', 'your-plugin-textdomain' ),
                'new_item'           => __( 'New Slideshow', 'your-plugin-textdomain' ),
                'edit_item'          => __( 'Edit Slideshow', 'your-plugin-textdomain' ),
                'view_item'          => __( 'View Slideshow', 'your-plugin-textdomain' ),
                'all_items'          => __( 'All Slideshows', 'your-plugin-textdomain' ),
                'search_items'       => __( 'Search Slideshows', 'your-plugin-textdomain' ),
                'parent_item_colon'  => __( 'Parent Slideshows:', 'your-plugin-textdomain' ),
                'not_found'          => __( 'No slideshows found.', 'your-plugin-textdomain' ),
                'not_found_in_trash' => __( 'No slideshows found in Trash.', 'your-plugin-textdomain' )
                );

            $args = array(
                'labels'             => $labels,
                'description'        => __( 'Description.', 'your-plugin-textdomain' ),
                'public'             => false,
                'publicly_queryable' => false,
                'show_ui'            => true,
                'show_in_menu'       => true,
                'query_var'          => true,
                'rewrite'            => array( 'slug' => 'slideshows' ),
                'capability_type'    => 'post',
                'has_archive'        => false,
                'hierarchical'       => false,
                'menu_position'      => null,
                'supports'           => array( 'title', 'author' )
                );

            register_post_type( 'slideshow', $args );
        }

        public function shortcode_parser( $atts, $content = null ) 
        {
            $a = shortcode_atts( array(
                'id' => null,
                ), $atts );

            if (!$a['id']) return;

            $audio_slideshow_audio = get_post_meta($a['id'], "_audio_slideshow_audio", true);
            $audio_slideshow_audio_type = get_post_meta($a['id'], "_audio_slideshow_audio_type", true);
            $audio_slideshow_slides = get_post_meta($a['id'], "_audio_slideshow_slides", true);

            ob_start(); ?>
            <div>
                <audio controls data-audio-show="audio_slideshow_slides"> <source src="<?= $audio_slideshow_audio ?>" type="<?= $audio_slideshow_audio_type ?>" /> </audio>
            </div> 
            <div id="audio_slideshow_slides">
                <?php
                if(is_array($audio_slideshow_slides)) {
                    foreach ($audio_slideshow_slides as $key => $slide) {
                        ?>
                        <div data-slide-start="<?= $slide['time'] ?>" id="<?= $key ?>" style="display: none;"><?= $slide['markup'] ?></div>
                        <?php
                    }
                } 
                ?>
            </div> <?php
            return ob_get_clean();
        }

    } // end class 
    
} // end check for class

// Begin!!!
$attributes = [];
$attributes['plugin_name'] = 'audio-slideshow';
$class = new AudioSlideshow($attributes);