<?php
/**
 * procesar-carga-convocatorias.php
 * Procesa el archivo Excel/CSV de convocatorias y carga en tabla temporal
 * Compatible con PHP 5.6+
 */

// Verificar autenticación
require_once 'auth.php';
if (!estaAutenticado()) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('success' => false, 'mensaje' => 'Sesión expirada. Por favor, inicie sesión nuevamente.'));
    exit;
}

// Solo admin
if ($_SESSION['usuario_rol'] !== 'admin') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('success' => false, 'mensaje' => 'No tiene permisos para realizar esta acción.'));
    exit;
}

header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';

// ============================================
// FUNCIONES AUXILIARES
// ============================================

/**
 * Responder en formato JSON y terminar
 */
function responderJSON($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Limpiar y sanear texto
 */
function limpiarTexto($texto) {
    $texto = trim($texto);
    $texto = preg_replace('/\s+/', ' ', $texto);
    return $texto;
}

/**
 * Parsear archivo CSV
 */
function parsearCSV($filepath, $delimiter = ';') {
    $datos = array();
    
    if (($handle = fopen($filepath, 'r')) !== false) {
        // Detectar y eliminar BOM UTF-8
        $bom = fread($handle, 3);
        if ($bom !== "\xef\xbb\xbf") {
            rewind($handle);
        }
        
        // Leer encabezados
        $headers = fgetcsv($handle, 0, $delimiter, '"', '"');
        
        if ($headers === false) {
            fclose($handle);
            return false;
        }
        
        // Normalizar encabezados
        $headers = array_map(function($h) {
            return strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', $h)));
        }, $headers);
        
        // Leer filas de datos
        while (($row = fgetcsv($handle, 0, $delimiter, '"', '"')) !== false) {
            // Saltar filas vacías
            if (count(array_filter($row, function($v) { return trim($v) !== ''; })) === 0) {
                continue;
            }
            
            $registro = array();
            foreach ($headers as $idx => $header) {
                $registro[$header] = isset($row[$idx]) ? $row[$idx] : '';
            }
            $datos[] = $registro;
        }
        
        fclose($handle);
    }
    
    return $datos;
}

/**
 * Parsear archivo Excel XLSX
 */
function parsearExcel($filepath) {
    $datos = array();
    
    $zip = new ZipArchive();
    if ($zip->open($filepath) !== true) {
        return false;
    }
    
    // Leer shared strings
    $sharedStrings = array();
    $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
    
    if ($sharedStringsXml !== false) {
        $xml = @simplexml_load_string($sharedStringsXml);
        if ($xml !== false) {
            foreach ($xml->si as $si) {
                if (isset($si->t)) {
                    $sharedStrings[] = (string)$si->t;
                } elseif (isset($si->r)) {
                    $texto = '';
                    foreach ($si->r as $r) {
                        $texto .= (string)$r->t;
                    }
                    $sharedStrings[] = $texto;
                }
            }
        }
    }
    
    // Leer primera hoja
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    
    if ($sheetXml === false) {
        $zip->close();
        return false;
    }
    
    $xml = @simplexml_load_string($sheetXml);
    
    if ($xml === false) {
        $zip->close();
        return false;
    }
    
    $filas = array();
    
    foreach ($xml->sheetData->row as $row) {
        $fila = array();
        $maxCol = 0;
        
        foreach ($row->c as $cell) {
            $colRef = preg_replace('/[0-9]/', '', (string)$cell['r']);
            $colIndex = excelColumnToIndex($colRef);
            $maxCol = max($maxCol, $colIndex);
            
            $valor = '';
            $tipocelda = isset($cell['t']) ? (string)$cell['t'] : '';
            
            if ($tipocelda === 's') {
                $idx = (int)$cell->v;
                $valor = isset($sharedStrings[$idx]) ? $sharedStrings[$idx] : '';
            } elseif ($tipocelda === 'inlineStr') {
                if (isset($cell->is->t)) {
                    $valor = (string)$cell->is->t;
                } elseif (isset($cell->is->r)) {
                    $valor = '';
                    foreach ($cell->is->r as $r) {
                        $valor .= (string)$r->t;
                    }
                }
            } else {
                $valor = (string)$cell->v;
            }
            
            $fila[$colIndex] = $valor;
        }
        
        for ($i = 0; $i <= $maxCol; $i++) {
            if (!isset($fila[$i])) {
                $fila[$i] = '';
            }
        }
        ksort($fila);
        $filas[] = array_values($fila);
    }
    
    $zip->close();
    
    // Primera fila = encabezados
    if (count($filas) > 0) {
        $headers = array_map(function($h) {
            return strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', $h)));
        }, $filas[0]);
        
        for ($i = 1; $i < count($filas); $i++) {
            if (count(array_filter($filas[$i], function($v) { return trim($v) !== ''; })) === 0) {
                continue;
            }
            
            $registro = array();
            foreach ($headers as $idx => $header) {
                $registro[$header] = isset($filas[$i][$idx]) ? $filas[$i][$idx] : '';
            }
            $datos[] = $registro;
        }
    }
    
    return $datos;
}

/**
 * Convertir letra de columna Excel a índice
 */
function excelColumnToIndex($col) {
    $col = strtoupper($col);
    $length = strlen($col);
    $index = 0;
    
    for ($i = 0; $i < $length; $i++) {
        $index = $index * 26 + (ord($col[$i]) - ord('A') + 1);
    }
    
    return $index - 1;
}

/**
 * Validar formato de código base
 * Formato: T(1) + AAAA(4) + EEEE(4) + C(1) = 10 caracteres
 */
function validarCodigoBase($codigo) {
    $codigo = strtoupper(trim($codigo));
    
    // Debe tener exactamente 10 caracteres
    if (strlen($codigo) !== 10) {
        return array('valido' => false, 'error' => 'Debe tener exactamente 10 caracteres');
    }
    
    // Formato: Letra + 4 dígitos + 4 dígitos + Letra
    if (!preg_match('/^[A-Z][0-9]{4}[0-9]{4}[A-Z]$/', $codigo)) {
        return array('valido' => false, 'error' => 'Formato inválido (debe ser T+AAAA+EEEE+C)');
    }
    
    return array('valido' => true, 'codigo' => $codigo);
}

/**
 * Validar un registro de convocatoria
 */
function validarRegistro($registro, $conn, $session_id) {
    $errores = array();
    
    // Extraer y limpiar campos
    $codigo_base = isset($registro['codigobase']) ? limpiarTexto($registro['codigobase']) : 
                   (isset($registro['codigo_base']) ? limpiarTexto($registro['codigo_base']) : '');
    $nombre = isset($registro['nombre']) ? limpiarTexto($registro['nombre']) : '';
    $tipo_documento = isset($registro['tipodocumento']) ? limpiarTexto($registro['tipodocumento']) : 
                      (isset($registro['tipo_documento']) ? limpiarTexto($registro['tipo_documento']) : '');
    $info_institucional = isset($registro['infoinstitucional']) ? limpiarTexto($registro['infoinstitucional']) : 
                          (isset($registro['info_institucional']) ? limpiarTexto($registro['info_institucional']) : '');
    $etiqueta_persona = isset($registro['etiquetapersona']) ? limpiarTexto($registro['etiquetapersona']) : 
                        (isset($registro['etiqueta_persona']) ? limpiarTexto($registro['etiqueta_persona']) : '');
    $etiqueta_tema = isset($registro['etiquetatema']) ? limpiarTexto($registro['etiquetatema']) : 
                     (isset($registro['etiqueta_tema']) ? limpiarTexto($registro['etiqueta_tema']) : '');
    
    // Validar código base
    if (empty($codigo_base)) {
        $errores[] = 'Código base vacío';
    } else {
        $validacionCodigo = validarCodigoBase($codigo_base);
        if (!$validacionCodigo['valido']) {
            $errores[] = 'Código base: ' . $validacionCodigo['error'];
        } else {
            $codigo_base = $validacionCodigo['codigo'];
            
            // Verificar duplicado en BD oficial
            $codigo_escaped = $conn->real_escape_string($codigo_base);
            $query = "SELECT id FROM z_convocatorias WHERE UPPER(codigo_base) = UPPER('$codigo_escaped')";
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                $errores[] = 'Código base ya existe en el sistema';
            }
            
            // Verificar duplicado en misma sesión de carga
            $query = "SELECT id FROM z_convocatorias_temporal WHERE UPPER(codigo_base) = UPPER('$codigo_escaped') AND session_id = '$session_id'";
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                $errores[] = 'Código base duplicado en el archivo';
            }
        }
    }
    
    // Validar nombre
    if (empty($nombre)) {
        $errores[] = 'Nombre vacío';
    } elseif (strlen($nombre) > 200) {
        $errores[] = 'Nombre excede 200 caracteres';
    }
    
    // Validar tipo documento
    if (empty($tipo_documento)) {
        $errores[] = 'Tipo de documento vacío';
    } elseif (strlen($tipo_documento) > 100) {
        $errores[] = 'Tipo de documento excede 100 caracteres';
    }
    
    // Valores por defecto
    if (empty($etiqueta_persona)) {
        $etiqueta_persona = 'Autor(es)';
    }
    
    return array(
        'codigo_base' => $codigo_base,
        'nombre' => $nombre,
        'tipo_documento' => $tipo_documento,
        'info_institucional' => $info_institucional,
        'etiqueta_persona' => $etiqueta_persona,
        'etiqueta_tema' => $etiqueta_tema,
        'valido' => count($errores) === 0,
        'errores' => $errores
    );
}

