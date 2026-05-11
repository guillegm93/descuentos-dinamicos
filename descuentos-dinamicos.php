<?php
/* 
 * Plugin Name: Descuentos Dinámicos WooCommerce
 * Description: Plugin para aplicar descuentos automaticos
 * Version: 1.0
 * Author: Guillermo Gonzalez Maroto
 */

if(!defined('ABSPATH')) exit;

/***
 * ADMIN PANEL
 */

// Menú
add_action('admin_menu', function(){
    add_menu_page(
            'Descuentos Dinamicos',
            'Descuentos',
            'manage_options',
            'descuentos-dinamicos',
            'dd_admin_page',
            'dashicons-tag',
            20
    );
});

// Registro de opciones
add_action('admin_init', function(){
        register_setting('dd_settings_group', 'dd_enable_qty');
        register_setting('dd_settings_group', 'dd_qty_discount');
        register_setting('dd_settings_group', 'dd_enable_2x1');
        register_setting('dd_settings_group', 'dd_enable_role');
        register_setting('dd_settings_group', 'dd_role_discount');
});

// Página de administracion
function dd_admin_page(){
    ?>
<div class="wrap">
    <h1>Configuracion de Descuentos</h1>
    <form method="post" action="options.php">
        <?php settings_fields('dd_settings_group'); ?>
        <?php do_settings_sections('dd_settings_group'); ?>
        <table class="form-table">
            <tr>
                <th>Activar descuento por cantidad</th>
                <td>
                    <input type="checkbox" name="dd_enable_qty" value="1" <?php checked(1, get_option('dd_enable_qty')); ?>>
                </td>
            </tr>
            <tr>
                <th>Porcentaje descuento (%)</th>
                <td>
                    <input type="number" name="dd_qty_discount" value="<?php echo esc_attr(get_option('dd_qty_discount', 10)); ?>">
                </td>
            </tr>
            <tr>
                <th>Activar 2x1</th>
                <td>
                    <input type="checkbox" name="dd_enable_2x1" value="1" <?php checked(1, get_option('dd_enable_2x1')); ?>>
                </td>
            </tr>
            <tr>
                <th>Activar descuento por rol</th>
                <td>
                    <input type="checkbox" name="dd_enable_role" value="1" <?php checked(1, get_option('dd_enable_role')); ?>>
                </td>
            </tr>
            <tr>
                <th>Descuento por rol (%)</th>
                <td>
                    <input type="number" name="dd_role_discount" value="<?php echo esc_attr(get_option('dd_role_discount',5));?>">
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>
<?php
}

// Hook de WooCommerce
add_action('woocommerce_cart_calculate_fees', 'aplicar_descuentos_dinamicos');

function aplicar_descuentos_dinamicos($cart) {

    if (is_admin() && !defined('DOING_AJAX')) return;

    if (did_action('woocommerce_cart_calculate_fees') > 1) {
        return;
    }

    $descuento_total = 0;
    $user = wp_get_current_user();

    // Opciones
    $enable_qty = get_option('dd_enable_qty');
    $qty_discount = get_option('dd_qty_discount', 10);

    $enable_2x1 = get_option('dd_enable_2x1');

    $enable_role = get_option('dd_enable_role');
    $role_discount = get_option('dd_role_discount', 5);

    foreach ($cart->get_cart() as $item) {

        $cantidad = $item['quantity'];

        // Precio correcto
        $precio = $item['line_subtotal'] / $cantidad;

        $descuentos = [];

        // 🔹 Descuento por cantidad
        if ($enable_qty && $cantidad >= 3) {
            $descuentos[] = ($precio * $cantidad) * ($qty_discount / 100);
        }

        // 🔹 2x1
        if ($enable_2x1 && $cantidad >= 2) {

            $unidades_gratis = floor($cantidad / 2);

            $descuentos[] = $unidades_gratis * $precio;
        }

        // 🔹 Descuento por rol
        if ($enable_role && in_array('customer', (array) $user->roles)) {
            $descuentos[] = ($precio * $cantidad) * ($role_discount / 100);
        }

        // Aplicar el mejor descuento
        if (!empty($descuentos)) {
            $descuento_total += max($descuentos);
        }
    }

    // Añadir descuento
    if ($descuento_total > 0) {
        $cart->add_fee('Descuentos aplicados', -$descuento_total);
    }
}
