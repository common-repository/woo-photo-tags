<?php
/**
 * Woocommerce Photo Tag Popup Template
 *
 * This template can be overriden by copying this file to your-theme/woocommerce-photo-tag-templates/wcpt-product.php
 *
 * @author 		Paul Tem-Bokum
 * @package 	WooCommerce Photo Tags/Templates
 * @version     1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Don't allow direct access

global $wcpt_product;

?><div class='wcpt-product'>

        <div class="wcpt-product-image">
            <a href="<?php echo $wcpt_product->get_permalink() ?>">
                <?php echo $wcpt_product->get_image() ?>
            </a>
    </div>
    <div class="wcpt-product-title">
        <h4><a href="<?php echo $wcpt_product->get_permalink() ?>"><?php echo $wcpt_product->get_title() ?></a></h4>
    </div>

    <div class="wcpt-product-price price">
        <span class="woocommerce-Price-amount amount"><?php echo $wcpt_product->get_price_html() ?></span>
    </div>

</div>