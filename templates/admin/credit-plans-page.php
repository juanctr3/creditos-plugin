<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e( 'Planes de Crédito', 'wc-credit-payment-system' ); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=wc-credit-plans&action=new'); ?>" class="page-title-action"><?php _e( 'Añadir Nuevo Plan', 'wc-credit-payment-system' ); ?></a>

    <?php if ( isset( $_GET['success'] ) ): ?>
    <div id="message" class="updated notice is-dismissible">
        <p><?php _e( 'Plan guardado correctamente.', 'wc-credit-payment-system' ); ?></p>
    </div>
    <?php endif; ?>

    <hr class="wp-header-end">

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column"><?php _e( 'Nombre del Plan', 'wc-credit-payment-system' ); ?></th>
                <th scope="col" class="manage-column"><?php _e( 'Interés (%)', 'wc-credit-payment-system' ); ?></th>
                <th scope="col" class="manage-column"><?php _e( 'Cuotas Máximas', 'wc-credit-payment-system' ); ?></th>
                <th scope="col" class="manage-column"><?php _e( 'Frecuencia', 'wc-credit-payment-system' ); ?></th>
                <th scope="col" class="manage-column"><?php _e( 'Estado', 'wc-credit-payment-system' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $plans = WC_Credit_Payment_Plans::get_all_plans();
            if ( $plans ) {
                foreach ( $plans as $plan ) {
                    ?>
                    <tr>
                        <td>
                            <strong><a href="<?php echo admin_url('admin.php?page=wc-credit-plans&action=edit&plan_id=' . $plan->id); ?>"><?php echo esc_html($plan->name); ?></a></strong>
                        </td>
                        <td><?php echo esc_html($plan->interest_rate); ?>%</td>
                        <td><?php echo esc_html($plan->max_installments); ?></td>
                        <td><?php echo esc_html( ucfirst($plan->payment_frequency) ); ?></td>
                        <td>
                            <span class="<?php echo $plan->status === 'active' ? 'dashicons dashicons-yes-alt' : 'dashicons dashicons-no-alt'; ?>"></span>
                            <?php echo $plan->status === 'active' ? __( 'Activo', 'wc-credit-payment-system' ) : __( 'Inactivo', 'wc-credit-payment-system' ); ?>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                ?>
                <tr>
                    <td colspan="5"><?php _e( 'No se encontraron planes de crédito.', 'wc-credit-payment-system' ); ?></td>
                </tr>
                <?php
            }
            ?>
        </tbody>
    </table>
</div>