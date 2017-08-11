<?php
/*
Plugin Name: Custom HTML/PHP Post Templates
Plugin URI: http://www.github.com/stephenafamo/html-php-pages-and-posts
Description: Use uploaded html or php files as templates for pages and posts.
Version: 2.0.0
Author: Stephen Afam-Osemene
Author URI: https://www.stephenafamo.com/
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
*/

if (!class_exists("CustomPagesAndPosts")) {
    
    class CustomPagesAndPosts {

        public function __construct ($attributes = [])
        {
            foreach ($attributes as $key => $attribute) {
                $this->$key = $attribute;
            }

            $this->options = get_option($this->plugin_name);
            $this->options['post_types'] = $this->options['post_types'] ? $this->options['post_types'] : $this->default_post_types;

            add_filter( 'mime_types', [$this, 'custom_upload_mimes'], 1);
            add_filter( 'single_template', [$this, 'load_custom_template'], 111, 1);
            add_filter( 'template_include', [$this, 'load_custom_template'], 111, 1);
            add_filter( 'add_meta_boxes', [$this, 'add_custom_meta_box']);
            add_action( 'save_post', [$this, 'save_custom_meta_box'], 10, 2);       
            add_shortcode( 'html_php_page_post', [$this, 'shortcode_parser']); 
            add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_action_links'] );
            add_action( 'admin_menu', [ $this, 'add_settings_menu'] );
            add_action('admin_init', [ $this, 'options_update'] );
            add_action('admin_enqueue_scripts', [ $this, 'media_lib_uploader_enqueue']);
        }

        public function custom_upload_mimes( $existing_mimes ) 
        {
            // add webm to the list of mime types
            $existing_mimes['htm|html'] = 'text/html';
            $existing_mimes['php'] = 'php';
            $existing_mimes['css'] = 'text/css';
            $existing_mimes['js'] = 'application/javascript';
            // return the array back to the function with our added mime type
            return $existing_mimes;
        }

        public function load_custom_template ($single_template)
        {
            global $post;

            if (!in_array($post->post_type, $this->options['post_types']))
                return $single_template;

            $file = wp_upload_dir()['basedir'] . '/' . $this->options['default_settings'][$post->post_type]['agadyn_custom_template_path'];
            $options = $this->options['default_settings'][$post->post_type]['agadyn_custom_template_options'];

            $specific_file = wp_upload_dir()['basedir'] . '/' . get_post_meta($post->ID, '_agadyn_custom_template_path', true);
            
            if(is_file($specific_file))  {
                $file = $specific_file;
                $options = get_post_meta($post->ID, '_agadyn_custom_template_options', true);
            }

            if (is_file($file)){

                switch ($options) {

                    case 'overwrite_all':
                        $single_template = $file;
                        break;

                    case 'overwrite_content':
                        $post->post_content = '[html_php_page_post]';
                        break;

                    case 'below_content':
                        $post->post_content .= '<br> [html_php_page_post]';
                        break;

                    case 'above_content':
                        $post->post_content = '[html_php_page_post] <br>'.$post->post_content;
                        break;
                }
            }
            return $single_template;
        }

        public function shortcode_parser( $atts, $content = null ) 
        {

            global $post;

            $file = wp_upload_dir()['basedir'] . '/' . $this->options['default_settings'][$post->post_type]['agadyn_custom_template_path'];
            $specific_file = wp_upload_dir()['basedir'] . '/' . get_post_meta($post->ID, '_agadyn_custom_template_path', true);
            if(is_file($specific_file)) $file = $specific_file;

            if (is_file($file)){
                // return file_get_contents($file);
                ob_start();
                    require_once $file;
                return ob_get_clean();
            }
        }

        public function add_custom_meta_box()
        {
            $post_types = apply_filters ( 'hppp_post_types', $this->options['post_types']);
            add_meta_box( 
                'agadyn_custom_template', 
                'Custom HTML or PHP', 
                array($this, 'custom_meta_box_markup'),
                $post_types,
                'normal',
                'high');

        }

        public function custom_meta_box_markup($object) 
        {

            $template_post_id = get_post_meta($object->ID, "_agadyn_custom_template_id", true);

            $default_values = [];
            $default_values['agadyn_custom_template_path'] = get_post_meta($object->ID, "_agadyn_custom_template_path", true);
            $default_values['agadyn_custom_template_id'] = $template_post_id;
            $default_values['agadyn_custom_template_options'] = get_post_meta($object->ID, "_agadyn_custom_template_options", true);
            
            wp_nonce_field(basename(__FILE__), "meta-box-nonce");

            echo $this->template_options_markup ($template_post_id, $default_values);

        }

        public function template_options_markup ($template_post_id = 0, $default_values = [], $unique_prefix = null, $unique_suffix = null)
        {
            if (!$template_post_id) $template_post_id = 0;
            if (!$default_values) $default_values = [];

            ?>

                <div>
                    <label for="agadyn_custom_template">Link to custom template</label>


                    <input name="<?php echo $unique_prefix; ?>agadyn_custom_template_path<?php echo $unique_suffix; ?>" id="<?php echo $unique_prefix; ?>agadyn_custom_template_path<?php echo $unique_suffix; ?>" type="text" value="<?php echo $default_values['agadyn_custom_template_path'] ?>" readonly> 
                    <br/>
                    <input id="upload_template_button" type="button" class="button" value="<?php _e( 'Select/Upload template' ); ?>" 
                            onclick="select_template(event, <?php echo "'$unique_prefix', '$unique_suffix'"; ?>)"/>
                    <input id="delete_template_button" type="button" class="button" value="<?php _e( 'Delete template' ); ?>"
                            onclick="delete_template(event, <?php echo "'$unique_prefix', '$unique_suffix'"; ?>)"/>
                    <br/>
                    <input name="<?php echo $unique_prefix; ?>agadyn_custom_template_id<?php echo $unique_suffix; ?>" id="<?php echo $unique_prefix; ?>agadyn_custom_template_id<?php echo $unique_suffix; ?>" type="hidden" value="<?php echo $default_values['agadyn_custom_template_id'] ?>">

                    <br>

                    <label for="agadyn_custom_template_options">Options</label>
                    <select name="<?php echo $unique_prefix; ?>agadyn_custom_template_options<?php echo $unique_suffix; ?>" id="<?php echo $unique_prefix; ?>agadyn_custom_template_options<?php echo $unique_suffix; ?>">
                        <?php 
                            $option_values = [
                                'overwrite_all' => 'Overwrite All', 
                                'overwrite_content' => 'Overwrite Content', 
                                'below_content' => 'Below Content', 
                                'above_content' => 'Above Content'];

                            foreach($option_values as $key => $value) 
                            {
                                if($key == $default_values['agadyn_custom_template_options'])
                                {
                                    ?>
                                        <option value="<?php echo $key?>" selected><?php echo $value; ?></option>
                                    <?php    
                                }
                                else
                                {
                                    ?>
                                        <option value="<?php echo $key?>"><?php echo $value; ?></option>
                                    <?php
                                }
                            }
                        ?>
                    </select>

                    <br>

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

            if(isset($_POST["agadyn_custom_template_id"]))
            {
                $agadyn_custom_template_id = $_POST["agadyn_custom_template_id"];
                $agadyn_custom_template_path = get_post_meta( $_POST["agadyn_custom_template_id"], '_wp_attached_file', true );
            }   
            update_post_meta($post_id, "_agadyn_custom_template_id", $agadyn_custom_template_id);
            update_post_meta($post_id, "_agadyn_custom_template_path", $agadyn_custom_template_path);

            if(isset($_POST["agadyn_custom_template_options"]))
            {
                $agadyn_custom_template_options = $_POST["agadyn_custom_template_options"];
            }   
            update_post_meta($post_id, "_agadyn_custom_template_options", $agadyn_custom_template_options);
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

        public function add_settings_menu()
        {
            add_options_page(
                    'Custom HTML/PHP Post Templates Setting',
                    'Custom templates',
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

            foreach ($input['post_types'] as $key => $post_type) {
                $valid['post_types'][] = filter_var($post_type, FILTER_SANITIZE_STRING);
            }

            foreach ($input['default_settings'] as $post_type => $settings) {
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
          function media_lib_uploader_enqueue() {
            wp_enqueue_media();
            wp_register_script( 'agadyn-media-lib-uploader-js', plugins_url( 'partials/uploader.js' , __FILE__ ), array('jquery') );
            wp_enqueue_script( 'agadyn-media-lib-uploader-js' );
          }
  
    } // end class 
    
} // end check for class

// Begin!!!
$attributes['plugin_name'] = 'html-php-pages-and-posts';
$attributes['default_post_types'] = ["post", "page"];
$class = new CustomPagesAndPosts($attributes);