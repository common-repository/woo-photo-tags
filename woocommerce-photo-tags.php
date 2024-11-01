<?php
/**
 * Plugin Name: Woocommerce Photo Tags
 * Plugin URI: https://taggable.co.za/woocommerce-photo-tags/
 * Description: Tag your store's products on any photo on your site.
 * Version: 0.1.2
 * Author: Taggable
 * Author URI: https://taggable.co.za/
 * License: GPL2
 */



//function to run on activation
function woocommerce_photo_tags_activate() {

    if( !class_exists( 'WooCommerce' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( __( 'This plugin requires WooCommerce to be installed and activated. Please activate WooCommerce.', 'woocommerce-addon-slug' ), 'Plugin dependency check', array( 'back_link' => true ) );
    }
}

//sets up activation hook
register_activation_hook(__FILE__, 'woocommerce_photo_tags_activate');

$dir = plugin_dir_path( __FILE__ );

global $wcpt_arr_photos,$wcpt_product;
global $wcpt_featured_products;
global $is_wcpt_featured_products;
$wcpt_arr_photos        = array();
$wcpt_featured_products = array();


function wcpt_enqueue_tools() {
    wp_enqueue_style('font-awesome', 'https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css',false,false,'screen,projection' );
    wp_register_script('designtag_tools', plugins_url( 'js/designtags.min.js', __FILE__ ) , array('jquery'),false,true );
    wp_enqueue_script('designtag_tools');
    wp_register_script('spin', plugins_url( 'js/spin.min.js', __FILE__ ), array('jquery','designtag_tools'),false,true);
    wp_enqueue_script('spin');
    //wp_register_script('jquery-ui', 'https://code.jquery.com/ui/1.12.0/jquery-ui.min.js', array('jquery'),false,true );
	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-core');
	wp_enqueue_script('jquery-ui-draggable');
	wp_enqueue_script('jquery-ui-droppable');
    $wcpt_ga = (esc_attr( get_option('wcpt-ga') ))? esc_attr( get_option('wcpt-ga',0) ) : 0;
    $wcpt_preload = (esc_attr( get_option('wcpt-preload') ))? esc_attr( get_option('wcpt-preload') ) : 0;
    wp_localize_script( 'designtag_tools', 'designtag_vars', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),'wcpt_ga' => $wcpt_ga,'wcpt_preload'=>$wcpt_preload
    ));
    //wp_enqueue_style('jquery-ui','https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css','','','screen, projection');
    wp_enqueue_style('designbook-tags',plugins_url( 'css/woocommerce-photo-tags.min.css', __FILE__ ),'',plugin_dir_path('/css/woocommerce-photo-tags.min.css' ),'screen, projection');

    $wcpt_ga = (get_option('wcpt-bootstrap'))? get_option('wcpt-bootstrap',1)  : 0;
    wp_enqueue_style('wcpt-bootstrap-css',plugins_url( 'css/bootstrap.min.css', __FILE__ ),'',plugin_dir_path('/css/bootsrap.min.css' ),'screen, projection');


}

add_action("wp_enqueue_scripts", "wcpt_enqueue_tools");

add_action( 'admin_menu', 'wcpt_admin_menu' );

function wcpt_admin_menu() {
    $parent_slug = 'edit.php?post_type=wc-photo-tag';
    $page_title = 'Woocommerce Photo Tags Settings';
    $menu_title = 'Settings';
    $capability = 'manage_options';
    $menu_slug = 'wcpt-settings';
    $function = 'wcpt_build_option_page';
    add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);
    add_action( 'admin_init', 'wcpt_register_settings' );
}

function wcpt_register_settings() {
    $arr_size_defaults = array();
    foreach (get_intermediate_image_sizes() as $size) {
        if (!preg_match('/(woocommerce|shop|thumbnail|medium)/',$size)) {
        $arr_size_defaults[$size] = 1;
        }

    }
    register_setting( 'wcpt-tag-options', 'wcpt-singular',array('default' => 1) );
    register_setting( 'wcpt-tag-options', 'wcpt-archive',array('default' => 1) );
    register_setting( 'wcpt-tag-options', 'wcpt-border-radius', array('default' => 0,'sanitize_callback'=>'wcpt_options_integer') );
    register_setting( 'wcpt-tag-options', 'wcpt-product-orig-icon',array('default' => 'fa fa-plus-circle') );
    register_setting( 'wcpt-tag-options', 'wcpt-product-orig-size',array('default' => 20,'sanitize_callback'=>'wcpt_options_integer') );
    register_setting( 'wcpt-tag-options', 'wcpt-product-orig-colour',array('default' => '#FFFFFF','sanitize_callback'=>'sanitize_hex_color') );
    register_setting( 'wcpt-tag-options', 'wcpt-product-orig-label',array('default' => 'Original Product') );
    register_setting( 'wcpt-tag-options', 'wcpt-product-orig-label-colour',array('default' => '#000000','sanitize_callback'=>'sanitize_hex_color') );

    register_setting( 'wcpt-tag-options', 'wcpt-product-look-icon',array('default' => 'fa fa-plus-circle') );
    register_setting( 'wcpt-tag-options', 'wcpt-product-look-size',array('default' => 20,'sanitize_callback'=>'wcpt_options_integer') );
    register_setting( 'wcpt-tag-options', 'wcpt-product-look-colour', array('default' => '#FFFFFF','sanitize_callback'=>'sanitize_hex_color') );
    register_setting( 'wcpt-tag-options', 'wcpt-product-look-label',array('default' => 'Suggested Product') );
    register_setting( 'wcpt-tag-options', 'wcpt-product-look-label-colour',array('default' => '#000000','sanitize_callback'=>'sanitize_hex_color') );
    register_setting( 'wcpt-tag-options', 'wcpt-product-orig-animate' );
    register_setting( 'wcpt-tag-options', 'wcpt-product-look-animate' );
    /*register_setting( 'wcpt-tag-options', 'wcpt-user-animate' );
    register_setting( 'wcpt-tag-options', 'wcpt-custom-animate' );
    register_setting( 'wcpt-tag-options', 'wcpt-user-icon' );
    register_setting( 'wcpt-tag-options', 'wcpt-user-size' );
    register_setting( 'wcpt-tag-options', 'wcpt-user-colour' );
    register_setting( 'wcpt-tag-options', 'wcpt-custom-icon' );
    register_setting( 'wcpt-tag-options', 'wcpt-custom-size' );
    register_setting( 'wcpt-tag-options', 'wcpt-custom-colour' );*/

    register_setting( 'wcpt-tag-options', 'wcpt-tagtool-results-count',array('default' => 10,'sanitize_callback'=>'wcpt_options_integer'));
    register_setting( 'wcpt-tag-options', 'wcpt-ga',array('default' => 1));
    register_setting( 'wcpt-tag-options', 'wcpt-preload',array('default' => 1));
    register_setting( 'wcpt-tag-options', 'wcpt-product-show-box',array('default' => 1));
    register_setting( 'wcpt-tag-options', 'wcpt-image-sizes', array('default'=>$arr_size_defaults));
    register_setting( 'wcpt-tag-options', 'wcpt-bootstrap', array('default'=>1));
    register_setting( 'wcpt-tag-options', 'wcpt-product-tab', array('default'=>1));
    register_setting( 'wcpt-tag-options', 'wcpt-product-tab-heading', array('default'=>'Photo Tags'));
    register_setting( 'wcpt-tag-options', 'wcpt-product-default', array('default'=>1));

}

