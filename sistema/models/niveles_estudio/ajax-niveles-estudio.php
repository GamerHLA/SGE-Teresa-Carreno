<?php
require_once '../../includes/config.php';

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => false, 'msg' => 'Método no permitido']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'getCategorias':
            getCategorias($pdo);
            break;

        case 'getEspecializaciones':
            $categoria = $_POST['categoria'] ?? '';
            getEspecializaciones($pdo, $categoria);
            break;

        case 'addNivel':
            addNivel($pdo, $_POST);
            break;

        case 'addEspecializacion':
            addEspecializacion($pdo, $_POST);
            break;

        case 'addCategoria':
            addCategoria($pdo, $_POST);
            break;

        case 'deleteCategoria':
            deleteCategoria($pdo, $_POST);
            break;

        case 'deleteEspecializacion':
            deleteEspecializacion($pdo, $_POST);
            break;

        case 'getNivelesParaPosicion':
            getNivelesParaPosicion($pdo);
            break;

        case 'getProfesorNiveles':
            $profesorId = $_POST['profesor_id'] ?? 0;
            getProfesorNiveles($pdo, $profesorId);
            break;

        case 'addProfesorNivel':
            addProfesorNivel($pdo, $_POST);
            break;

        case 'deleteProfesorNivel':
            deleteProfesorNivel($pdo, $_POST);
            break;

        case 'reactivateCategoria':
            reactivateCategoria($pdo, $_POST);
            break;

        case 'reactivateEspecializacion':
            reactivateEspecializacion($pdo, $_POST);
            break;

        default:
            echo json_encode(['status' => false, 'msg' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    error_log("Error en ajax-niveles-estudio.php: " . $e->getMessage());
    echo json_encode(['status' => false, 'msg' => 'Error del sistema: ' . $e->getMessage()]);
}


/**
 * Obtener lista de categorías únicas en orden específico
 */
function getCategorias($pdo)
{
    // Usar la tabla maestra directamente
    $sql = "SELECT nombre as categoria 
            FROM niveles_estudio 
            WHERE COALESCE(estatus, 1) = 1
            ORDER BY orden ASC";

    $query = $pdo->prepare($sql);
    $query->execute();
    $categoriasOrdenadas = $query->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode(['status' => true, 'data' => $categoriasOrdenadas]);
}

/**
 * Obtener especializaciones por categoría
 */
function getEspecializaciones($pdo, $categoria)
{
    if (empty($categoria)) {
        echo json_encode(['status' => false, 'msg' => 'Categoría requerida']);
        return;
    }

    // Unir especialidades con su categoría (nivel_estudio)
    $sql = "SELECT ee.id, ee.nombre as nivel_estudio, ne.nombre as categoria, ee.orden 
            FROM especialidades_estudio ee
            JOIN niveles_estudio ne ON ee.nivel_id = ne.id
            WHERE ne.nombre = ? AND COALESCE(ne.estatus, 1) = 1 
            AND COALESCE(ee.estatus, 1) = 1
            AND ee.nombre != '__DEFAULT__'
            ORDER BY ee.nombre ASC";
    $query = $pdo->prepare($sql);
    $query->execute([$categoria]);
    $especializaciones = $query->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => true, 'data' => $especializaciones]);
}

/**
 * Agregar nueva categoría (Nivel de Estudio)
 */
