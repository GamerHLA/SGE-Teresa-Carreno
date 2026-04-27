<?php
/**
 * BUSCAR_CEDULA.PHP
 * =================
 * 
 * Script de búsqueda global de cédula en el sistema escolar.
 * Busca una cédula en todas las tablas del sistema y muestra resultados.
 * 
 * FUNCIONALIDADES:
 * - Búsqueda en tabla de alumnos
 * - Búsqueda en tabla de representantes
 * - Búsqueda en tabla de profesores
 * - Búsqueda en tabla de usuarios (por nombre/usuario)
 * - Muestra resultados organizados por categoría con tarjetas Bootstrap
 * - Contador total de resultados encontrados
 * - Botones para nueva búsqueda o limpiar resultados
 * 
 * PARÁMETROS:
 * - GET['cedula']: Cédula a buscar
 * 
 * VISUALIZACIÓN:
 * - Alertas informativas con contadores
 * - Tarjetas con colores diferenciados por tipo
 * - Información detallada de cada registro encontrado
 * 
 * DEPENDENCIAS:
 * - includes/config.php (conexión PDO)
 * - Sesión activa
 */
session_start();
if (empty($_SESSION['active'])) {
    header("Location: ../");
    exit;
}

// SI config.php está en includes:
require_once 'includes/config.php';

// O si está en models:
// require_once 'models/config.php';

// Resto del código igual...

// Mostrar errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isset($_GET['cedula']) && !empty($_GET['cedula'])) {
    $cedula = trim($_GET['cedula']);

    echo "<div class='alert alert-info'>Buscando cédula: " . htmlspecialchars($cedula) . "</div>";

    // Buscar en todas las tablas
    $resultados = buscarEnTodasLasTablas($pdo, $cedula);
    mostrarResultados($resultados, $cedula);
} else {
    echo "<div class='alert alert-danger'>No se recibió la cédula para buscar</div>";
}