function wcpt_options_integer($input) {
    $output = (preg_match('/\d+/',$input)) ? $input : '';
	return $output;
}

function wcpt_build_option_page() {

    echo '
<div class="wrap">
        <h1 class="wp-heading-inline">Woocommerce Photo Tag Settings</h1>
        <form method="post" action="options.php">';

        settings_fields( 'wcpt-tag-options' );
        do_settings_sections( 'wcpt-tag-options' );

        echo '
        <h3>Tag Options</h3>
        <table class="form-table">
        <tr valign="top">
        <th></th><th>Font-Awesome Icon</th><th>Size</th><th>Colour</th><th>Label</th><th>Label Colour</th><th>Animation</th>
        </tr>
        <tr valign="top">
            <th><label>Original product</label> </th>
            <td><input type="text" name="wcpt-product-orig-icon" value="'.esc_attr( get_option('wcpt-product-orig-icon','fa fa-plus-circle')).'" /></td>
            <td><input type="text" name="wcpt-product-orig-size" style="text-align: right; width:50px;" value="'.esc_attr( get_option('wcpt-product-orig-size',20) ).'" /> px </td>
            <td><input type="text" name="wcpt-product-orig-colour" value="'.esc_attr( get_option('wcpt-product-orig-colour','#ffffff') ).'" /></td>
            <td><input type="text" name="wcpt-product-orig-label" value="'.esc_attr( get_option('wcpt-product-orig-label','Original Product') ).'" /></td>
            <td><input type="text" name="wcpt-product-orig-label-colour" value="'.esc_attr( get_option('wcpt-product-orig-label-colour','#000000') ).'" /></td>
            <td>'.wcpt_animate_dd('wcpt-product-orig-animate',esc_attr( get_option('wcpt-product-orig-animate') )).'</td>
        </tr>
         
       <tr valign="top">
            <th><label>Lookalike product</label> </th>
            <td><input type="text" name="wcpt-product-look-icon" value="'.esc_attr( get_option('wcpt-product-look-icon') ).'" /></td>
            <td><input type="text" name="wcpt-product-look-size" style="text-align: right;width:50px;" value="'.esc_attr( get_option('wcpt-product-look-size',20) ).'" /> px </td>
            <td><input type="text" name="wcpt-product-look-colour" value="'.esc_attr( get_option('wcpt-product-look-colour','#ffffff') ).'" /></td>
            <td><input type="text" name="wcpt-product-look-label" value="'.esc_attr( get_option('wcpt-product-look-label','Suggested Product') ).'" /></td>
            <td><input type="text" name="wcpt-product-look-label-colour" value="'.esc_attr( get_option('wcpt-product-look-label-colour','#000000') ).'" /></td>
            <td>'.wcpt_animate_dd('wcpt-product-look-animate',esc_attr( get_option('wcpt-product-look-animate') )).'</td>
        </tr>
        <tr>
        <td></td>
        <td colspan="6"><i>Woocommerce Photo Tag icons are <a href="https://fontawesome.com/icons?d=gallery&m=free" target="_blank"> FontAwesome icons</a>. Use the above settings to select your own icon shapes and colours.</i></td>
        </tr>
        
        <!--<tr valign="top">
            <th><label>User</label> </th>
            <td>fa <input type="text" name="wcpt-user-icon" value="'.esc_attr( get_option('wcpt-user-icon') ).'" /></td>
            <td><input type="text" name="wcpt-user-size" value="'.esc_attr( get_option('wcpt-user-size') ).'" /> px </td>
            <td><input type="text" name="wcpt-user-colour" value="'.esc_attr( get_option('wcpt-user-colour') ).'" /></td>
            <td>'.wcpt_animate_dd('wcpt-user-animate',esc_attr( get_option('wcpt-user-animate') )).'</td>
        </tr>
        <tr valign="top">
            <th><label>Custom</label> </th>
            <td>fa <input type="text" name="wcpt-custom-icon" value="'.esc_attr( get_option('wcpt-custom-icon') ).'" /></td>
            <td><input type="text" name="wcpt-custom-size" value="'.esc_attr( get_option('wcpt-custom-size') ).'" /> px </td>
            <td><input type="text" name="wcpt-custom-colour" value="'.esc_attr( get_option('wcpt-custom-colour') ).'" /></td>
            <td>'.wcpt_animate_dd('wcpt-custom-animate',esc_attr( get_option('wcpt-custom-animate') )).'</td>
        </tr> -->
         <tr>
        <th class="row">Use default product style</th>
        
        <td colspan="6"><input type="checkbox" name="wcpt-product-default" value="1" '.checked(esc_attr( get_option('wcpt-product-default',1) ),1,false).'/> &nbsp;<i>Use the default catalogue style for product tags (recommended). Disable this use the plugin template which you can override in your own theme. </i></td>
        </tr>
         <tr>
        <th class="row">Popup box</th>
        
        <td colspan="6"><input type="checkbox" name="wcpt-product-show-box" value="1" '.checked(esc_attr( get_option('wcpt-product-show-box',1) ),1,false).'/> &nbsp;<i>Create a bounding box for the tag popup with a themed heading, as per the settings above.</i></td>
        </tr>
        <tr>
        <th class="row">Pop-up border radius</th>
        
        <td colspan="6">
            <input type="text" name="wcpt-border-radius" style="text-align: right;" value="'.esc_attr( get_option('wcpt-border-radius',0) ).'" /> px <br>
            <i>Round the edges of your popup box to suit your theme.</i>
        </td>
        </tr>
        
        <!--<th class="row">Show on single posts</th>
        
        <td colspan="4"><input type="checkbox" name="wcpt-singular" value="1" '.checked(esc_attr( get_option('wcpt-singular',1) ),1,false).'/></td>
        </tr>
        <tr>
        <th class="row">Show in archives</th>
        
        <td colspan="4"><input type="checkbox" name="wcpt-archive" value="1" '.checked(esc_attr( get_option('wcpt-archive',1) ),1,false).'/></td>
        </tr>-->
        <tr>
        <th class="row">Product Tab</th>
        
        <td colspan="6"><input type="checkbox" name="wcpt-product-tab" value="1" '.checked(esc_attr( get_option('wcpt-product-tab',1) ),1,false).'/> &nbsp;<i>Add a tab to your single product view that shows all images and posts linked to that product using Woocommerce Photo Tags.</i> </td>
        </tr>
        <tr>
        <th class="row">Product Tab Heading</th>
        
        <td colspan="6"><input type="text" name="wcpt-product-tab-heading" value="'.esc_attr( get_option('wcpt-product-tab-heading','Photo Tags') ).'" /><br>
            <i>Choose a heading for the product tab on your product page.</i>
        </td>
        </tr>
        <tr>
        <th class="row">Load Bootstrap css</th>
        
        <td colspan="6"><input type="checkbox" name="wcpt-bootstrap" value="1" '.checked(esc_attr( get_option('wcpt-bootstrap',1) ),1,false).'/><i>Woocommerce Photo Tags uses Bootstrap css to create responsive grids. Disable this if you are having layout problems on the product tab.</i></td>
        </tr>

        <tr>
        <th class="row">Send events to Google Analytics</th>
        
        <td colspan="6"><input type="checkbox" name="wcpt-archive" value="1" '.checked(esc_attr( get_option('wcpt-ga',0) ),1,false).'/>&nbsp; <i>When enabled, Woocommerce Photo Tags will send interaction events to Google Analytics when users interact with your tags.</i></td>
        </tr>
        <tr>
        <th class="row">Preload Tags</th>
        
        <td colspan="6"><input type="checkbox" name="wcpt-preload" value="1" '.checked(esc_attr( get_option('wcpt-preload',1) ),1,false).'/> &nbsp;<i>Load tags when the page loads (recommended), not via ajax. Disable and test if you notice perfomance issues.</i></td>
        </tr><tr>
        <th class="row">Image sizes</th>
        
        <td colspan="6">        
    ';

    foreach (get_intermediate_image_sizes() as $size) {
        $size_options = get_option('wcpt-image-sizes');
        $val = $size_options[$size];
        echo '<input type="checkbox" name="wcpt-image-sizes['.$size.']" value="1" '.checked(esc_attr($val),1,false).'/><label for="wcpt-image-sizes['.$size.']">'.$size.'</label>&nbsp;&nbsp;&nbsp;';
    }


    echo '</td>
        </tr>
        <tr>
        <td></td>
        <td colspan="6"><i>Select  image sizes on which to load the tags. Typically tags are suitable for larger images. Woocommerce product image sizes are deselected by default.</i> </td>
        </tr>
        </table>        
    <h3>Tagtool Options</h3>
    <table class="form-table">
        <tr>
        <th class="row">Products per page</th>
        <td><input type="text" name="wcpt-tagtool-results-count" value="'.esc_attr( get_option('wcpt-tagtool-results-count',12) ).'" /><br>
        <i>Select how many products to return per page on the tagging tool. Some themes may override the tagtool pagination, in this case set the tool to return more products, and use the filters to narrow your results.</i>
        </td>
        </tr>
        
    </table>
    ';

    submit_button();

    echo '</form></div>';


}

