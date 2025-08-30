jQuery(document).ready(function($) {
    // Lógica del acordeón para los detalles del plan
    $('.wcps-toggle-details').on('click', function(e) {
        e.preventDefault();
        var $details = $(this).closest('.wcps-plan').find('.wcps-plan-details');
        $details.slideToggle();
        $(this).text($(this).text() === '+' ? '-' : '+');
    });

    // Asegurarse de que el formulario de "añadir al carrito" sepa qué plan se seleccionó
    // El radio button ya está dentro del <form>, así que su valor se enviará automáticamente.
});