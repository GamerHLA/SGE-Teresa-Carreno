<?php
session_start();

// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sistema_escolar";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$mensaje = "";
$error = "";

// Procesar el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['listNacionalidad']) && isset($_POST['cedula'])) {
    $nacionalidadCodigo = trim($_POST['listNacionalidad']);
    $cedula = trim($_POST['cedula']);
    
    // Validar que sea una opción válida
    $opciones_validas = ['V', 'E', 'P'];
    if (!in_array($nacionalidadCodigo, $opciones_validas)) {
        $error = "Seleccione una opción válida de nacionalidad";
    } elseif (empty($cedula)) {
        $error = "Por favor, ingrese el número de cédula";
    } elseif (strlen($cedula) < 7 || strlen($cedula) > 10) {
        $error = "La cédula debe tener entre 7 y 10 caracteres";
    } elseif (!preg_match('/^[0-9]+$/', $cedula)) {
        $error = "La cédula debe contener solo números";
    } else {
        // Obtener el ID de la nacionalidad desde la base de datos
        $sqlNac = "SELECT id FROM nacionalidades WHERE codigo = ?";
        $stmtNac = $conn->prepare($sqlNac);
        $stmtNac->bind_param("s", $nacionalidadCodigo);
        $stmtNac->execute();
        $resultNac = $stmtNac->get_result();
        
        if ($resultNac->num_rows > 0) {
            $nacionalidadData = $resultNac->fetch_assoc();
            $idNacionalidad = $nacionalidadData['id'];
            
            // Verificar si la cédula ya existe
            $sqlCheck = "SELECT alumno_id FROM alumnos WHERE cedula = ?";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bind_param("s", $cedula);
            $stmtCheck->execute();
            $resultCheck = $stmtCheck->get_result();
            
            if ($resultCheck->num_rows > 0) {
                $error = "La cédula ya está registrada en el sistema";
            } else {
                // Insertar en la base de datos (guardar nacionalidad y cédula en tabla alumnos)
                $sql = "INSERT INTO alumnos (id_nacionalidades, cedula, estatus) VALUES (?, ?, 1)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("is", $idNacionalidad, $cedula);
                
                if ($stmt->execute()) {
                    $mensaje = "Nacionalidad y cédula guardadas correctamente";
                    // Limpiar formulario después de guardar
                    $_POST = array();
                } else {
                    $error = "Error al guardar: " . $stmt->error;
                }
                $stmt->close();
            }
            $stmtCheck->close();
        } else {
            $error = "No se encontró la nacionalidad en la base de datos";
        }
        $stmtNac->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selección de Nacionalidad</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #007bff;
        }

        .form-control.is-invalid {
            border-color: #dc3545;
        }

        .form-control.is-valid {
            border-color: #28a745;
        }

        .invalid-feedback {
            display: none;
            width: 100%;
            margin-top: 5px;
            font-size: 14px;
            color: #dc3545;
        }

        .invalid-feedback.d-block {
            display: block;
        }

        .btn-continuar {
            width: 100%;
            padding: 15px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .btn-continuar:not(:disabled) {
            background: #28a745;
        }

        .btn-continuar:not(:disabled):hover {
            background: #218838;
        }

        .btn-continuar:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }

        .mensaje {
            color: #155724;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .error {
            color: #721c24;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Seleccione su Nacionalidad</h2>
        
        <?php if (!empty($mensaje)): ?>
            <div class="mensaje"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form id="formNacionalidad" method="POST" action="" novalidate>
            <!-- Nacionalidad -->
            <div class="form-group">
                <label for="listNacionalidad">Nacionalidad</label>
                <select class="form-control" name="listNacionalidad" id="listNacionalidad" required>
                    <option value="">Seleccione una opción</option>
                    <option value="V">V - Venezolano</option>
                    <option value="E">E - Extranjero</option>
                    <option value="P">P - Pasaporte</option>
                </select>
                <div class="invalid-feedback">Por favor, seleccione una nacionalidad.</div>
            </div>

            <!-- Cédula -->
            <div class="form-group">
                <label for="cedula">Cédula</label>
                <input type="text" class="form-control" id="cedula" name="cedula" 
                       placeholder="Cédula (7-10 dígitos)" 
                       maxlength="10">
                <div class="invalid-feedback" id="cedula-error"></div>
            </div>
            
            <button type="submit" class="btn-continuar" id="btnContinuar" disabled>
                Guardar
            </button>
        </form>
    </div>
    
    <script>
        // Integrar las validaciones de fuction_nacionalidad.js
        document.addEventListener('DOMContentLoaded', function() {
            const nacionalidadSelect = document.getElementById('listNacionalidad');
            const cedulaInput = document.getElementById('cedula');
            const cedulaError = document.getElementById('cedula-error');
            const btnContinuar = document.getElementById('btnContinuar');
            const formNacionalidad = document.getElementById('formNacionalidad');

            // Validación cuando se pierde el foco (click afuera)
            nacionalidadSelect.addEventListener('blur', validarNacionalidad);
            cedulaInput.addEventListener('blur', validarCedula);
            
            // Validación en tiempo real
            nacionalidadSelect.addEventListener('change', function() {
                validarNacionalidad();
                validarCedula();
                actualizarBoton();
            });
            
            cedulaInput.addEventListener('input', function() {
                validarCedula();
                actualizarBoton();
            });

            // Función para validar nacionalidad
            function validarNacionalidad() {
                const valor = nacionalidadSelect.value;
                
                // Resetear clases
                nacionalidadSelect.classList.remove('is-invalid', 'is-valid');
                
                if (valor === '') {
                    nacionalidadSelect.classList.add('is-invalid');
                    return false;
                } else {
                    nacionalidadSelect.classList.add('is-valid');
                    return true;
                }
            }

            // Función para validar cédula
            function validarCedula() {
                const nacionalidad = nacionalidadSelect.value;
                const cedula = cedulaInput.value.trim();
                
                // Resetear estados
                cedulaInput.classList.remove('is-invalid', 'is-valid');
                cedulaError.textContent = '';
                cedulaError.classList.remove('d-block');

                // Validar que haya nacionalidad seleccionada primero
                if (!nacionalidad) {
                    cedulaInput.classList.add('is-invalid');
                    cedulaError.textContent = 'Primero seleccione una nacionalidad';
                    cedulaError.classList.add('d-block');
                    return false;
                }

                // Validar que no esté vacía
                if (!cedula) {
                    cedulaInput.classList.add('is-invalid');
                    cedulaError.textContent = 'Por favor, ingrese el número de cédula';
                    cedulaError.classList.add('d-block');
                    return false;
                }

                // Validar longitud (7-10 caracteres)
                if (cedula.length < 7 || cedula.length > 10) {
                    cedulaInput.classList.add('is-invalid');
                    cedulaError.textContent = 'La cédula debe tener entre 7 y 10 caracteres';
                    cedulaError.classList.add('d-block');
                    return false;
                }

                // Validar que sean solo números
                if (!/^[0-9]+$/.test(cedula)) {
                    cedulaInput.classList.add('is-invalid');
                    cedulaError.textContent = 'La cédula debe contener solo números';
                    cedulaError.classList.add('d-block');
                    return false;
                }

                // Si pasa todas las validaciones
                cedulaInput.classList.add('is-valid');
                return true;
            }

            // Función para actualizar el estado del botón
            function actualizarBoton() {
                const nacionalidadValida = validarNacionalidad();
                const cedulaValida = validarCedula();
                btnContinuar.disabled = !(nacionalidadValida && cedulaValida);
            }

            // Función para validar todo el formulario antes de enviar
            formNacionalidad.addEventListener('submit', function(e) {
                const nacionalidadValida = validarNacionalidad();
                const cedulaValida = validarCedula();
                
                if (!nacionalidadValida || !cedulaValida) {
                    e.preventDefault();
                    // Forzar mostrar errores
                    if (!nacionalidadValida) {
                        nacionalidadSelect.classList.add('is-invalid');
                    }
                    if (!cedulaValida) {
                        cedulaInput.classList.add('is-invalid');
                        if (cedulaError.textContent) {
                            cedulaError.classList.add('d-block');
                        }
                    }
                    return false;
                }
            });
        });
    </script>
</body>
</html>