function wcpt_animate_dd($option,$selected) {
    $select = '<select name="'.$option.'" class="wcpt-options"><option value="">None</option>';
    $options['swingimage'] = 'Swing';
    //$options['pulseimage'] = 'Pulse';

    foreach ($options as $key => $value) {
        $selected_text = ($key == $selected) ? ' selected' : '';
        $select.= '<option value="'.$key.'" '.$selected_text.'>'.$value.'</option>';
    }
    $select .= '</select>';
    return $select;
}

add_action('init', 'wcpt_wc_photo_tags', 0);
function wcpt_wc_photo_tags() {
    $labels = array(
        'name' => 'Photo Tags',
        'singular_name' => 'Photo Tag',
        'add_new' => 'Add Photo Tag',
        'add_new_item' => 'Add New Photo Tag',
        'edit_item' => 'Edit Photo Tag',
        'new_item' => 'New Photo Tag',
        'all_items' => 'All Photo Tags',
        'view_item' => 'View Photo Tag',
        'search_items' => 'Search Photo Tags',
        'not_found' => 'No Photo Tags found',
        'not_found_in_trash' => 'No Photo Tags found in Trash',
        'parent_item_colon' => '',
        'menu_name' => 'Photo Tags',
    );
    $args = array(
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'query_var' => true,
        'rewrite' => true,
        'has_archive' => true,
        'capability_type' => 'post',
        'menu_position' => null,
        //'taxonomies' => array('photo_category'),
        'supports' => array('title', 'thumbnail', 'author', 'editor')
    );
    register_post_type('wc-photo-tag',$args);
}

function wcpt_add_fields_meta_box() {
    add_meta_box(
        'wcpt_fields_meta_box', // $id
        'Photo Tag Fields', // $title
        'wcpt_show_fields_meta_box', // $callback
        'wc-photo-tag', // $screen
        'normal', // $context
        'high' // $priority
    );
}
add_action( 'add_meta_boxes', 'wcpt_add_fields_meta_box' );

function wcpt_show_fields_meta_box() {
    global $post;
    $meta = get_post_meta( $post->ID,'',false ); ?>

    <input type="hidden" name="wcpt_meta_box_nonce" value="<?php echo wp_create_nonce( basename(__FILE__) ); ?>">

    <!-- All fields will go here -->
    <p>
        <label for="wcpt[x-position]">X position</label>
        <br>
        <input type="text" name="wcpt[x-position]" id="wcpt[x-position]" class="regular-text" value="<?php esc_attr_e($meta['wcpt-x-position'][0]) ; ?>">
    </p>
    <p>
        <label for="wcpt[y-position]">Y position</label>
        <br>
        <input type="text" name="wcpt[y-position]" id="wcpt[y-position]" class="regular-text" value="<?php esc_attr_e($meta['wcpt-y-position'][0]) ; ?>">
    </p>
    <p>
        <label for="wcpt[original]">Original</label>
        <br>
        <input type="checkbox" name="wcpt[original]" id="wcpt[original]"  value="1" <?php echo ($meta['wcpt-original'][0]) ? 'checked' : ''; ?>>
    </p>
    <p>
        <label for="wcpt[parent-image-width]">Parent Image Width</label>
        <br>
        <input type="text" name="wcpt[parent-image-width]" id="wcpt[parent-image-width]" class="regular-text" value="<?php esc_attr_e($meta['wcpt-parent-image-width'][0]) ; ?>">
    </p>
    <p>
        <label for="wcpt[parent-image-height]">Parent Image Height</label>
        <br>
        <input type="text" name="wcpt[parent-image-height]" id="wcpt[parent-image-height]" class="regular-text" value="<?php esc_attr_e($meta['wcpt-parent-image-height'][0]) ; ?>">
    </p>
    <p>
        <label for="wcpt[tag-width]">Tag Width</label>
        <br>
        <input type="text" name="wcpt[tag-width]" id="wcpt[tag-width]" class="regular-text" value="<?php esc_attr_e($meta['wcpt-tag-width'][0]) ; ?>">
    </p>
    <p>
        <label for="wcpt[tag-height]">Tag Height</label>
        <br>
        <input type="text" name="wcpt[tag-height]" id="wcpt[tag-height]" class="regular-text" value="<?php esc_attr_e($meta['wcpt-tag-height'][0]) ; ?>">
    </p>
    <p>
        <label for="wcpt[tag-type]">Tag Type</label>
        <br>
        <select id="wcpt[tag-type]" class=""  name="wcpt[tag-type]">
            <option value="product" <?php echo ($meta['wcpt-tag-type'][0] == "product") ? 'selected' : ''; ?>>Product</option>
            <option value="user" <?php echo ($meta['wcpt-tag-type'][0] == "user") ? 'selected' : ''; ?>>User</option>
            <option value="custom" <?php echo ($meta['wcpt-tag-type'][0] == "custom") ? 'selected' : ''; ?>>Custom</option>
        </select>
        </p>
    <p>
        <label for="wcpt[product-id]">Product ID</label>
        <br>
        <input type="text" name="wcpt[product-id]" id="wcpt[product-id]" class="regular-text" value="<?php esc_attr_e($meta['wcpt-product-id'][0]) ; ?>">
    </p>
    <p>
        <label for="wcpt[variation-id]">Variation ID</label>
        <br>
        <input type="text" name="wcpt[variation-id]" id="wcpt[variation-id]" class="regular-text" value="<?php esc_attr_e($meta['wcpt-variation-id'][0]) ; ?>">
    </p>
    <p>
        <label for="wcpt[tagged-user-id]">Tagged User ID</label>
        <br>
        <input type="text" name="wcpt[tagged-user-id]" id="wcpt[tagged-user-id]" class="regular-text" value="<?php esc_attr_e($meta['wcpt-tagged-user-id'][0]) ; ?>">
    </p>

    <p>
        <label for="wcpt[attachment-id]">Media Attachment ID</label>
        <br>
        <input type="text" name="wcpt[attachment-id]" id="wcpt[attachment-id]" class="regular-text" value="<?php esc_attr_e($meta['wcpt-attachment-id'][0]) ; ?>">
    </p>

<?php }

