<?php
/**
 * Displays the customer sync page for the Softone WooCommerce Integration.
 */
function softone_customers_page() {
    if (isset($_POST['sync_customers'])) {
        $result = softone_sync_customers();
        if (is_array($result) && isset($result['success']) && $result['success']) {
            echo '<div class="notice notice-success"><p>' . esc_html($result['message']) . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Failed to synchronize customers.</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <h1>Customer Sync</h1>
        <form method="post">
            <input type="hidden" name="sync_customers" value="1" />
            <?php submit_button('Sync Customers'); ?>
        </form>
        <?php if (isset($result) && is_array($result) && isset($result['customers'])): ?>
        <h2>Synchronized Customers</h2>
        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Afm</th>
                    <th>Phone</th>
                    <th>Email</th>
                </tr>
            </thead>
            <tbody>
                <?php 
				echo "<pre>";
				print_r($result);
				foreach ($result['customers'] as $c=>$customer): 
				// echo $customer['cust_ID'];
				// $customer = new WC_Customer( $customer_id );
				// echo $customer->get_email() . ' ' . $customer->get_billing_last_name();?>
                <tr>
                    <td><?php echo esc_html($customer['cust_ID']); ?></td>
                    <td><?php echo esc_html($customer['cust_Code']); ?></td>
                    <td><?php echo esc_html($customer['cust_descr']); ?></td>
                    <td><?php echo esc_html($customer['custAfm']); ?></td>
                    <td><?php echo esc_html($customer['cust_phone']); ?></td>
                    <td><?php echo esc_html($customer['cust_Email']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php
}
?>
