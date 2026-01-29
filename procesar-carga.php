<?php
/**
 * procesar-carga.php
 * Procesa el archivo Excel/CSV y carga los datos en la tabla temporal
 * Compatible con PHP 5.6+
 */

// Verificar autenticación
require_once 'auth.php';
if (!estaAutenticado()) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('success' => false, 'mensaje' => 'Sesión expirada. Por favor, inicie sesión nuevamente.'));
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// Incluir configuración de base de datos
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
 * Parsear archivo CSV con mejor manejo de delimitadores y comillas
 */
function parsearCSV($filepath, $delimiter = ';') {
    $datos = array();
    
    if (($handle = fopen($filepath, 'r')) !== false) {
        // Detectar y eliminar BOM UTF-8
        $bom = fread($handle, 3);
        if ($bom !== "\xef\xbb\xbf") {
            rewind($handle);
        }
        
        // Leer encabezados con el delimitador detectado
        // El tercer parámetro es el enclosure (comillas), cuarto es escape
$headers = fgetcsv($handle, 0, $delimiter, '"', '"');
        
        if ($headers === false) {
            fclose($handle);
            return false;
        }
        
        // Normalizar encabezados (minúsculas, sin caracteres especiales)
        $headers = array_map(function($h) {
            return strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $h)));
        }, $headers);
        
        // Leer filas de datos
		while (($row = fgetcsv($handle, 0, $delimiter, '"', '"')) !== false) {
            // Saltar filas completamente vacías
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
    
    // Leer shared strings (textos compartidos)
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
    
    // Leer primera hoja (sheet1)
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
            
            // Tipo 's' = referencia a shared string
            if ($tipocelda === 's') {
                $idx = (int)$cell->v;
                $valor = isset($sharedStrings[$idx]) ? $sharedStrings[$idx] : '';
            }
            // Tipo 'inlineStr' = string inline (usado por algunas librerías)
            elseif ($tipocelda === 'inlineStr') {
                if (isset($cell->is->t)) {
                    $valor = (string)$cell->is->t;
                } elseif (isset($cell->is->r)) {
                    // Rich text inline
                    $valor = '';
                    foreach ($cell->is->r as $r) {
                        $valor .= (string)$r->t;
                    }
                }
            }
            // Valor directo en <v>
            else {
                $valor = (string)$cell->v;
            }
            
            $fila[$colIndex] = $valor;
        }
        
        // Rellenar columnas vacías intermedias
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
            return strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $h)));
        }, $filas[0]);
        
        for ($i = 1; $i < count($filas); $i++) {
            // Saltar filas vacías
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
 * Convertir letra de columna Excel a índice numérico
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
 * Validar un registro individual
 */
function validarRegistro($registro, $conn, $session_id, $codigo_base = null) {
    $errores = array();
    $codigoInvalido = false;
    
    // Extraer y limpiar campos
    $codigo = isset($registro['codigo']) ? limpiarTexto($registro['codigo']) : '';
    $autores = isset($registro['autores']) ? limpiarTexto($registro['autores']) : '';
    $tema = isset($registro['tema']) ? limpiarTexto($registro['tema']) : '';
    
    // Validar código
    if (empty($codigo)) {
        $errores[] = 'Código vacío';
    } else {
        if (strlen($codigo) > 50) {
            $errores[] = 'Código excede 50 caracteres';
        }
        
        // Validar formato contra codigo_base de convocatoria
        if ($codigo_base !== null) {
            $longitudEsperada = strlen($codigo_base) + 3; // codigo_base (10) + correlativo (3) = 13
            
            if (strlen($codigo) !== $longitudEsperada) {
                $errores[] = "Código debe tener $longitudEsperada caracteres";
                $codigoInvalido = true;
            } elseif (strtoupper(substr($codigo, 0, strlen($codigo_base))) !== strtoupper($codigo_base)) {
                $errores[] = "Código debe iniciar con $codigo_base";
                $codigoInvalido = true;
            } else {
                // Verificar que los últimos 3 caracteres sean numéricos
                $correlativo = substr($codigo, -3);
                if (!preg_match('/^[0-9]{3}$/', $correlativo)) {
                    $errores[] = 'Correlativo debe ser numérico (001-999)';
                    $codigoInvalido = true;
                }
            }
        }
        
        // Verificar duplicado en tabla oficial
        $codigo_escaped = $conn->real_escape_string($codigo);
        $query = "SELECT id FROM z_diplomas WHERE UPPER(codigo) = UPPER('$codigo_escaped')";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $errores[] = 'Código ya existe en el sistema';
        }
        
        // Verificar duplicado en la misma sesión de carga
        $query = "SELECT id FROM z_diplomas_temporal WHERE UPPER(codigo) = UPPER('$codigo_escaped') AND session_id = '$session_id'";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $errores[] = 'Código duplicado en el archivo';
        }
    }
    
    // Validar autores
    if (empty($autores)) {
        $errores[] = 'Autores vacío';
    }
    
    // Validar tema (OPCIONAL - solo validar longitud si tiene contenido)
   if (!empty($tema) && strlen($tema) > 255) {
        $errores[] = 'Tema excede 255 caracteres';
    }
    
    return array(
        'codigo' => $codigo,
        'autores' => $autores,
        'tema' => $tema,
        'valido' => count($errores) === 0,
        'codigo_invalido' => $codigoInvalido,
        'errores' => $errores
    );
}

