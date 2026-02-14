# Sistema de Reportes de Conducta

## Descripción General
Sistema para gestionar reportes de conducta de estudiantes con generación de PDFs bajo demanda.

## Características

### Botón de Reportes
- Ubicación: Columna "Acciones" en lista de estudiantes
- Icono: Clipboard color cyan
- Funcionalidad: Ver reportes existentes o crear nuevos

### Sistema de Modales

**Modal Principal (Ver Reportes)**
- Muestra tabla con todos los reportes del alumno
- Si no hay reportes: botón para crear nuevo
- Columnas: Fecha, Tipo, Docente, Acciones (Ver PDF, Ver Detalles)

**Modal de Creación**
- Campos: Fecha, Tipo, Descripción, Observaciones
- Validación: Campos obligatorios excepto Observaciones

### Generación de PDFs
- Los PDFs se generan bajo demanda (no se guardan en disco)
- Funcionamiento igual a las boletas individuales
- Se abren en el navegador para ver/descargar
- Contenido: Información del alumno, detalles del reporte, docente que reporta

## Estructura de Base de Datos

Tu tabla `conductReports` debe tener:
- `idConductReport` - ID principal
- `idStudent` - Relación con estudiante
- `idTeacher` - Relación con docente  
- `date_` - Fecha del reporte
- `actionTaken` - Tipo de reporte
- `description` - Descripción  
- `feedback` - Observaciones

## Archivos del Sistema

### PHP
- `teachers/list.php` - Lista de estudiantes con botón de reporte
- `teachers/get_student_report.php` - Obtiene reportes de un estudiante
- `teachers/save_student_report.php` - Guarda nuevo reporte en BD
- `teachers/generate_report_pdf.php` - Genera PDF bajo demanda

### CSS
- `css/teacher/list.css` - Estilos del botón de reporte

### SQL
- `INSTALAR_SESION_UNICA.sql` - Script de instalación de la base de datos

## Flujo de Trabajo

**Crear Reporte:**
1. Clic en botón de reporte
2. Verificación de reportes existentes
3. Si no existe: crear nuevo con formulario
4. Guardar en base de datos

**Ver Reportes:**
1. Clic en botón de reporte
2. Tabla con todos los reportes
3. Ver PDF: genera y abre en navegador
4. Ver Detalles: muestra info completa en modal

## Funciones JavaScript

- `checkStudentReport(studentId)` - Obtiene y muestra reportes
- `viewReportPDF(idConductReport)` - Abre PDF en nueva pestaña
- `viewReportDetails(idConductReport)` - Muestra detalles en SweetAlert

## Seguridad
- Validación de sesión en todos los endpoints
- Prepared statements en todas las consultas SQL
- Validación de entrada en frontend y backend
- Solo docentes autenticados pueden crear/ver reportes

## Archivos Modificados/Creados

### Archivos PHP

1. **[teachers/list.php](teachers/list.php)**
   - Agregado: Columna "Reporte" en tabla de estudiantes
   - Agregado: Botón con clase `.botonReporte`
   - Agregado: Dos modales (reportModal, createReportModal)
   - Modificado: JavaScript `cargarAlumnos()` para incluir columna de reportes
   - Agregado: Funciones JavaScript para manejo de reportes

2. **[teachers/get_student_report.php](teachers/get_student_report.php)** (NUEVO)
   - Función: Obtener todos los reportes de un estudiante
   - Entrada: `?studentId={id}` (GET)
   - Salida JSON:
     ```json
     {
       "success": true,
       "hasReport": true,
       "count": 2,
       "reports": [
         {
           "idConductReport": 1,
           "fecha": "2025-02-10",
           "tipo": "Disciplinario",
           "descripcion": "...",
           "observaciones": "...",
           "teacherFullName": "Juan Pérez García",
           "pdfPath": "reporte_1234_1_20250210.pdf",
           "createdAt": "2025-02-10 14:30:00"
         }
       ]
     }
     ```

3. **[teachers/save_student_report.php](teachers/save_student_report.php)** (NUEVO)
   - Función: Guardar nuevo reporte y generar PDF automáticamente
   - Entrada: POST con datos del formulario
   - Proceso:
     1. Validar datos
     2. Insertar en base de datos
     3. Generar PDF
     4. Actualizar campo `pdfPath`
   - Salida JSON con confirmación

4. **[teachers/generate_report_pdf.php](teachers/generate_report_pdf.php)** (NUEVO)
   - Función: Generar PDF del reporte
   - Librería: FPDF
   - Función principal: `generateReportPDF($idConductReport, $conexion, $saveToFile, $outputPath)`
   - Parámetros:
     - `$idConductReport`: ID del reporte
     - `$conexion`: Conexión a la base de datos
     - `$saveToFile`: true para guardar, false para mostrar en navegador
     - `$outputPath`: Ruta donde guardar el archivo (si $saveToFile = true)

### Archivos CSS

**[css/teacher/list.css](css/teacher/list.css)**
- Agregado: Estilos para `.botonReporte`
- Características: Botón transparente, icono cyan 2rem, sin bordes

### Archivos SQL

1. **[update_conductReports_table.sql](update_conductReports_table.sql)**
   - Script completo para crear/actualizar tabla `conductReports`
   
2. **[add_pdfPath_column.sql](add_pdfPath_column.sql)** (NUEVO)
   - Script simple para agregar columna `pdfPath` si no existe

## Estructura de Base de Datos

### Tabla: `conductReports`

