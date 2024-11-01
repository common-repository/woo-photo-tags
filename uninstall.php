<?php
/**
 * Created by PhpStorm.
 * User: paul
 * Date: 2018/10/20
 * Time: 1:39 PM
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

$options = array('wcpt-singular','wcpt-archive','wcpt-border-radius','wcpt-product-orig-icon','wcpt-product-orig-size','wcpt-product-orig-colour','wcpt-product-orig-label','wcpt-product-look-icon','wcpt-product-look-size','wcpt-product-look-colour','wcpt-product-look-label','wcpt-product-orig-animate','wcpt-product-look-animate','wcpt-tagtool-results-count','wcpt-ga','wcpt-preload','wcpt-product-show-box','wcpt-image-sizes','wcpt-bootstrap','wcpt-product-tab','wcpt-product-tab-heading');

//call delete option and use the vairable inside the quotations
foreach ($options as $option_name) {
    delete_option($option_name);
}