function buscarEnTodasLasTablas($pdo, $cedula)
{
    $resultados = [];

    try {
        // Buscar en alumnos
        $sql = "SELECT * FROM alumnos WHERE cedula = ? AND estatus = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$cedula]);
        $resultados['alumnos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<div class='alert alert-secondary'>Alumnos encontrados: " . count($resultados['alumnos']) . "</div>";

        // Buscar en representantes
        $sql = "SELECT * FROM representantes WHERE cedula = ? AND estatus = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$cedula]);
        $resultados['representantes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<div class='alert alert-secondary'>Representantes encontrados: " . count($resultados['representantes']) . "</div>";

        // Buscar en profesores
        $sql = "SELECT * FROM profesor WHERE cedula = ? AND estatus = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$cedula]);
        $resultados['profesores'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<div class='alert alert-secondary'>Profesores encontrados: " . count($resultados['profesores']) . "</div>";

        // Buscar en usuarios (buscando por nombre que contenga la cédula)
        $sql = "SELECT u.*, r.nombre_rol FROM usuarios u 
                LEFT JOIN rol r ON u.rol = r.rol_id 
                WHERE (u.nombre LIKE ? OR u.usuario LIKE ?) AND u.estatus = 1";
        $stmt = $pdo->prepare($sql);
        $busqueda = "%$cedula%";
        $stmt->execute([$busqueda, $busqueda]);
        $resultados['usuarios'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<div class='alert alert-secondary'>Usuarios encontrados: " . count($resultados['usuarios']) . "</div>";
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Error en la consulta: " . $e->getMessage() . "</div>";
    }

    return $resultados;
}

function mostrarResultados($resultados, $cedula)
{
    $totalResultados = count($resultados['alumnos']) + count($resultados['representantes']) +
        count($resultados['profesores']) + count($resultados['usuarios']);

    echo "<div class='alert alert-warning'>Total de registros: " . $totalResultados . "</div>";

    if ($totalResultados === 0) {
        echo '<div class="alert alert-warning">No se encontraron resultados para la cédula: <strong>' . htmlspecialchars($cedula) . '</strong></div>';
        return;
    }

    // Resto del código para mostrar resultados...
    echo '<div class="alert alert-success mb-4">';
    echo '<h4><i class="fas fa-info-circle"></i> Resultados para la cédula: ' . htmlspecialchars($cedula) . '</h4>';
    echo '<p>Total de registros encontrados: <strong>' . $totalResultados . '</strong></p>';
    echo '</div>';

    // Mostrar alumnos
    if (!empty($resultados['alumnos'])) {
        echo '<div class="card mb-4">';
        echo '<div class="card-header bg-primary text-white">';
        echo '<h5><i class="fas fa-user-graduate"></i> Alumnos</h5>';
        echo '</div>';
        echo '<div class="card-body">';
        foreach ($resultados['alumnos'] as $alumno) {
            echo '<div class="row mb-3">';
            echo '<div class="col-md-6"><strong>Nombre:</strong> ' . htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellido']) . '</div>';
            echo '<div class="col-md-6"><strong>Edad:</strong> ' . htmlspecialchars($alumno['edad']) . '</div>';
            echo '<div class="col-md-6"><strong>Teléfono:</strong> ' . htmlspecialchars($alumno['telefono']) . '</div>';
            echo '<div class="col-md-6"><strong>Correo:</strong> ' . htmlspecialchars($alumno['correo']) . '</div>';
            echo '<div class="col-md-12"><strong>Dirección:</strong> ' . htmlspecialchars($alumno['direccion']) . '</div>';
            echo '<div class="col-md-6"><strong>Fecha Nac.:</strong> ' . htmlspecialchars($alumno['fecha_nac']) . '</div>';
            echo '</div>';
        }
        echo '</div></div>';
    }

    // Mostrar representantes
    if (!empty($resultados['representantes'])) {
        echo '<div class="card mb-4">';
        echo '<div class="card-header bg-success text-white">';
        echo '<h5><i class="fas fa-users"></i> Representantes</h5>';
        echo '</div>';
        echo '<div class="card-body">';
        foreach ($resultados['representantes'] as $representante) {
            echo '<div class="row mb-3">';
            echo '<div class="col-md-6"><strong>Nombre:</strong> ' . htmlspecialchars($representante['nombre'] . ' ' . $representante['apellido']) . '</div>';
            echo '<div class="col-md-6"><strong>Edad:</strong> ' . htmlspecialchars($representante['edad']) . '</div>';
            echo '<div class="col-md-6"><strong>Teléfono:</strong> ' . htmlspecialchars($representante['telefono']) . '</div>';
            echo '<div class="col-md-6"><strong>Correo:</strong> ' . htmlspecialchars($representante['correo']) . '</div>';
            echo '<div class="col-md-12"><strong>Dirección:</strong> ' . htmlspecialchars($representante['direccion']) . '</div>';
            echo '<div class="col-md-6"><strong>Fecha Nac.:</strong> ' . htmlspecialchars($representante['fecha_nac']) . '</div>';
            echo '</div>';
        }
        echo '</div></div>';
    }

    // Mostrar profesores
    if (!empty($resultados['profesores'])) {
        echo '<div class="card mb-4">';
        echo '<div class="card-header bg-warning text-dark">';
        echo '<h5><i class="fas fa-chalkboard-teacher"></i> Profesores</h5>';
        echo '</div>';
        echo '<div class="card-body">';
        foreach ($resultados['profesores'] as $profesor) {
            echo '<div class="row mb-3">';
            echo '<div class="col-md-6"><strong>Nombre:</strong> ' . htmlspecialchars($profesor['nombre'] . ' ' . $profesor['apellido']) . '</div>';
            echo '<div class="col-md-6"><strong>Nivel Estudio:</strong> ' . htmlspecialchars($profesor['nivel_est']) . '</div>';
            echo '<div class="col-md-6"><strong>Teléfono:</strong> ' . htmlspecialchars($profesor['telefono']) . '</div>';
            echo '<div class="col-md-6"><strong>Correo:</strong> ' . htmlspecialchars($profesor['correo']) . '</div>';
            echo '<div class="col-md-12"><strong>Dirección:</strong> ' . htmlspecialchars($profesor['direccion']) . '</div>';
            echo '</div>';
        }
        echo '</div></div>';
    }

    // Mostrar usuarios
    if (!empty($resultados['usuarios'])) {
        echo '<div class="card mb-4">';
        echo '<div class="card-header bg-info text-white">';
        echo '<h5><i class="fas fa-user-cog"></i> Usuarios del Sistema</h5>';
        echo '</div>';
        echo '<div class="card-body">';
        foreach ($resultados['usuarios'] as $usuario) {
            echo '<div class="row mb-3">';
            echo '<div class="col-md-6"><strong>Nombre:</strong> ' . htmlspecialchars($usuario['nombre']) . '</div>';
            echo '<div class="col-md-6"><strong>Usuario:</strong> ' . htmlspecialchars($usuario['usuario']) . '</div>';
            echo '<div class="col-md-6"><strong>Rol:</strong> ' . htmlspecialchars($usuario['nombre_rol']) . '</div>';
            echo '<div class="col-md-6"><strong>Estatus:</strong> ' . ($usuario['estatus'] == 1 ? 'Activo' : 'Inactivo') . '</div>';
            echo '</div>';
        }
        echo '</div></div>';
    }

    // Botón para nueva búsqueda
    echo '<div class="text-center mt-4">';
    echo '<button class="btn btn-primary mr-2" onclick="openModalConsulta()">';
    echo '<i class="fas fa-search"></i> Nueva Búsqueda';
    echo '</button>';
    echo '<button class="btn btn-secondary" onclick="limpiarBusqueda()">';
    echo '<i class="fas fa-times"></i> Limpiar';
    echo '</button>';
    echo '</div>';
}