// ============================================
// PROCESO PRINCIPAL
// ============================================

// Verificar archivo subido
if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    $errorCodes = array(
        UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido',
        UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido',
        UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
        UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo',
        UPLOAD_ERR_NO_TMP_DIR => 'Error del servidor: falta carpeta temporal',
        UPLOAD_ERR_CANT_WRITE => 'Error del servidor: no se puede escribir',
        UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la carga'
    );
    
    $errorCode = isset($_FILES['archivo']) ? $_FILES['archivo']['error'] : UPLOAD_ERR_NO_FILE;
    $mensaje = isset($errorCodes[$errorCode]) ? $errorCodes[$errorCode] : 'Error desconocido';
    
    responderJSON(array('success' => false, 'mensaje' => $mensaje));
}

// Verificar session_id
$session_id = isset($_POST['session_id']) ? trim($_POST['session_id']) : '';

if (empty($session_id)) {
    responderJSON(array('success' => false, 'mensaje' => 'Sesión de carga no válida.'));
}

$session_id = $conn->real_escape_string($session_id);

// Verificar extensión
$archivo = $_FILES['archivo'];
$extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
$extensionesPermitidas = array('xlsx', 'csv');

if (!in_array($extension, $extensionesPermitidas)) {
    responderJSON(array('success' => false, 'mensaje' => 'Formato no válido. Solo .xlsx o .csv'));
}

