<?php
/**
 * Displays the customer sync page for the Softone WooCommerce Integration.
 */
function softone_product_test_page() {
    if (isset($_POST['test_sku_item_code'])) {
        $result = test_sku_item_code();
        if (is_array($result) && isset($result['success']) && $result['success']) {
            echo '<div class="notice notice-success"><p>' . esc_html($result['message']) . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Failed to synchronize customers.</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <h1>test_sku_item_code</h1>
        <form method="post">
            <input type="hidden" name="test_sku_item_code" value="1" />
            <?php submit_button('Test skus'); ?>
        </form>
        <?php if (isset($result) && is_array($result) && isset($result['skus'])): ?>
        <h2>Testing if item.code reply from Soft1 with Woocommerce sku !!!</h2>
        
		<?php 
		echo "<pre>";
		print_r($result);
		// print_r($result['products']);
			echo 'ok';	
        endif; ?>
    </div>
    <?php
}
?>