function addCategoria($pdo, $data)
{
    $categoria = trim($data['categoria'] ?? '');
    $posicionRaw = $data['posicion'] ?? 'final';

    if (empty($categoria)) {
        echo json_encode(['status' => false, 'msg' => 'El nombre de la categoría es obligatorio']);
        return;
    }

    // Check if exists
    $sqlCheck = "SELECT id FROM niveles_estudio WHERE nombre = ?";
    $queryCheck = $pdo->prepare($sqlCheck);
    $queryCheck->execute([$categoria]);
    if ($queryCheck->fetch()) {
        echo json_encode(['status' => false, 'msg' => 'Esta categoría ya existe']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Obtener el siguiente orden disponible
        $sqlMax = "SELECT COALESCE(MAX(orden), 0) + 1 FROM niveles_estudio";
        $orden = $pdo->query($sqlMax)->fetchColumn();

        $sqlInsert = "INSERT INTO niveles_estudio (nombre, estatus, orden) VALUES (?, 1, ?)";
        $queryInsert = $pdo->prepare($sqlInsert);
        $queryInsert->execute([$categoria, $orden]);
        $newId = $pdo->lastInsertId();

        // Agregar una especialidad default para que la categoría sea visible y funcional
        $sqlDef = "INSERT INTO especialidades_estudio (nivel_id, nombre, orden, estatus) VALUES (?, '__DEFAULT__', 1, 1)";
        $pdo->prepare($sqlDef)->execute([$newId]);

        $pdo->commit();

        echo json_encode([
            'status' => true,
            'msg' => 'Categoría agregada correctamente',
            'id' => $newId
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => false, 'msg' => 'Error al agregar la categoría: ' . $e->getMessage()]);
    }
}


/**
 * Agregar nuevo nivel de estudio (Especialidad)
 */
function addNivel($pdo, $data)
{
    $nivelEstudio = trim($data['nivel_estudio'] ?? '');
    $categoria = trim($data['categoria'] ?? '');

    if (empty($nivelEstudio) || empty($categoria)) {
        echo json_encode(['status' => false, 'msg' => 'Nombre y categoría obligatorios']);
        return;
    }

    // Buscar ID de categoría
    $sqlCat = "SELECT id FROM niveles_estudio WHERE nombre = ?";
    $qCat = $pdo->prepare($sqlCat);
    $qCat->execute([$categoria]);
    $categoriaId = $qCat->fetchColumn();

    if (!$categoriaId) {
        echo json_encode(['status' => false, 'msg' => 'Categoría no encontrada']);
        return;
    }

    // Verificar si ya existe en esta categoría
    $sqlCheck = "SELECT id FROM especialidades_estudio WHERE nombre = ? AND nivel_id = ?";
    $queryCheck = $pdo->prepare($sqlCheck);
    $queryCheck->execute([$nivelEstudio, $categoriaId]);
    if ($queryCheck->fetch()) {
        echo json_encode(['status' => false, 'msg' => 'Este nivel de estudio ya existe en esta categoría']);
        return;
    }

    try {
        $sqlMax = "SELECT COALESCE(MAX(orden), 0) + 1 FROM especialidades_estudio WHERE nivel_id = ?";
        $qMax = $pdo->prepare($sqlMax);
        $qMax->execute([$categoriaId]);
        $orden = $qMax->fetchColumn();

        $sqlInsert = "INSERT INTO especialidades_estudio (nivel_id, nombre, orden, estatus) VALUES (?, ?, ?, 1)";
        $queryInsert = $pdo->prepare($sqlInsert);
        $queryInsert->execute([$categoriaId, $nivelEstudio, $orden]);

        echo json_encode([
            'status' => true,
            'msg' => 'Nivel de estudio agregado correctamente',
            'id' => $pdo->lastInsertId()
        ]);

    } catch (Exception $e) {
        echo json_encode(['status' => false, 'msg' => 'Error al agregar el nivel: ' . $e->getMessage()]);
    }
}

/**
 * Agregar nueva especialización (es lo mismo que addNivel en este contexto)
 */
function addEspecializacion($pdo, $data)
{
    return addNivel($pdo, $data);
}


/**
 * Obtener todos los niveles para el selector de posición
 */
function getNivelesParaPosicion($pdo)
{
    $sql = "SELECT id, nombre as categoria, orden 
            FROM niveles_estudio 
            WHERE COALESCE(estatus, 1) = 1
            ORDER BY orden ASC";
    $query = $pdo->prepare($sql);
    $query->execute();
    $niveles = $query->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => true, 'data' => $niveles]);
}

/**
 * Obtener todos los niveles de educación de un profesor
 */
function getProfesorNiveles($pdo, $profesorId)
{
    if (empty($profesorId)) {
        echo json_encode(['status' => false, 'msg' => 'ID de profesor requerido']);
        return;
    }

    $sql = "SELECT 
                pne.id,
                pne.especializacion_id as nivel_estudio_id,
                ee.nombre as nivel_estudio,
                ne.nombre as categoria
            FROM profesor_niveles_estudio pne
            JOIN especialidades_estudio ee ON pne.especializacion_id = ee.id
            JOIN niveles_estudio ne ON ee.nivel_id = ne.id
            WHERE pne.profesor_id = ? 
            AND COALESCE(ne.estatus, 1) = 1 
            AND COALESCE(ee.estatus, 1) = 1
            ORDER BY ne.orden, ee.nombre";

    $query = $pdo->prepare($sql);
    $query->execute([$profesorId]);
    $niveles = $query->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => true, 'data' => $niveles]);
}

/**
 * Agregar nivel de educación a un profesor
 */
