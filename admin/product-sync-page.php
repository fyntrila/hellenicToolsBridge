<?php
/**
 * Displays the product sync page for the Softone WooCommerce Integration.
 */
function softone_products_page() {
    if (isset($_POST['sync_products'])) {
        $result = softone_sync_products();
        if (is_array($result) && isset($result['success']) && $result['success']) {
            echo '<div class="notice notice-success"><p>' . esc_html($result['message']) . '</p></div>';
			$products=$result['products'];
        } else {
            echo '<div class="notice notice-error"><p>Failed to synchronize products.</p></div>';
        }
    }
	if (isset($_POST['sync_product_descs_specs'])) {
        $result = softone_sync_product_descs_specs();
        if (is_array($result) && isset($result['success']) && $result['success']) {
            echo '<div class="notice notice-success"><p>' . esc_html($result['message']) . '</p></div>';
			$products=$result['products'];
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
		
		<form method="post">
			<input type="hidden" name="sync_product_descs_specs" value="1" />
            <?php submit_button('Sync Product Descriptions & Specs'); ?>
        </form>
		
        <?php if (isset($result) && is_array($result) && isset($result['products']) && isset($_POST['sync_products'])): ?>
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
				// echo "<pre>";
				// print_r($result);
				// echo "</pre>";
					// $aa=0;
					
					foreach ($products as $product): 
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
		
		
		
		<?php if (isset($result) && is_array($result) && isset($products) && isset($_POST['sync_product_descs_specs'])): 
		echo "Skipped:".$products['skipped'].", Updated:".$products['updated'].", Invalid:".$products['invalid']; ?>
        <h2>Synchronized product_descs_specs</h2>
		 
        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th>Counter</th>
                    <th>SKU</th>
                    <th>ItemCode</th>
                    <th>Soft1Code(mtrl)</th>
                    <th>Description</th>
                    <th>TechSpecs</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
				// echo "<pre>";
				// print_r($result);
				// echo "</pre>";
					// $aa=0;
					
					foreach ($products as $sku=>$product): 
					if(in_array($sku,['skipped','updated','invalid'])) continue; 
						$aa++;
				?>
					
					<tr>
						<td><?php echo esc_html($aa); ?></td>
						<td><?php echo esc_html($sku); ?></td>
						<td><?php echo esc_html($product['itemcode']); ?></td>
						<td><?php echo esc_html($product['mtrl']); ?></td>
						<td><?php echo esc_html($product['descr']); ?></td>
						<td><?php echo esc_html($product['techSpecs']); ?></td>
						<td><?php echo esc_html($product['updated']); ?></td>
					</tr>
                <?php endforeach; ?>
            </tbody>
        </table>
         
        <?php endif; ?>
    </div>
    <?php
}
?>