function wcpt_save_fields_meta( $post_id ) {
    // verify nonce
    if ( !wp_verify_nonce( sanitize_text_field($_POST['wcpt_meta_box_nonce']), basename(__FILE__) ) ) {
        return $post_id;
    }
    // check autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return $post_id;
    }
    // check permissions
    if ( 'page' === $_POST['post_type'] ) {
        if ( !current_user_can( 'edit_page', $post_id ) ) {
            return $post_id;
        } elseif ( !current_user_can( 'edit_post', $post_id ) ) {
            return $post_id;
        }
    }


    $fields = $_POST['wcpt'];
    update_post_meta($post_id,'wcpt-x-position',sanitize_text_field($fields['x-position']));
    update_post_meta($post_id,'wcpt-y-position',sanitize_text_field($fields['y-position']));
    update_post_meta($post_id,'wcpt-original',sanitize_text_field($fields['original']));
    update_post_meta($post_id,'wcpt-parent-image-width',sanitize_text_field($fields['parent-image-width']));
    update_post_meta($post_id,'wcpt-parent-image-height',sanitize_text_field($fields['parent-image-height']));
    update_post_meta($post_id,'wcpt-tag-width',sanitize_text_field($fields['tag-width']));
    update_post_meta($post_id,'wcpt-tag-height',sanitize_text_field($fields['tag-height']));
    update_post_meta($post_id,'wcpt-tag-type',sanitize_text_field($fields['tag-type']));
    update_post_meta($post_id,'wcpt-product-id',sanitize_text_field($fields['product-id']));
    update_post_meta($post_id,'wcpt-attachment-id',sanitize_text_field($fields['attachment-id']));
}

add_action( 'save_post', 'wcpt_save_fields_meta' );

add_shortcode('wcpt_variation_images', 'wcpt_variation_images');

add_shortcode('designbook_tagtool','wcpt_tagging_html');

add_action('wp_footer','wcpt_tag_panel_output',1);

add_action('wp_footer','wcpt_photo_tag_output',99,1);

function wcpt_photo_tag_output () {
    if (esc_attr( get_option('wcpt-preload',1) ) == 1) {
        global $wcpt_arr_photos;
        $result = wcpt_get_all_designtags(false,$wcpt_arr_photos);
        if (is_array($result)) {
	        if(count($result)) {
		        foreach ($result['tags'] as $key=>$tag) {
			        echo $tag['html'];
		        }
		        echo '<div id="wcpt-featured-products-inner">'.$result['wcpt_featured_products'].'</div>';
	        }
        }
    }
}

add_action('wp_ajax_tag_panel_output', 'wcpt_tag_panel_output', 9);

function wcpt_tag_panel_output() {
    wcpt_tagging_html();
}

function wcpt_tagging_html() {
    $tag_users = false;
    $author_select = "";
    $is_multi_vendor = false;
    $is_admin = current_user_can('administrator');
    $is_vendor = current_user_can('vendor');
    $hidden = '';
    if (($is_vendor) || ($is_admin)) {
        $hidden = 'style="display:none"';
    };
    ?>

 <div id="tag-products-layer">
    <h4>Tagging Tool</h4>
    <!--<div class="heading" > Tagging Tool </div>-->
    <div class="close-modal x-black" >✖</div>
 
   
    <div id="tag_form" style="display: none; position: absolute; z-index: 1100; background-color: white; border: 1px solid #222;txt-align:left;" >
         <i class="fa fa-tag designtag"></i>
         <i class="fa fa-user"></i>
         <div><img id="tag_form_img" style="width:100px;height:auto;"></div>
        <div id="original-product"><input type="checkbox" id="chk_original" value="1" checked><label for="chk_original" style="display: inline-block;width: 80%;">Original Product</label></div>
        <div id="description"><textarea rows="3" placeholder="Description(optional)" id="txt-description"></textarea></div>
        <div><input id="tag_save" type="button" value="save" /><input id="tag_cancel" type="button" value="cancel" /></div>
    </div>

     <?php   if ($tag_users) {      ?>

       <div class="tagtool-section users">Tag Users</div>
        <div class="tagtool-user-list" <?php echo $hidden; ?> >
            <input type="text" name="user-search" id="user-search" placeholder="type to search">
            <div><a class="button tagtool-button" id="user-search-button">Search Users</a></div>
        </div>
        <div class="tagtool-section products">Tag Products</div>
        <?php     }   if (($is_admin) || ($is_vendor)) {   ?>
        <div class="tagtool-product-list">
            <div class="tagtool-filter">
        <?php  $cats = "";
        $cats = isset($_GET['cats'])? esc_attr($_GET['cats']) : '';
        $text = isset($_GET['tag-search'])? esc_attr($_GET['tag-search']) : '';
        wp_dropdown_categories(array('taxonomy' => 'product_cat','hierarchical'=> 1,'value_field'=>'slug','selected'=>$cats,'show_option_all' => 'All categories'));
        ?></div>
          <div class="tagtool-filter"><input id="tag-search" name="tag-search" type="text"></div>
            <div class="tagtool-filter"><input id="tagtool-filter" name="tagtool-filter" type="button" class="button" value="Filter"></div>

        <div id="tagtool-product-results">
            <?php wcpt_tagtool_product_search($cats,$text); ?>
        </div>
        </div>
        <?php
    }
     ?> </div> <?php
}

function wcpt_tagtool_product_search($cats,$text) {
    // -------- add filters to taggable product output
    add_filter( 'paginate_links', 'wcpt_tagtool_paginate_links', 10, 1 );
    add_action( 'woocommerce_before_shop_loop_item', 'wcpt_product_draggable_start', 5 );
    add_action( 'woocommerce_after_shop_loop_item', 'wcpt_product_draggable_end', 5 );
    if ($text) {
        add_filter('woocommerce_shortcode_products_query','wcpt_tagtool_text_search',10,2);
    }

    // Remove "Select options" button from (variable) products on the main WooCommerce shop page.
    add_filter( 'woocommerce_loop_add_to_cart_link', 'wcpt_remove_select_options' );
    remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
    remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
    remove_action( 'woocommerce_after_shop_loop', 'woocommerce_catalog_ordering', 10 );
    remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 10 );
    remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
    add_filter( 'loop_shop_per_page', 'wcpt_tagtool_limit', 19 );

    //change links on tagtool paginationn


    echo do_shortcode('[products columns=1 category="'.$cats.'" paginate="true" limit='.esc_attr(get_option('wcpt-tagtool-results-count')).' orderby="title"]');

    remove_filter( 'paginate_links', 'wcpt_tagtool_paginate_links', 10, 1 );
    add_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
    add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
    remove_filter( 'woocommerce_loop_add_to_cart_link', 'wcpt_remove_select_options' );
    remove_action( 'woocommerce_before_shop_loop_item', 'wcpt_product_draggable_start', 5 );
    remove_action( 'woocommerce_after_shop_loop_item', 'wcpt_product_draggable_end', 5 );
    add_action( 'woocommerce_after_shop_loop', 'woocommerce_catalog_ordering', 10 );
    add_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 10 );
    add_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
    if ($text) {
        remove_filter('woocommerce_shortcode_products_query','wcpt_tagtool_text_search',10,2);
    }
    return;

}