// Verificar tamaño
if ($archivo['size'] > 5 * 1024 * 1024) {
    responderJSON(array('success' => false, 'mensaje' => 'El archivo excede 5MB'));
}

// Limpiar datos previos de esta sesión
$queryLimpiar = "DELETE FROM z_convocatorias_temporal WHERE session_id = '$session_id'";
$conn->query($queryLimpiar);

// Parsear archivo
$datos = array();
$tmpFile = $archivo['tmp_name'];

if ($extension === 'csv') {
    // Detectar delimitador
    $contenido = file_get_contents($tmpFile, false, null, 0, 2048);
    $enComillas = false;
    $contComa = 0;
    $contPuntoComa = 0;
    
    for ($i = 0; $i < strlen($contenido); $i++) {
        $char = $contenido[$i];
        if ($char === '"') {
            $enComillas = !$enComillas;
        } elseif (!$enComillas) {
            if ($char === ',') $contComa++;
            if ($char === ';') $contPuntoComa++;
        }
    }
    
    $delimiter = ($contComa > $contPuntoComa) ? ',' : ';';
    $datos = parsearCSV($tmpFile, $delimiter);
} elseif ($extension === 'xlsx') {
    $datos = parsearExcel($tmpFile);
}

// Verificar datos
if (empty($datos) || $datos === false) {
    responderJSON(array(
        'success' => false,
        'mensaje' => 'No se encontraron datos. Verifique que tenga las columnas: codigo_base, nombre, tipo_documento'
    ));
}

