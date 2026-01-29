<?php
/**
 * descargar.php
 * Fuerza la descarga de archivos de plantilla
 */

// Verificar autenticación
require_once 'auth.php';
if (!estaAutenticado()) {
    header('Location: login.php');
    exit;
}

// Archivos permitidos (por seguridad)
$archivosPermitidos = array(
    'csv'  => 'plantillas/plantilla.csv',
    'xlsx' => 'plantillas/plantilla.xlsx'
);

// Obtener tipo solicitado
$tipo = isset($_GET['tipo']) ? strtolower($_GET['tipo']) : '';

// Validar que el tipo sea permitido
if (!isset($archivosPermitidos[$tipo])) {
    http_response_code(404);
    die('Archivo no encontrado');
}

$archivo = $archivosPermitidos[$tipo];

// Verificar que el archivo existe
if (!file_exists($archivo)) {
    http_response_code(404);
    die('Archivo no encontrado');
}

// Determinar el tipo MIME
$mimeTypes = array(
    'csv'  => 'text/csv',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
);

$mimeType = $mimeTypes[$tipo];
$nombreArchivo = basename($archivo);

// Headers para forzar descarga
header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($archivo));

// Limpiar buffer y enviar archivo
ob_clean();
flush();
readfile($archivo);
exit;
?>
