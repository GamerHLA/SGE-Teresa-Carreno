var tablenacionalidades = 'nacionalidades';
document.addEventListener('DOMContentLoaded', function() {
    const nacionalidadSelect = document.getElementById('listNacionalidad');
    const cedulaInput = document.getElementById('cedula');
    const cedulaError = document.getElementById('cedula-error');

    // Validación cuando se pierde el foco (click afuera)
    nacionalidadSelect.addEventListener('blur', validarNacionalidad);
    cedulaInput.addEventListener('blur', validarCedula);

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

        // Validar que haya nacionalidad seleccionada primero
        if (!nacionalidad) {
            cedulaInput.classList.add('is-invalid');
            cedulaError.textContent = 'Primero seleccione una nacionalidad';
            return false;
        }

        // Validar que no esté vacía
        if (!cedula) {
            cedulaInput.classList.add('is-invalid');
            cedulaError.textContent = 'Por favor, ingrese el número de cédula';
            return false;
        }

        // Validar longitud (7-10 caracteres)
        if (cedula.length < 7 || cedula.length > 10) {
            cedulaInput.classList.add('is-invalid');
            cedulaError.textContent = 'La cédula debe tener entre 7 y 10 caracteres';
            return false;
        }

        // Si pasa todas las validaciones
        cedulaInput.classList.add('is-valid');
        return true;
    }

    // Función para validar todo el formulario antes de enviar
    window.validarFormulario = function() {
        const nacionalidadValida = validarNacionalidad();
        const cedulaValida = validarCedula();
        
        return nacionalidadValida && cedulaValida;
    };
});