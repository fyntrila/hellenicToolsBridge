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
			<label for="syncDate">Ελεγχο για αλλαγες απο:</label>
			<input type="date" id="syncDate" name="syncDate">
			<label for="WebActive">Μονο WebActive:</label>
			<input type="checkbox" id="WebActive" name="WebActive" value="1">	
            <?php submit_button('Sync Products'); ?>
        </form>
		
		<form method="post">
			<input type="hidden" name="update_or_insert" value="1"/>
			<?php submit_button('Check for update or insert'); ?>
		</form>
		
			<?php 
			
			if (isset($_POST['sync_products'])): 
				$metr=0;
				$WebActive=0;
				$WebActive=(isset($_POST['WebActive']))?$_POST['WebActive']:0;
				// echo "mpika <pre>";
				$fromDate=(!empty($_POST['syncDate']))?$_POST['syncDate']:'2025-11-29T05:21:41Z';
				// $fromDate='2025-11-29T05:21:41Z';
				// $WebActive=(isset($_POST['WebActive']))?1:null;
				$lastUpdatedItems=$api->getLastUpdatedItems($fromDate);?>
				<h2>Synchronized Products</h2>
				<table class="widefat fixed" cellspacing="0">
					<thead>
						<tr>
							<th>aa</th> 
							<th>item_id</th> 
							<th>item_code</th> 
							<th>item_descr</th> 
							<th>price_WholeSale</th> 
							<th>price_Retail</th> 
							<th>rem</th> 
							<th>reserved</th> 
							<th>item_category</th> 
							<th>item_group</th> 
							<th>item_mark</th> 
							<th>Manufacturers</th> 
							<th>WebActive</th> 
							<th>item_barcode</th> 
							<th>dim1_code</th> 
							<th>dim1_color</th> 
							<th>dim2_code</th> 
							<th>dim2_size</th> 
							<th>dim1_erp_id</th> 
							<th>dim2_erp_id</th> 
							<th>dim1_bal</th> 
							<th>dim1_reserved</th>
						</tr>
					</thead>
				
					<tbody>
					<?php 
					
						foreach($lastUpdatedItems['body'] as $updatedItem){
							if($updatedItem['WebActive']==$WebActive || !$WebActive){
								echo "<tr>";
									echo "<td>".(++$metr)."</td>";
									echo "<td>".$updatedItem['item_id']."</td>";
									echo "<td>".$updatedItem['item_code']."</td>";
									echo "<td>".$updatedItem['item_descr']."</td>";
									echo "<td>".$updatedItem['price_WholeSale']."</td>";
									echo "<td>".$updatedItem['price_Retail']."</td>";
									echo "<td>".$updatedItem['rem']."</td>";
									echo "<td>".$updatedItem['reserved']."</td>";
									echo "<td>".$updatedItem['item_category']."</td>";
									echo "<td>".$updatedItem['item_group']."</td>";
									echo "<td>".$updatedItem['item_mark']."</td>";
									echo "<td>".$updatedItem['Manufacturers']."</td>";
									echo "<td>".$updatedItem['WebActive']."</td>";
									echo "<td>".$updatedItem['item_barcode']."</td>";
									echo "<td>".$updatedItem['dim1_code']."</td>";
									echo "<td>".$updatedItem['dim1_color']."</td>";
									echo "<td>".$updatedItem['dim2_code']."</td>";
									echo "<td>".$updatedItem['dim2_size']."</td>";
									echo "<td>".$updatedItem['dim1_erp_id']."</td>";
									echo "<td>".$updatedItem['dim2_erp_id']."</td>";
									echo "<td>".$updatedItem['dim1_bal']."</td>";
									echo "<td>".$updatedItem['dim1_reserved']."</td>";
								echo "</tr>";
							}
						}	
					// print_r($lastUpdatedItems['body']);
					// echo "vgika";
					// echo "vgika";
					?>
					</tbody>
				</table>   
				<?php 
				echo $metr. ' changes found since '.$fromDate;
				echo $WebActive;
				endif; 
				
				if (isset($_POST['update_or_insert'])): 
				$start_time = microtime(true);
				$args = array(
					'limit' => -1,
					'status' => 'publish',
					'return' => 'ids',
				);
				$product_ids = wc_get_products( $args );
				$api = new Softone_API();
					$lastUpdatedItems=$api->getLastUpdatedItems('2026-01-01T00:00:00Z');
					// echo "<pre>";
					// print_r($lastUpdatedItems['body']);
					// echo "</pre>";
					echo count($lastUpdatedItems['body']);
					if(!empty($lastUpdatedItems['body'])){
						// echo "<pre>";
						// print_r($lastUpdatedItems['body']);
						// echo "</pre>";
						$updateInsertItems=$api->updateInsertItems($lastUpdatedItems['body']);
						echo "<pre>";
						print_r($updateInsertItems['items']);
						echo "</pre>";
					}
					else {
						echo "empty last update items";
					}
				endif;
				
			// if($i==100){
				// End Clock Time in Seconds
				$end_time = microtime(true);

				// Calculate the Script Execution Time
				$execution_time = ($end_time - $start_time);
				// if(isset($metr))
					// echo "Totals: ".$metr
				//$totals['update']
				echo "Update:".$updateInsertItems['update']." Insert.".$updateInsertItems['insert']." Skipped:".$updateInsertItems['skipped']."in ".$execution_time." seconds";
				// die;
			// }
		?>
    </div>
<?php

 }
 ?>