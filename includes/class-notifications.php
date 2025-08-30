<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Credit_Notifications {
    public function __construct() {
        add_action( 'woocommerce_email_after_order_table', array( $this, 'add_credit_details_to_emails' ), 10, 4 );
    }

    public function add_credit_details_to_emails( $order, $sent_to_admin, $plain_text, $email ) {
        global $wpdb;
        $account_table = $wpdb->prefix . 'wc_credit_accounts';
        
        $credit_account = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $account_table WHERE order_id = %d",
            $order->get_id()
        ) );

        if ( ! $credit_account ) {
            return;
        }
        
        $plan = WC_Credit_Payment_Plans::get_plan( $credit_account->plan_id );

        if ( $plain_text ) {
            // ... (código existente) ...
        } else {
            // ... (código existente) ...
        }
    }

    /**
     * Envía notificaciones de recordatorio para una cuota específica.
     * @param int $installment_id
     */
    public function send_reminder_notification( $installment_id ) {
        // En esta fase, solo enviaremos el email. WhatsApp se integrará en la siguiente.
        $user = get_user_by( 'id', $user_id ); // Necesitamos obtener el user_id de la cuota.
        
        // 1. Obtener datos necesarios de la BD (usuario, producto, cuota, etc.)
        // 2. Cargar la plantilla de email para "recordatorio de cuota"
        // 3. Reemplazar las variables dinámicas
        // 4. Enviar el email usando WC()->mailer()
        
        // Ejemplo de envío de email (lógica completa en Fase 4)
        $mailer = WC()->mailer();
        $recipient = $user->user_email;
        $subject = 'Recordatorio de Vencimiento de Cuota';
        $content = 'Hola, te recordamos que tu cuota está próxima a vencer...'; // Este contenido vendrá de la BD
        $headers = "Content-Type: text/html\r\n";
        
        // $mailer->send( $recipient, $subject, $mailer->wrap_message( $subject, $content ), $headers, [] );
        
        // Log para depuración
        error_log("Recordatorio de cuota enviado para la cuota ID: " . $installment_id);
    }
}
