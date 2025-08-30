<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wcps-plans-container">
    <h3><?php _e( 'Paga a Crédito', 'wc-credit-payment-system' ); ?></h3>
    <div class="wcps-accordion">
        <?php foreach ( $plans as $plan ) : ?>
            <?php
            // Cálculos básicos para mostrar
            $price = $product->get_price();
            $down_payment = ( $price * $plan->down_payment_percentage ) / 100;
            $financed_amount = $price - $down_payment;
            $total_interest = ( $financed_amount * $plan->interest_rate ) / 100;
            $total_financed = $financed_amount + $total_interest;
            $installment_amount = $total_financed / $plan->max_installments;
            ?>
            <div class="wcps-plan">
                <div class="wcps-plan-header">
                    <input type="radio" name="wcps_selected_plan" id="plan-<?php echo esc_attr( $plan->id ); ?>" value="<?php echo esc_attr( $plan->id ); ?>">
                    <label for="plan-<?php echo esc_attr( $plan->id ); ?>">
                        <strong><?php echo esc_html( $plan->name ); ?>:</strong>
                        <?php echo sprintf( __( '%d cuotas de %s', 'wc-credit-payment-system' ), $plan->max_installments, wc_price( $installment_amount ) ); ?>
                    </label>
                    <span class="wcps-toggle-details">+</span>
                </div>
                <div class="wcps-plan-details" style="display:none;">
                    <ul>
                        <li><strong><?php _e( 'Precio del Producto:', 'wc-credit-payment-system' ); ?></strong> <?php echo wc_price( $price ); ?></li>
                        <li><strong><?php _e( 'Cuota Inicial:', 'wc-credit-payment-system' ); ?></strong> <?php echo wc_price( $down_payment ); ?> (<?php echo $plan->down_payment_percentage; ?>%)</li>
                        <li><strong><?php _e( 'Monto a Financiar:', 'wc-credit-payment-system' ); ?></strong> <?php echo wc_price( $financed_amount ); ?></li>
                        <li><strong><?php _e( 'Interés Aplicado:', 'wc-credit-payment-system' ); ?></strong> <?php echo wc_price( $total_interest ); ?> (<?php echo $plan->interest_rate; ?>%)</li>
                        <li><strong><?php _e( 'Total a Pagar (Financiado):', 'wc-credit-payment-system' ); ?></strong> <?php echo wc_price( $total_financed ); ?></li>
                        <li><strong><?php _e( 'Frecuencia de Pagos:', 'wc-credit-payment-system' ); ?></strong> <?php echo esc_html( ucfirst($plan->payment_frequency) ); ?></li>
                    </ul>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>