function addProfesorNivel($pdo, $data)
{
    $profesorId = intval($data['profesor_id'] ?? 0);
    $especializacionId = intval($data['nivel_estudio_id'] ?? 0);

    if (empty($profesorId) || empty($especializacionId)) {
        echo json_encode(['status' => false, 'msg' => 'Datos incompletos']);
        return;
    }

    // Verificar si ya existe esta combinación
    $sqlCheck = "SELECT id FROM profesor_niveles_estudio WHERE profesor_id = ? AND especializacion_id = ?";
    $queryCheck = $pdo->prepare($sqlCheck);
    $queryCheck->execute([$profesorId, $especializacionId]);

    if ($queryCheck->fetch()) {
        echo json_encode(['status' => false, 'msg' => 'Este nivel ya está agregado al profesor']);
        return;
    }

    $sql = "INSERT INTO profesor_niveles_estudio (profesor_id, especializacion_id) VALUES (?, ?)";
    $query = $pdo->prepare($sql);

    if ($query->execute([$profesorId, $especializacionId])) {
        echo json_encode([
            'status' => true,
            'msg' => 'Nivel agregado correctamente',
            'id' => $pdo->lastInsertId()
        ]);
    } else {
        echo json_encode(['status' => false, 'msg' => 'Error al agregar el nivel']);
    }
}

/**
 * Eliminar nivel de educación de un profesor
 */
function deleteProfesorNivel($pdo, $data)
{
    $id = intval($data['id'] ?? 0);

    if (empty($id)) {
        echo json_encode(['status' => false, 'msg' => 'ID requerido']);
        return;
    }

    $sql = "DELETE FROM profesor_niveles_estudio WHERE id = ?";
    $query = $pdo->prepare($sql);

    if ($query->execute([$id])) {
        echo json_encode(['status' => true, 'msg' => 'Nivel eliminado correctamente']);
    } else {
        echo json_encode(['status' => false, 'msg' => 'Error al eliminar el nivel']);
    }
}

/**
 * Eliminar categoría (Nivel de Estudio)
 */
function deleteCategoria($pdo, $data)
{
    $categoria = trim($data['categoria'] ?? '');

    if (empty($categoria)) {
        echo json_encode(['status' => false, 'msg' => 'Nombre de categoría requerido']);
        return;
    }

    // Buscar ID
    $sqlId = "SELECT id FROM niveles_estudio WHERE nombre = ?";
    $qId = $pdo->prepare($sqlId);
    $qId->execute([$categoria]);
    $id = $qId->fetchColumn();

    if (!$id) {
        echo json_encode(['status' => false, 'msg' => 'Categoría no encontrada']);
        return;
    }

    try {
        // Soft delete: cambiar estatus a 0
        $sql = "UPDATE niveles_estudio SET estatus = 0 WHERE id = ?";
        $pdo->prepare($sql)->execute([$id]);
        echo json_encode(['status' => true, 'msg' => 'Categoría deshabilitada correctamente']);
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'msg' => 'Error al deshabilitar la categoría: ' . $e->getMessage()]);
    }
}

/**
 * Eliminar especialización específica (Nivel/Especialidad)
 */
function deleteEspecializacion($pdo, $data)
{
    $id = intval($data['id'] ?? 0);

    if (empty($id)) {
        echo json_encode(['status' => false, 'msg' => 'ID requerido']);
        return;
    }

    // Soft delete: cambiar estatus a 0
    try {
        $sql = "UPDATE especialidades_estudio SET estatus = 0 WHERE id = ?";
        $query = $pdo->prepare($sql);

        if ($query->execute([$id])) {
            echo json_encode(['status' => true, 'msg' => 'Especialización deshabilitada correctamente']);
        } else {
            echo json_encode(['status' => false, 'msg' => 'Error al deshabilitar la especialización']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'msg' => 'Error: ' . $e->getMessage()]);
    }
}

/**
 * Reactivar categoría (Nivel de Estudio)
 */
function reactivateCategoria($pdo, $data)
{
    $categoria = trim($data['categoria'] ?? '');
    if (empty($categoria)) {
        echo json_encode(['status' => false, 'msg' => 'Nombre de categoría requerido']);
        return;
    }
    try {
        $sql = "UPDATE niveles_estudio SET estatus = 1 WHERE nombre = ?";
        $pdo->prepare($sql)->execute([$categoria]);
        echo json_encode(['status' => true, 'msg' => 'Categoría reactivada correctamente']);
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'msg' => 'Error al reactivar la categoría: ' . $e->getMessage()]);
    }
}

/**
 * Reactivar especialización específica
 */
function reactivateEspecializacion($pdo, $data)
{
    $id = intval($data['id'] ?? 0);
    if (empty($id)) {
        echo json_encode(['status' => false, 'msg' => 'ID requerido']);
        return;
    }
    try {
        $sql = "UPDATE especialidades_estudio SET estatus = 1 WHERE id = ?";
        $pdo->prepare($sql)->execute([$id]);
        echo json_encode(['status' => true, 'msg' => 'Especialización reactivada correctamente']);
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'msg' => 'Error: ' . $e->getMessage()]);
    }
}
