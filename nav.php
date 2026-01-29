<?php
/**
 * nav.php
 * Navbar unificado para todo el sistema
 * Incluir después de obtener $usuario con obtenerUsuarioActual()
 *
 * Uso: <?php $pagina_actual = 'index'; include 'nav.php'; ?>
 *
 * Páginas válidas: index, listado, convocatorias, admin-usuarios
 */

// Detectar página actual si no está definida
if (!isset($pagina_actual)) {
    $pagina_actual = basename($_SERVER['PHP_SELF'], '.php');
}

// Obtener nombre de usuario (compatibilidad con diferentes variables)
$nombre_usuario = '';
if (isset($usuario['nombre'])) {
    $nombre_usuario = $usuario['nombre'];
} elseif (isset($_SESSION['usuario_nombre'])) {
    $nombre_usuario = $_SESSION['usuario_nombre'];
}

// Verificar si es admin
$es_admin = false;
if (isset($usuario['rol'])) {
    $es_admin = ($usuario['rol'] === 'admin');
} elseif (isset($_SESSION['usuario_rol'])) {
    $es_admin = ($_SESSION['usuario_rol'] === 'admin');
}
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-institucional">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="assets/img/logo.svg" alt="Logo" height="40" class="me-2">
            <span>Sistema de Diplomas</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link<?php echo $pagina_actual === 'index' ? ' active' : ''; ?>" href="index.php">
                        <i class="bi bi-cloud-upload me-1"></i> Cargar
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?php echo $pagina_actual === 'listado' ? ' active' : ''; ?>" href="listado.php">
                        <i class="bi bi-list-ul me-1"></i> Listado
                    </a>
                </li>
                <?php if ($es_admin): ?>
                <li class="nav-item">
                    <a class="nav-link<?php echo in_array($pagina_actual, ['convocatorias', 'convocatoria-form', 'carga-convocatorias']) ? ' active' : ''; ?>" href="convocatorias.php">
                        <i class="bi bi-folder me-1"></i> Convocatorias
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?php echo $pagina_actual === 'admin-usuarios' ? ' active' : ''; ?>" href="admin-usuarios.php">
                        <i class="bi bi-people me-1"></i> Usuarios
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="validador.php" target="_blank">
                        <i class="bi bi-patch-check me-1"></i> Validador
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>
                        <?php echo htmlspecialchars($nombre_usuario); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="cambiar-password.php"><i class="bi bi-key me-2"></i>Cambiar Contraseña</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