function wcpt_tagtool_limit() {
    return esc_attr(get_option('wcpt-tagtool-results-count',12)) ;
}


add_action('wp_ajax_tagtool_product_search', 'wcpt_tagtool_search_ajax', 9);

function wcpt_tagtool_search_ajax() {
    $cats = sanitize_text_field($_GET['cats']);
    $text = sanitize_text_field($_GET['tag-search']);
	wcpt_tagtool_product_search($cats,$text);
    exit;
}

function wcpt_tagtool_paginate_links( $link ) {
    // make filter magic happen here...
    return $link;
};

function wcpt_tagtool_text_search($args,$atts) {
    $text = sanitize_text_field($_GET['tag-search']);
    $args['s'] = $text;
    return $args;
}
// add the filter


add_action('wp_ajax_nopriv_wcpt_tagging_html', 'wcpt_tagging_html', 9);
add_action('wp_ajax_wcpt_tagging_html', 'wcpt_tagging_html', 9);

function wcpt_product_draggable_start() {

    global $product;

    echo "<div class=\"product-simple draggable\" id=\"".esc_attr($product->get_id())  ."\" data-type=\"product\" data-product_id=\"".esc_attr($product->get_id())  ."\">";

}

function wcpt_product_draggable_end() {
 echo "</div>";
}

function wcpt_remove_select_options( $product ) {

    global $product;

    if ( is_shop() && 'variable' === $product->product_type ) {
        return '';
    } else {
        sprintf( '<a href="%s" data-quantity="%s" class="%s" %s>%s</a>',
            esc_url( $product->add_to_cart_url() ),
            esc_attr( isset( $args['quantity'] ) ? $args['quantity'] : 1 ),
            esc_attr( isset( $args['class'] ) ? $args['class'] : 'button' ),
            isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '',
            esc_html( $product->add_to_cart_text() )
        );
    }

}

add_filter('the_content','wcpt_load_content');

function wcpt_load_content($content) {

    if (!$content) return $content;
    global $wcpt_arr_photos;
	//for Gutenberg - if images have been loaded as block content then add tags this way

	//disable warnings for html5 elements
	libxml_use_internal_errors(true);

	$doc = new DOMDocument();
	$doc->loadHTML($content);
	//get image elements from content
	$images = $doc->getElementsByTagName('img');
	$img_id = 0;
	foreach ($images as $img) {
		//gallery image
		if ($img->hasAttribute('class')) {
			$img->setAttribute('class',$img->getAttribute('class').' droppable');
		} else {
			$img->setAttribute('class','droppable');
		}
		if($img->hasAttribute('data-id')) {
			$img_id = $img->getAttribute('data-id');
		} else {
			//normal images have ids in class names
			if ($img->hasAttribute('class')) {
				preg_match('/wp-image-(\d+)/',$img->getAttribute('class'),$matches);
				if (isset($matches[1])) {
					$img_id = $matches[1];
				}

			}
		}
		$img->setAttribute('data-wcpt_image_id',$img_id);
		$img->setAttribute('class',$img->getAttribute('class').' wcpt_img_'.$img_id);
		//$img->setAttribute('id',"img_".$img_id);
		$tag_count = get_post_meta($img_id,'wcpt-tag-count',true);
		if ($tag_count > 0) {
			$wcpt_arr_photos[]   = $img_id;
			$img->setAttribute('class',$img->getAttribute('class').' tag_autoload ');

		}
	}
	return $doc->saveHTML();

}

add_filter( 'wp_get_attachment_image_attributes', 'wcpt_change_attachment_image_markup',10,3 );

function wcpt_change_attachment_image_markup($attributes,$attachment,$size) {
    global $wcpt_arr_photos;

    $image_sizes = get_option('wcpt-image-sizes');

    //image size option is not selected
    if (!is_array($size)) {
        if (!isset($image_sizes[$size])) {
            return $attributes;
        } elseif (($size && $image_sizes[$size] <> 1)) {
            return $attributes;
        };
    }


    $tag_count = get_post_meta($attachment->ID,'wcpt-tag-count',true);
    if ($tag_count > 0) {
        $wcpt_arr_photos[]   = $attachment->ID;
        $attributes['class'] = $attributes['class'].' tag_autoload';
    }
    $attributes['data-wcpt_image_id'] = $attachment->ID;
    //$attributes['id'] = 'img_'.$attachment->ID;
    $attributes['class'] = $attributes['class'].' wcpt_img_'.$attachment->ID.' droppable';
    return $attributes;
}

//add_filter( 'post_thumbnail_html', 'wcpt_image_html', 10, 3 );

 function wcpt_image_html($html) {
     return '<div class="wcpt-image">'.$html.'</div>';
 }

function wcpt_custom_img($html, $id, $caption, $title, $align, $url, $size, $alt) {
    /*
    $html - default HTML, you can use regular expressions to operate with it
    $id - attachment ID
    $caption - image Caption
    $title - image Title
    $align - image Alignment
    $url - link to media file or to the attachment page (depends on what you selected in media uploader)
    $size - image size (Thumbnail, Medium, Large etc)
    $alt - image Alt Text
    */

    preg_match('/class=\"([^\"]+)\"/',$html,$matches);
    $class = $matches[1];
    $class .= " droppable tag_autoload";
    /*
     * First of all lets operate with image sizes
     */
    list( $img_src, $width, $height ) = image_downsize($id, $size);
    $hwstring = image_hwstring($width, $height);

    /*
      * Second thing - get the image URL $image_thumb[0]
     */
    $image_thumb = wp_get_attachment_image_src( $id, $size );

    $out = '<div class="wcpt-image">'; // I want to wrap image into this div element
    if($url){ // if user wants to print the link with image
        $out .= '<a href="' .esc_url($url)  . '" class="fancybox">';
    }
    $out .= '<img class="'.esc_attr($class) .'" id="img_'.esc_attr($id).'" data-image_id="'.esc_attr($id).'" src="'. esc_url($image_thumb[0])  .'" alt="'.esc_attr($alt).'" '.esc_attr($hwstring).'/>';
    if($url){
        $out .= '</a>';
    }
    $out .= '</div>';
    return $out; // the result HTML
}

add_filter('image_send_to_editor', 'wcpt_custom_img', 1, 8);


add_action('wp_ajax_nopriv_wcpt_designtag_fill', 'wcpt_designtag_fill', 9);
add_action('wp_ajax_wcpt_designtag_fill', 'wcpt_designtag_fill', 9);

function wcpt_designtag_fill() {
    $designtag_id = sanitize_text_field($_POST['id']);
    $data = wcpt_get_designtag($designtag_id);
    wp_send_json($data);
}

