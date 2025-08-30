// ... (dentro de la clase WC_Credit_Payment_Plans) ...

/**
 * Obtener planes disponibles para un producto específico.
 * @param int $product_id
 * @return array
 */
public static function get_available_plans_for_product( $product_id ) {
    global $wpdb;
    $product = wc_get_product( $product_id );
    if ( ! $product ) {
        return [];
    }

    $category_ids = $product->get_category_ids();
    $category_ids_placeholders = ! empty( $category_ids ) ? implode( ',', array_fill( 0, count( $category_ids ), '%d' ) ) : 'NULL';

    $plans_table = $wpdb->prefix . 'wc_credit_plans';
    $assignments_table = $wpdb->prefix . 'wc_credit_plan_assignments';

    $query = $wpdb->prepare(
        "SELECT DISTINCT p.* FROM $plans_table p
         LEFT JOIN $assignments_table a ON p.id = a.plan_id
         WHERE p.status = 'active'
         AND (
            (a.assignment_type = 'product' AND a.assignment_id = %d) OR
            (a.assignment_type = 'category' AND a.assignment_id IN ($category_ids_placeholders)) OR
            (NOT EXISTS (SELECT 1 FROM $assignments_table WHERE plan_id = p.id))
         )
         ORDER BY p.name ASC",
        array_merge( [$product_id], $category_ids )
    );

    return $wpdb->get_results( $query );
}