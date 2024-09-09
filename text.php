<?php
/*
Plugin Name: WooCommerce Display Variable Products and Variations
Description: Displays a list of all variable products with their variations' names, regular prices, and sale prices in the specified format.
Version: 1.3
Author: Your Name
*/

// Function to retrieve variable products and variations data
function wc_display_products() {
    // Get all variable products
    $args = array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'product_type',
                'field'    => 'slug',
                'terms'    => 'variable',
            ),
        ),
    );

    $variable_products = new WP_Query($args);
    $product_data = array();

    if ($variable_products->have_posts()) {
        while ($variable_products->have_posts()) {
            $variable_products->the_post();
            $product = wc_get_product(get_the_ID());

            if ($product && $product->is_type('variable')) {
                $product_info = array(
                    'product_name' => $product->get_name(), // Full product name
                    'variations' => array()
                );

                $variations = $product->get_children();

                foreach ($variations as $variation_id) {
                    $variation = wc_get_product($variation_id);
                    $regular_price = get_post_meta($variation_id, '_regular_price', true);
                    $sale_price = get_post_meta($variation_id, '_sale_price', true);

                    // Store variation data in the desired format
                    $product_info['variations'][] = array(
                        'variation_name' => $variation->get_name(),
                        'regular_price' => $regular_price ? $regular_price : 'N/A',
                        'sale_price' => $sale_price ? $sale_price : 'N/A'
                    );
                }

                $product_data[] = $product_info;
            }
        }
        wp_reset_postdata();
    }

    wp_send_json_success($product_data);
}

// AJAX action to handle product data retrieval
add_action('wp_ajax_wc_display_products', 'wc_display_products');
add_action('wp_ajax_nopriv_wc_display_products', 'wc_display_products');

// Shortcode to display the button and product data
function wc_display_product_button_shortcode() {
    ob_start(); ?>
    <button id="wc-display-product-button" class="button">Display Products</button>
    <div id="wc-product-list"></div>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#wc-display-product-button').on('click', function() {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'wc_display_products',
                    },
                    success: function(response) {
                        if (response.success) {
                            // Clear and reload the product list with the updated data
                            var productList = $('#wc-product-list');
                            productList.empty(); 

                            $.each(response.data, function(index, product) {
                                var productHTML = '<div class="product-wrapper">';
                                productHTML += `<h3> ${index + 1}. <strong>Product Name:</strong>  ${product.product_name}  </h3>`;
                                productHTML += '<ul>';
                                productHTML += '<h3>Product Varitions:</h3>';
                                $.each(product.variations, function(i, variation) {
                                  
                                    productHTML += '<li>';
                                    productHTML += `${variation.variation_name} - <span class="yellow"> Purchase Price:  ${(Math.floor(variation.sale_price / 3) - 20)} </span> ,<span class="green"> Sale Price: ${variation.sale_price}</span>`;

                                    productHTML += '</li>';
                                });
                                productHTML += '</ul>';
                                productHTML += '</div>';
                                productList.append(productHTML);
                            });

                            alert('Product list updated');
                        } else {
                            alert('Failed to retrieve products.');
                        }
                    }
                });
            });
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('wc_display_product_button', 'wc_display_product_button_shortcode');
