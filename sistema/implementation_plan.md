# Plan de Refactorización CRUD para Esquema 'Personas' y 'Direccion'

Se ha implementado un nuevo esquema de base de datos donde la información personal (nombres, apellidos, cédula, etc.) y de contacto/dirección se ha abstraído en las tablas `personas` y `direccion` respectivamente. Sin embargo, el código backend (PHP/PDO) sigue intentando insertar y consultar estos datos directamente en las tablas `alumnos`, `profesores`, `representantes` y `usuarios`, lo que causa que el sistema falle.

Este plan detalla cómo se refactorizarán las consultas en los archivos correspondientes para que funcionen con el nuevo esquema.

## User Review Required

> [!WARNING]
> **Generación de IDs (Importante)**
> Revisando la base de datos, he notado que las columnas como `id_persona`, `id_direccion`, `id_alumnos`, etc., son `integer NOT NULL` pero **no tienen secuencias automáticas (serial/auto-increment)**.
> Por lo tanto, el código PHP tendrá que calcular el próximo ID disponible usando `SELECT COALESCE(MAX(id_columna), 0) + 1` en cada inserción. Si prefieres que altere la base de datos para usar secuencias (lo cual es más seguro para concurrencia), por favor házmelo saber. De lo contrario, procederé con el cálculo manual por PHP.

> [!IMPORTANT]
> **Relación 1:1 circular**
> Noté que `alumnos.id_alumnos` apunta a `personas.id_persona` (es decir, comparten el mismo ID) y, a su vez, `personas` tiene una columna `alumnos_id` que apunta a `alumnos`.
> El orden de inserción será:
> 1. Insertar en `direccion` (generando un `id_direccion`).
> 2. Insertar en `personas` (generando un `id_persona`) usando el `id_direccion`.
> 3. Insertar en `alumnos` usando `id_alumnos = id_persona`.
> 4. Actualizar `personas` seteando `alumnos_id = id_persona`.
>
> Este mismo flujo se aplicará a Profesores y Representantes.

## Proposed Changes

### Alumnos
Se actualizarán los archivos de procesamiento y consulta de alumnos.

#### [MODIFY] ajax-alumnos.php
- **Inserción**:
  - Obtener el nuevo `id_direccion`. Insertar datos en `direccion`.
  - Obtener el nuevo `id_persona`. Insertar datos personales y de contacto en `personas` (vinculando `id_direccion`).
  - Insertar en `alumnos` usando el `id_persona` como su `id_alumnos`.
  - Actualizar el `alumnos_id` en `personas`.
- **Actualización**:
  - Actualizar `personas` (nombre, apellido, cédula, etc.).
  - Actualizar `direccion` asociada a esa persona (estado, municipio, etc.).
  - Actualizar `alumnos` (si corresponde a otros campos como actividad extra, etc., aunque actualmente no se envían desde este formulario, se dejará la estructura lista o se omitirá si no se requiere).

#### [MODIFY] table_alumnos.php
- Modificar el `SELECT` para unir `alumnos` con `personas` (`ON a.id_alumnos = p.id_persona`) y con `direccion` (`ON p.id_direccion = d.id_direccion`).
- Traer los campos de ubicación haciendo JOIN de `direccion` con `estados`, `municipios`, `ciudades` y `parroquias`.

---

### Profesores
#### [MODIFY] ajax-profesores.php
- **Inserción**:
  - Similar a alumnos: Insertar `direccion`, luego `personas`, luego `profesores` (usando `id_persona` como `profesor_id`).
- **Actualización**:
  - Actualizar datos en `personas`, `direccion` y finalmente en `profesores` (estado de director, fechas, etc.).

#### [MODIFY] table_profesores.php
- Revisión y ajuste final del `JOIN`. Actualmente ya tiene un `LEFT JOIN personas`, pero falta vincular la tabla `direccion` correctamente para obtener la ubicación, ya que los campos `id_estado`, etc., ahora están en `direccion`, no en `personas` o `profesores`.

---

### Representantes
#### [MODIFY] ajax-representantes.php
- Refactorizar toda la lógica de Inserción/Actualización para que maneje las tablas `direccion`, `personas` y luego `representantes` (usando el `id_persona` como `id_representates`), exactamente igual que para Profesores y Alumnos.

#### [MODIFY] table_representantes.php
- Agregar el JOIN a `personas` y `direccion` para poder extraer el nombre, apellido, cédula y la ubicación, ya que el código actual asume que todo está en la tabla `representantes`.

---

### Usuarios
#### [MODIFY] ajax-usuarios.php
- Ajustar la lógica de creación. Como a este punto un usuario ya debe tener un "profesor" o "representante" asociado (y por ende una `persona` ya existe), nos aseguraremos de que solo se vincule el `id_persona` correcto.

## Verification Plan

### Manual Verification
- Iniciar el servidor local y acceder a la interfaz web.
- Probar el registro de un nuevo Representante y verificar que se insertan correctamente los registros en `direccion`, `personas` y `representantes`.
- Probar el registro de un nuevo Alumno (asignando el representante anterior).
- Probar el registro de un nuevo Profesor.
- Probar la edición de datos personales (nombre, cédula, estado) y verificar que las actualizaciones ocurren en las tablas `personas` y `direccion` correctamente sin afectar los identificadores principales.
- Asegurar que las listas en las tablas carguen correctamente todos los datos y no muestren errores de SQL o campos vacíos.