// ============================================
// PROCESO PRINCIPAL
// ============================================

// Verificar que se subió un archivo
if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    $errorCodes = array(
        UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor',
        UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido',
        UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
        UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo',
        UPLOAD_ERR_NO_TMP_DIR => 'Error del servidor: falta carpeta temporal',
        UPLOAD_ERR_CANT_WRITE => 'Error del servidor: no se puede escribir el archivo',
        UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la carga'
    );
    
    $errorCode = isset($_FILES['archivo']) ? $_FILES['archivo']['error'] : UPLOAD_ERR_NO_FILE;
    $mensaje = isset($errorCodes[$errorCode]) ? $errorCodes[$errorCode] : 'Error desconocido al subir el archivo';
    
    responderJSON(array(
        'success' => false,
        'mensaje' => $mensaje
    ));
}

// Verificar session_id
$session_id = isset($_POST['session_id']) ? trim($_POST['session_id']) : '';

if (empty($session_id)) {
    responderJSON(array(
        'success' => false,
        'mensaje' => 'Sesión de carga no válida. Por favor, recargue la página.'
    ));
}

$session_id = $conn->real_escape_string($session_id);

// Obtener convocatoria
$convocatoria_id = isset($_POST['convocatoria_id']) ? (int)$_POST['convocatoria_id'] : 0;
$codigo_base = null;
$convocatoria_nombre = null;

if ($convocatoria_id > 0) {
    $queryConv = "SELECT codigo_base, nombre FROM z_convocatorias WHERE id = $convocatoria_id AND activo = 1";
    $resultConv = $conn->query($queryConv);
    
    if ($resultConv && $resultConv->num_rows > 0) {
        $conv = $resultConv->fetch_assoc();
        $codigo_base = $conv['codigo_base'];
        $convocatoria_nombre = $conv['nombre'];
    } else {
        responderJSON(array(
            'success' => false,
            'mensaje' => 'Convocatoria no válida o no está activa.'
        ));
    }
} else {
    responderJSON(array(
        'success' => false,
        'mensaje' => 'Debe seleccionar una convocatoria antes de cargar el archivo.'
    ));
}

// Verificar extensión del archivo
$archivo = $_FILES['archivo'];
$nombreOriginal = $archivo['name'];
$extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
$extensionesPermitidas = array('xlsx', 'csv');

if (!in_array($extension, $extensionesPermitidas)) {
    responderJSON(array(
        'success' => false,
        'mensaje' => 'Formato de archivo no válido. Solo se permiten archivos .xlsx o .csv'
    ));
}

// Verificar tamaño (máximo 5MB)
if ($archivo['size'] > 5 * 1024 * 1024) {
    responderJSON(array(
        'success' => false,
        'mensaje' => 'El archivo excede el tamaño máximo de 5MB'
    ));
}

