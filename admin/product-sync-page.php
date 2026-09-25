<?php
/**
 * Displays the product sync page for the Softone WooCommerce Integration.
 */
function softone_products_page() {
    if (isset($_POST['sync_products'])) {
        $result = softone_sync_products();
        if (is_array($result) && isset($result['success']) && $result['success']) {
            echo '<div class="notice notice-success"><p>' . esc_html($result['message']) . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Failed to synchronize products.</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <h1>Product Sync</h1>
        <form method="post">
            <input type="hidden" name="sync_products" value="1" />
            <?php submit_button('Sync Products'); ?>
        </form>
        <?php if (isset($result) && is_array($result) && isset($result['products'])): ?>
        <h2>Synchronized Products</h2>
        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th>Counter</th>
                    <th>SKU</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Category</th>
                    <th>Group</th>
                    <th>Manufacturers</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
					$aa=0;
					foreach ($result['products'] as $product): 
					$aa++;
				?>
					
                <tr>
                    <td><?php echo esc_html($aa); ?></td>
                    <td><?php echo esc_html($product['item_code']); ?></td>
                    <td><?php echo esc_html($product['item_descr']); ?></td>
                    <td><?php echo esc_html($product['price_WholeSale']); ?></td>
                    <td><?php echo esc_html($product['item_category']); ?></td>
                    <td><?php echo esc_html($product['item_group']); ?></td>
                    <td><?php echo esc_html($product['Manufacturers']); ?></td>
                    <td><?php echo esc_html($product['action']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php
}
?>