// Verificar columnas requeridas
$primeraFila = $datos[0];
$columnasArchivo = array_keys($primeraFila);

// Normalizar nombres de columnas para verificación
$columnasNormalizadas = array();
foreach ($columnasArchivo as $col) {
    $columnasNormalizadas[] = strtolower(str_replace('_', '', $col));
}

$columnasRequeridas = array('codigobase', 'nombre', 'tipodocumento');
$faltantes = array();

foreach ($columnasRequeridas as $req) {
    $encontrada = false;
    foreach ($columnasNormalizadas as $col) {
        if ($col === $req || $col === str_replace('_', '', $req)) {
            $encontrada = true;
            break;
        }
    }
    if (!$encontrada) {
        // Buscar también con guión bajo
        $reqConGuion = preg_replace('/([a-z])([A-Z])/', '$1_$2', $req);
        foreach ($columnasArchivo as $col) {
            if (strtolower($col) === $reqConGuion || strtolower(str_replace('_', '', $col)) === $req) {
                $encontrada = true;
                break;
            }
        }
    }
    if (!$encontrada) {
        $faltantes[] = $req;
    }
}

if (!empty($faltantes)) {
    responderJSON(array(
        'success' => false,
        'mensaje' => 'Faltan columnas requeridas: ' . implode(', ', $faltantes) . '. Columnas encontradas: ' . implode(', ', $columnasArchivo)
    ));
}

// Procesar cada registro
$registros = array();
$totalValidos = 0;
$totalErrores = 0;
$fecha = date('Y-m-d H:i:s');

foreach ($datos as $idx => $fila) {
    $validacion = validarRegistro($fila, $conn, $session_id);
    
    $estado = $validacion['valido'] ? 'valido' : 'error';
    $mensajeError = $validacion['valido'] ? '' : implode('; ', $validacion['errores']);
    
    if ($validacion['valido']) {
        $totalValidos++;
    } else {
        $totalErrores++;
    }
    
    // Insertar en temporal
    $codigo_esc = $conn->real_escape_string($validacion['codigo_base']);
    $nombre_esc = $conn->real_escape_string($validacion['nombre']);
    $tipo_esc = $conn->real_escape_string($validacion['tipo_documento']);
    $info_esc = $conn->real_escape_string($validacion['info_institucional']);
    $etiq_persona_esc = $conn->real_escape_string($validacion['etiqueta_persona']);
    $etiq_tema_esc = $validacion['etiqueta_tema'] ? "'" . $conn->real_escape_string($validacion['etiqueta_tema']) . "'" : "NULL";
    $mensaje_esc = $conn->real_escape_string($mensajeError);
    
    $queryInsert = "INSERT INTO z_convocatorias_temporal 
                    (codigo_base, nombre, tipo_documento, info_institucional, etiqueta_persona, etiqueta_tema, session_id, estado, mensaje_error, fecha_carga) 
                    VALUES 
                    ('$codigo_esc', '$nombre_esc', '$tipo_esc', '$info_esc', '$etiq_persona_esc', $etiq_tema_esc, '$session_id', '$estado', '$mensaje_esc', '$fecha')";
    
    if (!$conn->query($queryInsert)) {
        if ($validacion['valido']) {
            $totalValidos--;
        }
        $totalErrores++;
        $estado = 'error';
        $mensajeError = 'Error al guardar: ' . $conn->error;
    }
    
    // Agregar a lista para preview
    $registros[] = array(
        'codigo_base' => $validacion['codigo_base'],
        'nombre' => $validacion['nombre'],
        'tipo_documento' => $validacion['tipo_documento'],
        'info_institucional' => $validacion['info_institucional'],
        'etiqueta_persona' => $validacion['etiqueta_persona'],
        'etiqueta_tema' => $validacion['etiqueta_tema'],
        'estado' => $estado,
        'mensaje_error' => $mensajeError
    );
}

// Responder
responderJSON(array(
    'success' => true,
    'mensaje' => 'Archivo procesado correctamente',
    'total' => count($registros),
    'validos' => $totalValidos,
    'errores' => $totalErrores,
    'registros' => $registros,
    'session_id' => $session_id
));
?>