// Limpiar datos previos de esta sesión
$queryLimpiar = "DELETE FROM z_diplomas_temporal WHERE session_id = '$session_id'";
$conn->query($queryLimpiar);

// Parsear archivo según tipo
$datos = array();
$tmpFile = $archivo['tmp_name'];

if ($extension === 'csv') {
    // Detectar delimitador de forma inteligente
    // Contar delimitadores FUERA de comillas para evitar falsos positivos
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
    
    // Usar el delimitador que tenga más ocurrencias fuera de comillas
    $delimiter = ($contComa > $contPuntoComa) ? ',' : ';';
    
    $datos = parsearCSV($tmpFile, $delimiter);
} elseif ($extension === 'xlsx') {
    $datos = parsearExcel($tmpFile);
}

// Verificar que se obtuvieron datos
if (empty($datos) || $datos === false) {
    responderJSON(array(
        'success' => false,
        'mensaje' => 'No se encontraron datos en el archivo o el formato no es válido. Verifique que el archivo tenga las columnas: codigo, autores, tema'
    ));
}

// Verificar columnas requeridas
$primeraFila = $datos[0];
$columnasRequeridas = array('codigo', 'autores', 'tema');
$columnasArchivo = array_keys($primeraFila);

foreach ($columnasRequeridas as $col) {
    if (!in_array($col, $columnasArchivo)) {
        responderJSON(array(
            'success' => false,
            'mensaje' => "Falta la columna requerida: '$col'. Las columnas encontradas son: " . implode(', ', $columnasArchivo)
        ));
    }
}

// Procesar cada registro
$registros = array();
$totalValidos = 0;
$totalErrores = 0;
$totalCodigoInvalido = 0;
$fecha = date('Y-m-d H:i:s');

foreach ($datos as $idx => $fila) {
    $validacion = validarRegistro($fila, $conn, $session_id, $codigo_base);
    
    // Determinar estado
    if ($validacion['valido']) {
        $estado = 'valido';
        $totalValidos++;
    } elseif ($validacion['codigo_invalido']) {
        $estado = 'codigo_invalido';
        $totalCodigoInvalido++;
        $totalErrores++;
    } else {
        $estado = 'error';
        $totalErrores++;
    }
    
    $mensajeError = $validacion['valido'] ? '' : implode('; ', $validacion['errores']);
    
    // Insertar en tabla temporal
    $codigo_escaped = $conn->real_escape_string($validacion['codigo']);
    $autores_escaped = $conn->real_escape_string($validacion['autores']);
    $tema_escaped = $conn->real_escape_string($validacion['tema']);
    $mensaje_escaped = $conn->real_escape_string($mensajeError);
    
    $queryInsert = "INSERT INTO z_diplomas_temporal 
                    (codigo, autores, tema, session_id, convocatoria_id, estado, mensaje_error, fecha_carga) 
                    VALUES 
                    ('$codigo_escaped', '$autores_escaped', '$tema_escaped', '$session_id', $convocatoria_id, '$estado', '$mensaje_escaped', '$fecha')";
    
    if (!$conn->query($queryInsert)) {
        // Si falla el insert, registrar como error
        if ($validacion['valido']) {
            $totalValidos--;
        }
        $totalErrores++;
        $estado = 'error';
        $mensajeError = 'Error al guardar: ' . $conn->error;
    }
    
    // Agregar a lista para mostrar en preview
    $registros[] = array(
        'codigo' => $validacion['codigo'],
        'autores' => $validacion['autores'],
        'tema' => $validacion['tema'],
        'estado' => $estado,
        'mensaje_error' => $mensajeError
    );
}

// Responder con resumen
responderJSON(array(
    'success' => true,
    'mensaje' => 'Archivo procesado correctamente',
    'total' => count($registros),
    'validos' => $totalValidos,
    'errores' => $totalErrores,
    'codigos_invalidos' => $totalCodigoInvalido,
    'registros' => $registros,
    'session_id' => $session_id,
    'convocatoria' => array(
        'id' => $convocatoria_id,
        'codigo_base' => $codigo_base,
        'nombre' => $convocatoria_nombre
    )
));
?>
