<?php
/**
 * Displays the product sync page for the Softone WooCommerce Integration.
 */
function softone_sync_products_page() {
	 if (!isset($_POST['sync_products'])) {
        //$result = woo2soft1_sync_products();
       //if (is_array($result) && isset($result['success']) && $result['success']) {
            // echo '<div class="notice notice-success"><p>' . esc_html($result) . '</p></div>';
        // } else {
            echo '<div class="notice notice-error"><p>Press button synchronize products.</p></div>';
        }
    //}
	$start_time = microtime(true);
	$args = array(
		'limit' => -1,
		'status' => 'publish',
		'return' => 'ids',
	);
	$product_ids = wc_get_products( $args );
	$api = new Softone_API();
    ?>
   

   <div class="wrap">
        <h1>Product Sync</h1>
		<form method="post">
            <input type="hidden" name="sync_products" value="1" />
            <?php submit_button('Sync Products'); ?>
        </form>
		
        <h2>Synchronized Products</h2>
        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Category</th>
                    <th>SubCategory</th>
                    <th>Barcode</th>
                    <th>Stock</th>
                </tr>
            </thead>
            <tbody>
			<?php if (isset($_POST['sync_products'])): ?>
			<?php
				$i=0;
				$totals=[
						'update'=>0,
						'insert'=>0
						];
				foreach($product_ids as $product_id){ 
					$soft1ItemKey="";
					$item = wc_get_product( $product_id );
					
					if($item->is_type( 'simple' )){
						$i++;
						
						$product=$item;
						$pTime = microtime(true);
						$soft1ItemKey=$api->findProductKey($product);
						if($soft1ItemKey){
							$totals['update']=$totals['update']+1;
						}
						else{
							$totals['insert']=$totals['insert']+1;
						}
						$end_pTime = microtime(true);
						?>
						 <tr>
							<td><?php echo $product_id; ?></td>
							<td><?php echo $product->get_sku(); ?></td>
							<td><?php echo $product->get_name(); ?></td>
							<td><?php echo $product->get_regular_price(); ?></td>
							<td><?php echo "simple";//echo esc_html($product['COMMECATEGORY_NAME']); ?></td>
							<td><?php echo ($soft1ItemKey)?"update":"insert";//echo esc_html($product['SUBMECATEGORY_NAME']); ?></td>
							<td><?php echo ($end_pTime - $pTime); ?></td>
							<td><?php echo $i;//esc_html($product['Stock QTY']); ?></td>
						</tr><?php	
					}
					
					
					if($i>19){
						usleep(200);
					}
					if($i>100){
						// // End Clock Time in Seconds
						$end_time = microtime(true);

						// // Calculate the Script Execution Time
						$execution_time = ($end_time - $start_time);
						
						break;
					}
				} 
				$result= "Totals: Update.".$totals['update']." Insert.".$totals['insert']." in ".$execution_time." seconds";
				echo '<div class="notice notice-success"><p>' . esc_html($result) . '</p></div>';?>
				 <?php endif; ?>
            </tbody>
        </table>   
		<?php 
			// if($i==100){
				// End Clock Time in Seconds
				$end_time = microtime(true);

				// Calculate the Script Execution Time
				$execution_time = ($end_time - $start_time);
				if(isset($totals))
					echo "Totals: Update.".$totals['update']." Insert.".$totals['insert']."in ".$execution_time." seconds";
				// die;
			// }
		?>
    </div>
<?php

 }
 ?>