add_action('wp_ajax_nopriv_wcpt_delete_designtag', 'wcpt_delete_designtag', 9);
add_action('wp_ajax_wcpt_delete_designtag', 'wcpt_delete_designtag', 9);

function wcpt_delete_designtag() {

    $tag_id = sanitize_text_field($_POST['tag_id']);
    $tag_post = get_post($tag_id);
    $tag_author = $tag_post->post_author;
    $photo_id = get_post_meta($tag_id,'wcpt-attachment-id',true);
    //check to see if user is authorised to delete
    if (current_user_can('administrator') || ($tag_author == get_current_user_id())) {
        $success = wp_delete_post($tag_id);

        if ($success) {

            $response = array('success' => true, 'message' => 'The tag was deleted successfully.', 'tag_id' => $tag_id);
            $photo_tag_count = wcpt_tag_count($photo_id) ;
            update_post_meta($photo_id,'wcpt-tag-count',$photo_tag_count);
        } else {
            $response = array('success' => false, 'message' => 'Error - tag not deleted.', 'tag_id' => $tag_id);
        }
    } else {
        $response = array('success' => false, 'message' => 'only the author of this tag or an administrator can delete this tag','tag_id' => $tag_id);
    }
    echo wp_send_json($response);
}

add_action('wp_trash_post','wcpt_delete_tags',10,1);
add_action('delete_attachment','wcpt_delete_tags',10,1);

function wcpt_delete_tags($post_id) {
    $post_type = get_post_type($post_id);
    if (in_array($post_type,array('product','attachment')) ) {
        $meta_key = ($post_type == 'product') ? 'wcpt-product-id' : 'wcpt-attachment-id';
        $args = array(
                'post_type' => 'wc-photo-tag',
            'meta_query' => array(
                array(
                    'key' => $meta_key,
                    'value' => $post_id,
                    'compare' => '=',
                )
            )
        );

        $tags = get_posts($args);
        foreach($tags as $tag) {
            wp_trash_post($tag->ID);
        }
    }
}


//ajax call to fetch designtags for an image
add_action('wp_ajax_nopriv_wcpt_get_designtags', 'wcpt_get_designtags', 9);
add_action('wp_ajax_wcpt_get_designtags', 'wcpt_get_designtags', 9);



//ajax call to fetch designtags for all images on a page
add_action('wp_ajax_nopriv_wcpt_get_all_designtags', 'wcpt_get_all_designtags', 9);
add_action('wp_ajax_wcpt_get_all_designtags', 'wcpt_get_all_designtags', 9);

function wcpt_get_all_designtags($json = true,$photo_ids = null) {
    global $is_wcpt_featured_products,$wcpt_featured_products;
    $islightbox = false;

    if($photo_ids) {
        if(is_array($photo_ids)) {
            $photo_ids = implode(",",$photo_ids);
        }
    } else if (isset($_POST['photo_ids'])) {
        $photo_ids = sanitize_text_field($_POST['photo_ids']);
        $json = true;
        $islightbox = sanitize_text_field($_POST['islightbox']);
    } else {
        return;
    }



    $args = array(
        'post_type' => 'wc-photo-tag',
        'meta_key'   => 'wcpt-attachment-id',
        'meta_value' => explode(',',$photo_ids),
        'meta_compare' => 'IN',
        'posts_per_page'=> -1
    );
    if (!empty($photo_ids)) {
        $tag_query = new WP_Query($args);
        if ( $tag_query->have_posts() ) {
            $photo_tags['no_results'] = false;
            $tag_count = 0;
            while ( $tag_query->have_posts() ) {
                $tag_query->the_post();
                $photo_id = get_post_meta(get_the_ID(),'wcpt-attachment-id');
                //$img_tags['photo_id'] = $photo_id;
                $photo_tags['tags'][get_the_ID()] = wcpt_get_designtag($tag_query->post,$islightbox);
                $tag_count ++;
            }
            $photo_tags['tag_count'] = $tag_count;
            if ($is_wcpt_featured_products && !$json) {
                $photo_tags['wcpt_featured_products'] = do_shortcode('[products ids="'.esc_attr(implode(',',$wcpt_featured_products)).'"]');
            }
            /* Restore original Post Data */

        } else {
             $photo_tags['no_results'] = true;
            $photo_tags['error_message'] = 'No tags on this page';
        }
        wp_reset_postdata();
    } else {
        $photo_tags['no_results'] = true;
        $photo_tags['photo_ids_missing'] = true;
        $photo_tags['error_message'] = 'The photo IDs are missing.';
    }

    if ($json) {
        echo wp_json_encode($photo_tags);
        die;
    } else {
        return $photo_tags;
    }

}


if (!function_exists('wcpt_get_designtag')) {
    function wcpt_get_designtag($tag,$islightbox = false) {
        //can pass designtag ID or Designtag object to this function
        global $is_wcpt_featured_products,$wcpt_featured_products,$wcpt_product;
        if (gettype($tag) == 'integer') {
            $id = $tag;
            $post = get_post($id);
        } else {
            $post = $tag;
            $id = $post->ID;
        }
        $tag_author = $post->post_author;
        if (current_user_can('administrator') || ($tag_author == get_current_user_id())) {
            $delete_button = '<a href="#" class="delete-tag" data-tag_id="'.esc_attr($id).'"><i class="fa fa-times"></i> delete</a>';
        }
        $z = "";
        if ($islightbox == "true") {
            $z = "_z";
        }
        $postmeta = get_post_meta($id);

        $data['tag_original'] = esc_attr($postmeta['wcpt-original'][0]);
        $tag_size = ($data['tag_original'])? esc_attr( get_option('wcpt-product-orig-size',20) ):esc_attr( get_option('wcpt-product-look-size',20) );
        $data['tag_id']= $id.$z;
        $data['tag_position'] = array('tag_x' => esc_attr($postmeta['wcpt-x-position'][0]),'tag_y' => esc_attr($postmeta['wcpt-y-position'][0]),
            'ratio_x' => esc_attr($postmeta['wcpt-x-position'][0])/esc_attr($postmeta['wcpt-parent-image-width'][0]), 'ratio_y' => esc_attr($postmeta['wcpt-y-position'][0]/$postmeta['wcpt-parent-image-height'][0]));
        $data['tag_size'] = array('tag_width' => $tag_size,'tag_height' => $tag_size);
        $data['tag_type'] = esc_attr($postmeta['wcpt-tag-type'][0]);

        //$data['tag_description'] = $post->post_content;


        if ($data['tag_type'] == 'product') {

            $orig_icon = esc_attr( get_option('wcpt-product-orig-icon','fa fa-plus-circle') );
            $look_icon = esc_attr( get_option('wcpt-product-look-icon','fa fa-plus-circle') );
            $show_box = esc_attr( get_option('wcpt-product-show-box',1) );
            $tag_class = ($data['tag_original']) ? $orig_icon: $look_icon.' suggestion ';
            $tag_class .= ($data['tag_original']) ? esc_attr( get_option('wcpt-product-orig-animate')) : esc_attr( get_option('wcpt-product-look-animate'));
            $box_class = ($show_box) ? " wcpt-product-box" : "";
            $tag_colour = ($data['tag_original']) ? esc_attr( get_option('wcpt-product-orig-colour','#ffffff')) : esc_attr( get_option('wcpt-product-look-colour','lightgrey'));
            $label = ($data['tag_original'])? esc_attr( get_option('wcpt-product-orig-label','Original Product')) : esc_attr( get_option('wcpt-product-look-label','Suggested Product'));
            $label_colour = ($data['tag_original'])? esc_attr( get_option('wcpt-product-orig-label-colour','#000000')) : esc_attr( get_option('wcpt-product-look-label-colour','#000000'));
            $product = wc_get_product($postmeta['wcpt-product-id'][0]);
            $wcpt_product = $product;
            $data['title'] = esc_attr($product->get_title());
            if (($product) && ($is_wcpt_featured_products) ) {
                $wcpt_featured_products[] =  esc_attr($product->get_id());
            }

            $data['tagged_image_id'] = esc_attr($postmeta['wcpt-attachment-id'][0]);
            $data['photo_id'] = esc_attr($postmeta['wcpt-attachment-id'][0]);

            $data_vars = "";
            foreach ($data as $key=>$val) {
                if (is_array($val)) {
                    foreach ($val as $key_inner => $val_inner) {
                        $data_vars .= ' data-'.esc_attr($key_inner).'="'.esc_attr($val_inner).'"';
                    }
                } else {
                    $data_vars .= ' data-'.esc_attr($key).'="'.esc_attr($val).'"';
                }

            }

            if (get_option('wcpt-product-default',1) == 1) {
                $product_html =  do_shortcode('[product id='.$product->get_id().' columns="1"]') ;
            } else {
                ob_start();
                wcpt_get_my_template('wcpt-product.php');
                $product_html = ob_get_clean() ;
            }





            $html = '<li id="tag_'.$id.$z.'" class="designtag '.$box_class.'" '.$data_vars.'  style="position: absolute; background-repeat:no-repeat;z-index: 299; padding: 0px; margin: 0px;">
                        <i class="designtag '.$tag_class.'" style="color:'.$tag_colour.';font-size:'.$tag_size.'px;"></i>
                        <div class="ft_msg" style="border-radius:'.esc_attr( get_option('wcpt-border-radius','0')).'px">
                        <div class="designtag" style="color:'.$label_colour.';background-color:'.$tag_colour.';border-radius:'.esc_attr( get_option('wcpt-border-radius','0')).'px">'.$label.'</div>'
                        .$product_html.$delete_button.'
                        </div>
                    </li>';
            $data['html'] = $html;
        }


        return $data;
    }
}



