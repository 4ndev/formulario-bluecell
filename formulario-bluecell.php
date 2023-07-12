<?php
/*
* Plugin Name: Formulario Bluecell
* Plugin URI: https://github.com/4ndev/formulario-bluecell
* Description: Plugin para mostrar un formulario al final del contenido de una página individual (single.php).
* Version: 0.1
* Author: Andrés Alvarado
* Author URI: https://github.com/4ndev
*/

// Creación de la tabla en la DB
function bluecell_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'bluecell_entries';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        phone varchar(20) NOT NULL,
        message text NOT NULL,
        subject varchar(100) NOT NULL,
        accept tinyint(1) NOT NULL,
        date datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'bluecell_create_table');

// Inserta el formulario después del contenido de la página individual
function bluecell_insert_form_after_content( $content ) {
    if ( is_single() ) {
        ob_start();
        ?>
			<form id="bluecell-form" method="post" action="">
				<label for="bluecell-name">Nombre:</label></br>
				<input type="text" name="bluecell-name" id="bluecell-name" class="bluecell-input" required></br>

				<label for="bluecell-email">Email:</label></br>
				<input type="email" name="bluecell-email" id="bluecell-email" class="bluecell-input" required></br>

				<label for="bluecell-phone">Teléfono:</label></br>
				<input type="tel" name="bluecell-phone" id="bluecell-phone" class="bluecell-input" required></br>

				<label for="bluecell-message">Mensaje:</label></br>
				<textarea name="bluecell-message" id="bluecell-message" class="bluecell-input" required></textarea></br>

				<label for="bluecell-subject">Asunto:</label></br>
				<input type="text" name="bluecell-subject" id="bluecell-subject" class="bluecell-input" required></br>

				<label for="bluecell-accept">Acepto las políticas:</label>
				<input type="checkbox" name="bluecell-accept" id="bluecell-accept" class="bluecell-input" required></br>

				<input type="submit" value="Enviar" id="bluecell-submit">
			</form>
        <?php
        $form = ob_get_clean();
        $content .= $form;
    }
    return $content;
}
add_filter( 'the_content', 'bluecell_insert_form_after_content' );

// Agrega el script jQuery y bluecell-script.js
function bluecell_enqueue_scripts() {
    wp_enqueue_script('jquery');

    wp_register_script('bluecell-script', plugins_url('js/bluecell-script.js', __FILE__), array('jquery'), '1.0', true);
    wp_enqueue_script('bluecell-script');

    wp_localize_script('bluecell-script', 'bluecell_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('bluecell-nonce')
    ));
}
add_action('wp_enqueue_scripts', 'bluecell_enqueue_scripts');

// Agrega estilos y scripts de DataTables
function bluecell_enqueue_datatables() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-datatables', 'https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js', array('jquery'), '1.13.5', true);
    wp_enqueue_style('datatables-style', 'https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css', array(), '1.13.5');
}
add_action('admin_enqueue_scripts', 'bluecell_enqueue_datatables');


// Agrega la página de administración para mostrar los datos enviados
function bluecell_add_admin_page() {
    add_menu_page(
        'Bluecell Entries',
        'Bluecell Entries',
        'manage_options',
        'bluecell-entries',
        'bluecell_entries_page',
        'dashicons-list-view',
        20
    );
}
add_action('admin_menu', 'bluecell_add_admin_page');

// Contenido de la página de administración
function bluecell_entries_page() {
    ?>
    <div class="wrap">
        <h1><?= get_admin_page_title() ?></h1>
        <table id="bluecell-entries-table" class="stripe hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Mensaje</th>
                    <th>Asunto</th>
                    <th>Aceptación</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php
					global $wpdb;
					$table_name = $wpdb->prefix . 'bluecell_entries';
					$entries = $wpdb->get_results("SELECT * FROM $table_name");
				?>
				<?php foreach ($entries as $entry): ?>
					<tr>
						<td><?= $entry->id; ?></td>
						<td><?= $entry->name; ?></td>
						<td><?= $entry->email; ?></td>
						<td><?= $entry->phone; ?></td>
						<td><?= $entry->message; ?></td>
						<td><?= $entry->subject; ?></td>
						<td><?= $entry->accept ? 'Sí' : 'No'; ?></td>
						<td><?= $entry->date; ?></td>
					</tr>                
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
	<script>
		jQuery(document).ready(function($) {
			$('#bluecell-entries-table').DataTable();
		});
    </script>
    <?php
}


// Procesa el envío del formulario por Ajax
function bluecell_process_form() {
    check_ajax_referer('bluecell-nonce', 'security');

    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $phone = sanitize_text_field($_POST['phone']);
    $message = sanitize_textarea_field($_POST['message']);
    $subject = sanitize_text_field($_POST['subject']);
    $accept = isset($_POST['accept']) ? 1 : 0;

    global $wpdb;
    $table_name = $wpdb->prefix . 'bluecell_entries';

    $data = array(
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'message' => $message,
        'subject' => $subject,
        'accept' => $accept,
        'date' => current_time('mysql')
    );

    $wpdb->insert($table_name, $data);
	
    wp_send_json_success('Formulario enviado con éxito');
}
add_action('wp_ajax_bluecell_process_form', 'bluecell_process_form');
add_action('wp_ajax_nopriv_bluecell_process_form', 'bluecell_process_form');

// Acción para eliminar la tabla "bluecell_entries" de DB al desinstalar el plugin
function bluecell_delete_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'bluecell_entries';

    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}
register_uninstall_hook(__FILE__, 'bluecell_delete_table');