```sql
CREATE TABLE conductReports (
    idConductReport INT AUTO_INCREMENT PRIMARY KEY,
    idStudent INT NOT NULL,
    idTeacher INT,
    fecha DATE,
    tipo VARCHAR(50),
    descripcion TEXT,
    observaciones TEXT,
    pdfPath VARCHAR(255),
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (idStudent) REFERENCES students(idStudent) ON DELETE CASCADE,
    INDEX idx_student (idStudent),
    INDEX idx_teacher (idTeacher),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Flujo de Trabajo

### Crear un Reporte
1. Usuario hace clic en botón de reporte (icono clipboard)
2. Sistema verifica si existen reportes previos
3. Si no existen, muestra botón "Crear Nuevo Reporte"
4. Usuario completa el formulario en el modal
5. Al guardar:
   - Se inserta el registro en la base de datos
   - Se genera automáticamente el PDF
   - Se guarda en `/reports/`
   - Se actualiza el campo `pdfPath`
6. SweetAlert confirma la operación

### Ver Reportes Existentes
1. Usuario hace clic en botón de reporte
2. Sistema muestra tabla con todos los reportes
3. Usuario puede:
   - **Ver PDF**: Abre PDF en nueva pestaña
   - **Ver Detalles**: Muestra SweetAlert con información completa

## Instalación

### 1. Base de Datos
Ejecute el script SQL para agregar la columna si no existe:
```bash
mysql -u usuario -p nombre_base_datos < add_pdfPath_column.sql
```

### 2. Directorio de Reportes
El directorio `/reports/` se crea automáticamente al guardar el primer reporte.

### 3. Permisos
Asegúrese de que el servidor web tenga permisos de escritura:
```bash
chmod 755 reports/
```

### 4. Despliegue al Servidor
Con WinSCP, suba los siguientes archivos:
- `teachers/list.php`
- `teachers/get_student_report.php`
- `teachers/save_student_report.php`
- `teachers/generate_report_pdf.php`
- `css/teacher/list.css`

## Funciones JavaScript Principales

### `checkStudentReport(studentId)`
Obtiene y muestra los reportes de un estudiante.

### `viewReportPDF(idConductReport)`
Abre el PDF del reporte en una nueva ventana.

### `viewReportDetails(idConductReport)`
Muestra los detalles del reporte en un SweetAlert.

### `saveReport(studentId)`
Guarda un nuevo reporte y genera el PDF automáticamente.

## Consideraciones de Seguridad

1. **Validación de sesión**: Todos los endpoints verifican la sesión del docente
2. **Prepared statements**: Todas las consultas SQL usan parámetros preparados
3. **Validación de entrada**: Los datos del formulario son validados en frontend y backend
4. **Control de acceso**: Solo docentes autenticados pueden crear/ver reportes

## Solución de Problemas

### El botón de reporte no aparece
- Verificar que JavaScript `cargarAlumnos()` incluye la columna en `row.innerHTML`
- Limpiar caché del navegador
- Verificar que el archivo CSS se cargó correctamente

# Sistema de Reportes de Conducta

## Descripción General
Sistema para gestionar reportes de conducta de estudiantes con generación de PDFs bajo demanda.

## Características

### Botón de Reportes
- Ubicación: Columna "Acciones" en lista de estudiantes
- Icono: Clipboard color cyan
- Funcionalidad: Ver reportes existentes o crear nuevos

### Sistema de Modales

**Modal Principal (Ver Reportes)**
- Muestra tabla con todos los reportes del alumno
- Si no hay reportes: botón para crear nuevo
- Columnas: Fecha, Tipo, Docente, Acciones (Ver PDF, Ver Detalles)

**Modal de Creación**
- Campos: Fecha, Tipo, Descripción, Observaciones
- Validación: Campos obligatorios excepto Observaciones

### Generación de PDFs
- Los PDFs se generan bajo demanda (no se guardan en disco)
- Funcionamiento igual a las boletas individuales
- Se abren en el navegador para ver/descargar
- Contenido: Información del alumno, detalles del reporte, docente que reporta

## Estructura de Base de Datos

Tu tabla `conductReports` debe tener:
- `idConductReport` - ID principal
- `idStudent` - Relación con estudiante
- `idTeacher` - Relación con docente  
- `date_` - Fecha del reporte
- `actionTaken` - Tipo de reporte
- `description` - Descripción  
- `feedback` - Observaciones

## Archivos del Sistema

### PHP
- `teachers/list.php` - Lista de estudiantes con botón de reporte
- `teachers/get_student_report.php` - Obtiene reportes de un estudiante
- `teachers/save_student_report.php` - Guarda nuevo reporte en BD
- `teachers/generate_report_pdf.php` - Genera PDF bajo demanda

### CSS
- `css/teacher/list.css` - Estilos del botón de reporte

## Flujo de Trabajo

**Crear Reporte:**
1. Clic en botón de reporte
2. Verificación de reportes existentes
3. Si no existe: crear nuevo con formulario
4. Guardar en base de datos

**Ver Reportes:**
1. Clic en botón de reporte
2. Tabla con todos los reportes
3. Ver PDF: genera y abre en navegador
4. Ver Detalles: muestra info completa en modal

## Funciones JavaScript

- `checkStudentReport(studentId)` - Obtiene y muestra reportes
- `viewReportPDF(idConductReport)` - Abre PDF en nueva pestaña
- `viewReportDetails(idConductReport)` - Muestra detalles en SweetAlert

## Seguridad
- Validación de sesión en todos los endpoints
- Prepared statements en todas las consultas SQL
- Validación de entrada en frontend y backend
- Solo docentes autenticados pueden crear/ver reportes