add_action('wp_ajax_nopriv_wcpt_designtag_save', 'wcpt_designtag_save', 9);
add_action('wp_ajax_wcpt_designtag_save', 'wcpt_designtag_save', 9);

function wcpt_designtag_save() {
    $photo_id = sanitize_text_field($_POST['img_id']) ;
    $img_width = sanitize_text_field($_POST['img_width']);
    $img_height = sanitize_text_field($_POST['img_height']);
    $tag_x = sanitize_text_field($_POST['tag_x']);
    $tag_y = sanitize_text_field($_POST['tag_y']);
    $tag_width = sanitize_text_field($_POST['tag_width']);
    $tag_height = sanitize_text_field($_POST['tag_height']);
    $tag_original = sanitize_text_field($_POST['tag_original']);
    $tag_id = sanitize_text_field($_POST['tag_id']);
    $obj_type = sanitize_text_field($_POST['obj_type']);
    $obj_id = sanitize_text_field($_POST['obj_id']);
    $obj_var = sanitize_text_field($_POST['obj_var']);
    $tag_description = sanitize_textarea_field($_POST['tag_description']) ;

    $key = ($obj_type == 'user') ? 'wcpt-tagged-user-id' : 'wcpt-product-id';

    if (current_user_can('vendor') || current_user_can('administrator')) {

        // new tag
        if (!$tag_id) {

            //need to enforce only 1 tag for a product per image
            $dupl_args = array(
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'wcpt-attachment-id',
                        'value' => $photo_id,
                        'compare' => '='
                    ),

                    array(
                        'key' => $key,
                        'value' => $obj_id,
                        'compare' => '='
                    )
                )
            );

            $post_args = array('post_type' => 'wc-photo-tag',
                'post_title' => 'product-'.$obj_id.'-'.$photo_id,
                'post_status' => 'publish',
                'post_author' => get_current_user_id(),
                'post_content' => $tag_description
            );

            $tag_id = wp_insert_post($post_args);
            $action = 'insert';
        } else {
            $action = 'update';
        }

        update_post_meta($tag_id,'wcpt-attachment-id',$photo_id);
        update_post_meta($tag_id,$key,$obj_id);
        update_post_meta($tag_id,'wcpt-x-position',$tag_x);
        update_post_meta($tag_id,'wcpt-y-position',$tag_y);
        update_post_meta($tag_id,'wcpt-parent-image-width',$img_width);
        update_post_meta($tag_id,'wcpt-parent-image-height',$img_height);
        update_post_meta($tag_id,'wcpt-tag-width',$tag_width);
        update_post_meta($tag_id,'wcpt-tag-height',$tag_height);
        update_post_meta($tag_id,'wcpt-tag-type',$obj_type);
        update_post_meta($tag_id,'wcpt-original',$tag_original);
        if ($obj_var) {
            update_post_meta($tag_id,'wcpt-variation-id',$obj_var);
        }
        $photo_tag_count = wcpt_tag_count($photo_id);
        //must reinstate this
        update_post_meta($photo_id,'wcpt-tag-count',$photo_tag_count);
        $data = wcpt_get_designtag($tag_id);
        $result = array('successful'=> true, 'tag_id'=>$tag_id, 'action'=> $action, 'data' => $data);

    } else {
        $result = array('successful' => false, 'error' => 'You do not have the right permissions to save Designtags');
    }
    wp_send_json($result);
}

if (!function_exists('make_excerpt')) {
    function make_excerpt($str, $length=115, $trailing='...', $rough = false){
        $str = strip_shortcodes($str);
        $str = strip_tags($str);
        $length-=mb_strlen($trailing);
        if (mb_strlen($str)> $length){
            $rough_excerpt = mb_substr($str,0,$length);
            if ( $rough )
                return $rough_excerpt.$trailing;
            $excerpt = mb_substr($rough_excerpt, 0, strrpos($rough_excerpt, " ")).$trailing;
            return $excerpt;
        }else{
            $res = $str;
        }
        return $res;
    }
}


function wcpt_tag_count($photo_id) {
    $tag_args = array(
        'post_type' => 'wc-photo-tag',
        'meta_query' => array(array('key' => 'wcpt-attachment-id', 'value' => $photo_id))
    );
    $tag_posts = get_posts($tag_args);

    $tagcount = count( $tag_posts );
    return $tagcount;
}

add_shortcode('wcpt_product_tabs','wcpt_product_tabs');

function wcpt_product_tabs() {

    return wcpt_render_product_tabs();
}

