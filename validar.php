<?php
/**
 * validar.php
 * Procesa la validación del código de diploma
 * Con soporte de convocatorias para etiquetas dinámicas
 * Compatible con PHP 5.6+
 */

header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';

// Obtener código enviado por POST
$codigo = isset($_POST['codigo']) ? trim($_POST['codigo']) : '';

// Validar que el código no esté vacío
if (empty($codigo)) {
    echo json_encode(array(
        'success' => false,
        'mensaje' => 'Por favor, ingrese un código de diploma.'
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

// Escapar el código
$codigo_escaped = $conn->real_escape_string($codigo);

// Buscar el diploma con JOIN a convocatorias
// Extraemos el codigo_base quitando los últimos 3 caracteres (correlativo)
$query = "SELECT 
            d.id, 
            d.codigo, 
            d.autores, 
            d.tema,
            c.tipo_documento,
            c.nombre as convocatoria_nombre,
            c.info_institucional,
            c.etiqueta_persona,
            c.etiqueta_tema
          FROM z_diplomas d 
          LEFT JOIN z_convocatorias c ON c.codigo_base = SUBSTRING(d.codigo, 1, LENGTH(d.codigo) - 3)
          WHERE UPPER(d.codigo) = UPPER('$codigo_escaped')";

$result = $conn->query($query);

if (!$result) {
    echo json_encode(array(
        'success' => false,
        'mensaje' => 'Error en la consulta. Intente nuevamente.'
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificar si se encontró el diploma
if ($result->num_rows > 0) {
    $diploma = $result->fetch_assoc();
    
    // Registrar la validación
    $fecha = date('Y-m-d H:i:s');
    $insert_query = "INSERT INTO z_registro (codigo, fecha) VALUES ('$codigo_escaped', '$fecha')";
    @$conn->query($insert_query); // @ para ignorar error si no existe la tabla
    
    // Preparar datos de respuesta con valores por defecto
    $datos = array(
        'codigo' => $diploma['codigo'],
        'autores' => $diploma['autores'],
        'tema' => $diploma['tema'] ? $diploma['tema'] : '',
        'tipo_documento' => $diploma['tipo_documento'] ? $diploma['tipo_documento'] : 'Diploma',
        'convocatoria_nombre' => $diploma['convocatoria_nombre'] ? $diploma['convocatoria_nombre'] : '',
        'info_institucional' => $diploma['info_institucional'] ? $diploma['info_institucional'] : '',
        'etiqueta_persona' => $diploma['etiqueta_persona'] ? $diploma['etiqueta_persona'] : 'Autor(es)',
        'etiqueta_tema' => $diploma['etiqueta_tema'] ? $diploma['etiqueta_tema'] : ''
    );
    
    // Respuesta exitosa
    echo json_encode(array(
        'success' => true,
        'mensaje' => $datos['tipo_documento'] . ' Válido',
        'datos' => $datos
    ), JSON_UNESCAPED_UNICODE);
    
} else {
    // Código no encontrado
    echo json_encode(array(
        'success' => false,
        'mensaje' => 'El código ingresado no se encuentra registrado en nuestro sistema. Verifique que esté escrito correctamente.'
    ), JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
