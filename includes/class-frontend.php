<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WC_Credit_Frontend {

    public static $endpoint = 'mis-creditos';

    public function __construct() {
        // Mostrar planes en la página de producto
        add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'display_credit_plans' ) );

        // Añadir datos del plan al item del carrito
        add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_plan_to_cart_item' ), 10, 3 );
        
        // Mostrar plan en el carrito y checkout
        add_filter( 'woocommerce_get_item_data', array( $this, 'display_plan_in_cart' ), 10, 2 );

        // Crear el crédito cuando se procesa la orden
        add_action( 'woocommerce_checkout_create_order', array( $this, 'create_credit_on_order_creation' ), 20, 2 );
        
        // --- INICIO FASE 3: LÓGICA DE "MI CUENTA" ---

        // 1. Registrar el nuevo endpoint para "Mi Cuenta"
        add_action( 'init', array( $this, 'add_my_account_endpoint' ) );

        // 2. Añadir el nuevo enlace al menú de "Mi Cuenta"
        add_filter( 'woocommerce_account_menu_items', array( $this, 'add_my_credits_link' ) );

        // 3. Renderizar el contenido de la nueva página
        add_action( 'woocommerce_account_' . self::$endpoint . '_endpoint', array( $this, 'my_credits_endpoint_content' ) );
        
        // 4. Cambiar el título de la página
        add_filter( 'the_title', array( $this, 'my_credits_endpoint_title' ) );
        
        // --- FIN FASE 3 ---
    }

    public function display_credit_plans() {
        global $product;
        $plans = WC_Credit_Payment_Plans::get_available_plans_for_product( $product->get_id() );

        if ( empty( $plans ) ) {
            return;
        }

        wc_get_template( 'frontend/product-plans.php', array( 
            'plans'   => $plans,
            'product' => $product,
        ), '', WCPS_PLUGIN_DIR . 'templates/' );
    }

    public function add_plan_to_cart_item( $cart_item_data, $product_id, $variation_id ) {
        if ( isset( $_POST['wcps_selected_plan'] ) && ! empty( $_POST['wcps_selected_plan'] ) ) {
            $cart_item_data['wcps_plan_id'] = absint( $_POST['wcps_selected_plan'] );
        }
        return $cart_item_data;
    }

    public function display_plan_in_cart( $item_data, $cart_item ) {
        if ( isset( $cart_item['wcps_plan_id'] ) ) {
            $plan = WC_Credit_Payment_Plans::get_plan( $cart_item['wcps_plan_id'] );
            if ( $plan ) {
                $item_data[] = array(
                    'key'     => __( 'Plan de Crédito', 'wc-credit-payment-system' ),
                    'value'   => esc_html( $plan->name ),
                    'display' => '',
                );
            }
        }
        return $item_data;
    }
    
    public function create_credit_on_order_creation( $order, $data ) {
        global $wpdb;
        $cart = WC()->cart->get_cart();
        $total_down_payment = 0;
        $credit_product_in_order = false;

        foreach ( $cart as $cart_item_key => $cart_item ) {
            if ( isset( $cart_item['wcps_plan_id'] ) ) {
                $credit_product_in_order = true;
                $plan_id = $cart_item['wcps_plan_id'];
                $plan = WC_Credit_Payment_Plans::get_plan( $plan_id );
                $product = $cart_item['data'];
                $price = (float) $product->get_price();

                // Calcular montos
                $down_payment = ( $price * (float)$plan->down_payment_percentage ) / 100;
                $financed_amount = $price - $down_payment;
                $total_interest = ( $financed_amount * (float)$plan->interest_rate ) / 100;
                $total_financed = $financed_amount + $total_interest;
                $installment_amount = $plan->max_installments > 0 ? $total_financed / $plan->max_installments : 0;

                $total_down_payment += $down_payment * $cart_item['quantity'];
                
                // Insertar en la tabla de cuentas de crédito
                $accounts_table = $wpdb->prefix . 'wc_credit_accounts';
                $wpdb->insert( $accounts_table, [
                    'order_id'          => $order->get_id(),
                    'user_id'           => $order->get_user_id(),
                    'plan_id'           => $plan_id,
                    'product_id'        => $product->get_id(),
                    'total_amount'      => $price,
                    'down_payment'      => $down_payment,
                    'financed_amount'   => $financed_amount,
                    'installment_amount'=> $installment_amount,
                    'total_installments'=> $plan->max_installments,
                    'status'            => 'active'
                ]);
                $credit_account_id = $wpdb->insert_id;

                // Insertar las cuotas individuales
                $installments_table = $wpdb->prefix . 'wc_credit_installments';
                if ($plan->max_installments > 0) {
                    for ( $i = 1; $i <= $plan->max_installments; $i++ ) {
                        $due_date = new DateTime();
                        $interval_string = match($plan->payment_frequency) {
                            'weekly' => "P{$i}W",
                            'biweekly' => "P" . ($i * 2) . "W",
                            default => "P{$i}M",
                        };
                        $due_date->add(new DateInterval($interval_string));

                        $wpdb->insert( $installments_table, [
                            'credit_account_id' => $credit_account_id,
                            'installment_number'=> $i,
                            'amount'            => $installment_amount,
                            'due_date'          => $due_date->format('Y-m-d'),
                            'status'            => 'pending'
                        ]);
                    }
                }
                
                $order->add_order_note( sprintf( __( 'Cuenta de crédito #%d creada para el producto "%s" con el plan "%s".', 'wc-credit-payment-system' ), $credit_account_id, $product->get_name(), $plan->name ) );
            } else {
                 // Si hay un producto sin plan, se suma su precio normal.
                 $total_down_payment += (float) $cart_item['data']->get_price() * $cart_item['quantity'];
            }
        }

        if ( $credit_product_in_order ) {
            $order->set_total( $total_down_payment );
        }
    }
    
    // --- INICIO FASE 3: MÉTODOS DE "MI CUENTA" ---

    public function add_my_account_endpoint() {
        add_rewrite_endpoint( self::$endpoint, EP_ROOT | EP_PAGES );
    }

    public function add_my_credits_link( $menu_links ) {
        $new_links = array_slice( $menu_links, 0, 2, true ) +
                     array( self::$endpoint => __( 'Mis Créditos', 'wc-credit-payment-system' ) ) +
                     array_slice( $menu_links, 2, null, true );
        return $new_links;
    }

    public function my_credits_endpoint_content() {
        // La lógica para decidir qué vista mostrar (lista o detalle) irá aquí.
        $view_credit_id = isset( $_GET['view-credit'] ) ? absint( $_GET['view-credit'] ) : 0;
        
        if ( $view_credit_id > 0 ) {
            // Mostrar la vista de detalle de un crédito
            wc_get_template( 'frontend/view-credit-details.php', 
                ['credit_id' => $view_credit_id], 
                '', 
                WCPS_PLUGIN_DIR . 'templates/' 
            );
        } else {
            // Mostrar la lista de todos los créditos del usuario
            wc_get_template( 'frontend/my-account-credits.php', 
                [], 
                '', 
                WCPS_PLUGIN_DIR . 'templates/' 
            );
        }
    }

    public function my_credits_endpoint_title( $title ) {
        global $wp_query;
        $is_endpoint = isset( $wp_query->query_vars[self::$endpoint] );

        if ( $is_endpoint && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
            $title = __( 'Mis Créditos', 'wc-credit-payment-system' );
            remove_filter( 'the_title', array( $this, 'my_credits_endpoint_title' ) );
        }
        return $title;
    }
    
    // --- FIN FASE 3 ---
}