function wcpt_render_product_tabs() {
$tabs = apply_filters( 'woocommerce_product_tabs', array() );

    if ( ! empty( $tabs ) ) {

        $html = '<div class="woocommerce-tabs wc-tabs-wrapper">
            <ul class="tabs wc-tabs" role="tablist">';

        foreach ( $tabs as $key => $tab ) {

            $html .= '<li class="'.esc_attr( $key ).'_tab" id="tab-title-'.esc_attr( $key ).'" role="tab" aria-controls="tab-'.esc_attr( $key ).'">
                        <a href="#tab-'.esc_attr( $key ).'">'.apply_filters( 'woocommerce_product_' . $key . '_tab_title', esc_html( $tab['title'] ), $key ).'</a></li>';

        }

        $html .='</ul>';

        foreach ( $tabs as $key => $tab )  {

            $html .='<div class="woocommerce-Tabs-panel woocommerce-Tabs-panel--'.esc_attr( $key ).' panel entry-content wc-tab" id="tab-'.esc_attr( $key ).'" role="tabpanel" aria-labelledby="tab-title-'.esc_attr( $key ).'">
                    </div>';
        }
        $html .=' </div>';
        return $html;
    }
}

add_shortcode('wcpt_add_to_cart','wcpt_add_to_cart');

function wcpt_add_to_cart($atts) {
    $id = $atts['id'];
    ob_start();
    echo do_shortcode('[add_to_cart id="'.$id.'"]');
    $myStr = esc_html(ob_get_contents()) ;
    ob_end_clean();
    return $myStr;

}

function wcpt_posts($atts) {
    global $wpdb;
    $output = "";
    $columns = isset($atts['columns']) ? $atts['columns']: 3;
    $is_bootstrap = get_option('wcpt-bootstrap');
    $wcpt_image_size = (isset($atts['image_size'])) ? $atts['image_size'] : 'large';
    //$thumbnail_size = (preg_match('/\d+,\d+/',$thumbnail_size)) ? explode(',',$thumbnail_size) : $thumbnail_size;


    if (is_product()) {
        global $product;
        $product_id = $product->get_id();
    }

    $args = array(
        'post_type' => 'wc-photo-tag',
        'meta_key'   => 'wcpt-product-id',
        'meta_value' => $product_id,
        'posts_per_page'=> -1
    );

    $arr_attachments = array();
    $tag_query = new WP_Query($args);
    if ( $tag_query->have_posts() ) {
        while ( $tag_query->have_posts() ) {
            $tag_query->the_post();
            $arr_attachments[] = get_post_meta(get_the_ID(),'wcpt-attachment-id',true) ;
        }

        $meta_query_args = array(
            'relation' => 'AND',
            array(
            'key'     => '_thumbnail_id',
            'value'   => $arr_attachments,
            'compare' => 'IN'
        ));
        $meta_query = new WP_Meta_Query($meta_query_args);
        //'meta_query'=>array($meta_query)

        $post_query_args = array('post_type' => 'post', 'post_status' => 'publish','meta_key' => '_thumbnail_id', 'meta_value' => implode(',',$arr_attachments),'meta_compare' => 'IN');
        $wcpt_post_query = new WP_Query($post_query_args);
        if ($wcpt_post_query->have_posts()) {
            $output .=  '<div '.wcpt_container("wcpt-container",$is_bootstrap).'>
            <div '.wcpt_row("wcpt-row",$is_bootstrap).'>';

            if ($columns > $wcpt_post_query->found_posts) {
                $columns = $wcpt_post_query->found_posts;
                $wcpt_image_size = 'large';
            }

            while ($wcpt_post_query->have_posts()) {
                $wcpt_post_query->the_post();
                $image = wp_get_attachment_image(get_post_thumbnail_id(get_the_ID()),$wcpt_image_size);
                if (strlen($image) > 0) {
                    $output .= '<div '.wcpt_column("wcpt-column",$columns,$is_bootstrap).'>
                    <article class="wcpt-article"><div class="wcpt-post-image"><a href="'.get_permalink(get_the_ID()).'" title="'.esc_attr(the_title('','',false)).'">'.$image.'</a></div><div class="wcpt-post-title"><a href="'.get_permalink(get_the_ID()).'" title="'.esc_attr(the_title('','',false)).'">'.the_title('<h4>','</h4>',false).'</a></div></article>
                    </div>';
                }
            }

            $output .= '</div></div>';
        }
        wp_reset_postdata();
    }
    return $output;
}

add_shortcode('wcpt_posts','wcpt_posts');

function wcpt_column($name = '',$columns = 3,$is_bootstrap = false) {
    $column_bootstrap = array(
            1 => ' col-xs-12',
            2 => ' col-sm-12 col-md-6',
            3 => ' col-sm-12 col-md-4',
            4 => ' col-xs-12 col-xs-6 col-md-3'
    );
    $classes = $name.'-'.$columns;
    if ($is_bootstrap) {
        $classes.= $column_bootstrap[$columns];
    }
    if (strlen($classes) > 0) {
        return 'class="'.$classes.'"';
    }
}

function wcpt_row($name = '',$is_bootstrap = false) {
    $classes = $name;
    if ($is_bootstrap) {
        $classes.= ' row';
    }
    if (strlen($classes) > 0) {
        return 'class="'.$classes.'"';
    }
}

function wcpt_container($name = '',$is_bootstrap = false) {

    $classes = $name;
    /*if ($is_bootstrap) {
        $classes.= ' container';
    }*/
    if (strlen($classes) > 0) {
        return 'class="'.$classes.'"';
    }
}

/**
 * Add a custom product data tab
 */
add_filter( 'woocommerce_product_tabs', 'wcpt_product_tab' );
function wcpt_product_tab( $tabs ) {

    // Adds the new tab
    if (get_option('wcpt-product-tab')==1) {
        $heading = esc_attr( get_option('wcpt-product-tab-heading','Photo Tags'));
        $tabs['wcpt'] = array(
            'title' 	=> __( $heading, 'woocommerce' ),
            'priority' 	=> 50,
            'callback' 	=> 'wcpt_product_tab_content'
        );
    }


    return $tabs;

}
function wcpt_product_tab_content() {

    // The new tab content

    echo wcpt_posts(array('columns'=>3,'image_size'=>'medium'));

}

add_shortcode('wcpt_products','wcpt_products');

function wcpt_products($atts) {
    global $is_wcpt_featured_products;
    $is_wcpt_featured_products = true;
    return '<div id="wcpt-featured-products"></div>';
}

function wcpt_locate_my_template( $template_name, $template_path = '', $default_path = '' ) {
    // Set variable to search in woocommerce-photo-tag-templates folder of theme.
    if ( ! $template_path ) :
        $template_path = 'woocommerce-photo-tag-templates/';
    endif;
    // Set default plugin templates path.
    if ( ! $default_path ) :
        $default_path = plugin_dir_path( __FILE__ ) . 'templates/'; // Path to the template folder
    endif;
    // Search template file in theme folder.
    $template = locate_template( array(
        $template_path . $template_name,
        $template_name
    ) );
    // Get plugins template file.
    if ( ! $template ) :
        $template = $default_path . $template_name;
    endif;
    return apply_filters( 'wcpt_locate_my_template', $template, $template_name, $template_path, $default_path );
}

function wcpt_get_my_template( $template_name, $args = array(), $tempate_path = '', $default_path = '' ) {
    if ( is_array( $args ) && isset( $args ) ) :
        extract( $args );
    endif;
    $template_file = wcpt_locate_my_template( $template_name, $tempate_path, $default_path );
    if ( ! file_exists( $template_file ) ) :
        _doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $template_file ), '1.0.0' );
        return;
    endif;
    include $template_file;
}