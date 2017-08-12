<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://stephenafamo.com
 * @since      1.0.0
 *
 * @package    Wp_Mailer_Lite
 * @subpackage Wp_Mailer_Lite/admin/partials
 */


//Grab all options
$args = ['show_ui' => true];
$post_types = get_post_types( $args, 'objects' );
$this->options = get_option($this->plugin_name);
$activated_post_types = apply_filters ( 'hppp_post_types', $this->options['post_types']);;
$default_settings = $this->options['default_settings'];

?>
<!-- This file should primarily consist of HTML with a little bit of PHP. -->

<div class="wrap">

    <h2><?php echo esc_html(get_admin_page_title()); ?></h2>
    
    <form method="post" action="options.php">

    <?php settings_fields($this->plugin_name);?>

        <!-- add post types -->
        <h2>Post types</h2>
        <?php
        foreach ( $post_types as $post_type ) {
           ?>
            <fieldset>
                <legend class="screen-reader-text"><span><?php echo $post_type->label; ?></span></legend>
                <label for="<?php echo $this->plugin_name; ?>-<?php echo $post_type->name; ?>">
                    <input type="checkbox" 
                            id="<?php echo $this->plugin_name;?>-<?php echo $post_type->name; ?>" 
                            name="<?php echo $this->plugin_name; ?>[post_types][]" 
                            value="<?php echo $post_type->name; ?>" 
                             <?php if(in_array($post_type->name, $activated_post_types)) echo 'checked="checked"'; ?> />
                    <span><?php echo $post_type->label; ?></span>
                </label>
            </fieldset>
        <?php } ?>

        <?php foreach ( $post_types as $post_type ): ?>
        <?php if (!in_array($post_type->name, $activated_post_types)) continue; ?>
            <h2>Default Settings for <?php echo $post_type->label; ?></h2>
            <?php echo $this->template_options_markup (null, $default_settings[$post_type->name], $this->plugin_name."[default_settings][$post_type->name][", "]"); ?>
        <?php endforeach; ?>

        <?php
        submit_button('Save all changes', 'primary','submit', TRUE); ?>

    </form>
</div>
