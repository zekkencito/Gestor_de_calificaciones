# 👨‍💼 Tutorial para Administradores
## Sistema Gestor de Calificaciones

### 📋 Índice
1. [Inicio de Sesión](#inicio-de-sesión)
2. [Dashboard Administrativo](#dashboard-administrativo)
3. [Gestión de Docentes](#gestión-de-docentes)
4. [Gestión de Estudiantes](#gestión-de-estudiantes)
5. [Asignaciones](#asignaciones)
6. [Ciclos Escolares](#ciclos-escolares)
7. [Configuración del Sistema](#configuración-del-sistema)
8. [Reportes y Consultas](#reportes-y-consultas)

---

## 🔐 Inicio de Sesión

### Acceso Administrativo
1. Ingresar a la URL del sistema
2. Usuario: `admin` (o el proporcionado)
3. Contraseña: [Configurada por el sistema]
4. Marcar "Recordarme" si deseas mantener la sesión

### Primer Inicio
- Si es primera vez, cambiar contraseña obligatoriamente
- Usar contraseña segura (mínimo 8 caracteres)

---

## 📊 Dashboard Administrativo

### Resumen General

Al ingresar verás 4 paneles principales:

1. **Total de Alumnos**
   - Número total de estudiantes registrados
   - Icono: Graduación

2. **Total de Docentes**
   - Cantidad de maestros activos
   - Icono: Persona con portafolios

3. **Total de Materias**
   - Materias disponibles en el sistema
   - Icono: Libro

4. **Total de Grupos**
   - Grupos configurados
   - Icono: Personas

### Gráficos Estadísticos

#### Porcentaje de Aprobados por Grupo
- Gráfico de líneas
- Muestra rendimiento de cada grupo
- Identifica grupos con bajo desempeño

#### Promedios por Materia
- Comparativa entre todas las materias
- Ayuda a identificar materias con dificultad
- Útil para tomar decisiones académicas

### Navegación Rápida
- **Alumnos:** Gestión completa de estudiantes
- **Docentes:** Administración de maestros
- **Asignaciones:** Vincular docentes con materias y grupos
- **Ciclo Escolar:** Configurar periodos académicos

---

## 👨‍🏫 Gestión de Docentes

### Ver Lista de Docentes

**Ruta:** Admin → Docentes

**Información visible:**
- ID
- Nombre completo
- Género
- Teléfono
- Email
- Grupos asignados
- Materias que imparten
- Estatus (Activo/Inactivo)
- Acciones

### Agregar Nuevo Docente

1. Clic en **"Agregar Docente"**
2. Llenar formulario:

#### Información Personal
- Nombre(s) *
- Apellido Paterno *
- Apellido Materno
- Género * (Masculino/Femenino)
- Teléfono
- Email *
- Dirección

#### Información Profesional
- INE
- Cédula Profesional
- Tipo de Docente *
  - ME: Maestro de Educación Especial
  - MS: Maestro de Secundaria

#### Asignaciones
- **Grupos:** Seleccionar uno o varios grupos
- **Materias:** Seleccionar materias que impartirá

3. Clic en **"Guardar"**

**El sistema automáticamente:**
- Genera usuario (ej. `maria34`)
- Genera contraseña aleatoria (6 caracteres)
- Envía credenciales al correo (si está configurado)
- Obliga al docente a cambiar contraseña en primer inicio

### Editar Docente

1. En la tabla, clic en **icono de lápiz** (editar)
2. Modificar campos necesarios
3. **Cambiar contraseña:** Si se llena el campo, se actualiza
4. Guardar cambios

### Ver Detalles

1. Clic en **icono de ojo** (ver)
2. Modal muestra:
   - Datos personales completos
   - Información laboral
   - Grupos y materias asignadas
   - Credenciales de acceso (usuario y contraseña)

### Eliminar Docente

1. Clic en **icono de basura** (eliminar)
2. Confirmar acción en modal
3. **Advertencia:** Eliminará también:
   - Usuario asociado
   - Asignaciones de materias y grupos
   - No elimina calificaciones ya capturadas

---

## 👨‍🎓 Gestión de Estudiantes

### Ver Lista de Alumnos

**Ruta:** Admin → Alumnos

### Agregar Estudiante

1. Clic en **"Agregar Alumno"**
2. Llenar datos:

#### Información Personal
- Nombre(s) *
- Apellido Paterno *
- Apellido Materno
- Género *
- Fecha de Nacimiento
- CURP
- Teléfono
- Email

#### Información Escolar
- Matrícula * (única)
- Grupo * (grado y salón)
- Ciclo Escolar *
- Estatus (Activo/Inactivo)

#### Información Familiar
- Nombre del Tutor
- Teléfono del Tutor
- Email del Tutor
- Dirección

3. Guardar

### Editar Estudiante

Similar a editar docente:
- Buscar alumno
- Editar información
- Cambiar de grupo si es necesario

### Eliminar Estudiante

⚠️ **Precaución:**
- Elimina el registro del alumno
- Elimina calificaciones asociadas
- Acción irreversible

---

## 📚 Asignaciones

### Vincular Docentes con Materias y Grupos

**Ruta:** Admin → Asignaciones

### Crear Nueva Asignación

1. **Agregar Asignación de Docente**
2. Seleccionar:
   - **Docente:** De la lista desplegable
   - **Grupo:** Ej. 1°A, 2°B
   - **Materia:** Ej. Matemáticas, Español

3. Guardar

### Buscar Asignaciones

**Filtros disponibles:**
- Por Grupo
- Por Materia
- Por Docente

**Ejemplo de uso:**
- Ver qué docentes están asignados a 1°A
- Ver quién imparte Matemáticas
- Ver todas las materias de un docente

### Eliminar Asignación

1. Buscar la asignación
2. Clic en "Eliminar"
3. Confirmar

**Efecto:**
- El docente ya no verá ese grupo/materia
- No puede capturar calificaciones
- Calificaciones previas se mantienen

---

## 📅 Ciclos Escolares

### Administrar Ciclos Escolares

**Ruta:** Admin → Ciclo Escolar

### Crear Nuevo Ciclo

1. Clic en **"Agregar Ciclo Escolar"**
2. Definir:
   - **Fecha de Inicio:** Ej. 21/08/2025
   - **Fecha de Fin:** Ej. 15/07/2026
   - **Año:** Ej. 2025-2026
   - **Estatus:** Activo/Inactivo

3. Guardar

### Configurar Trimestres

Para cada ciclo escolar:

1. **Trimestre 1**
   - Nombre: "Primer Trimestre"
   - Fecha inicio
   - Fecha fin

2. **Trimestre 2**
   - Nombre: "Segundo Trimestre"
   - Fecha inicio
   - Fecha fin

3. **Trimestre 3**
   - Nombre: "Tercer Trimestre"
   - Fecha inicio
   - Fecha fin

**Importante:**
- Los trimestres no deben sobreponerse
- Cubrir todo el ciclo escolar
- Los docentes solo pueden capturar en el trimestre activo

### Activar/Desactivar Ciclo

- Solo un ciclo puede estar activo
- Al activar uno nuevo, el anterior se desactiva
- Los docentes trabajan siempre con el ciclo activo

---

## ⚙️ Configuración del Sistema

### Fecha Límite para Calificaciones

**Ubicación:** Admin → Dashboard → Configuración

1. Establecer fecha límite
2. Después de esta fecha:
   - Docentes no pueden modificar calificaciones
   - Pueden consultar pero no editar
   - Pueden generar boletas

### Gestión de Grupos

**Ruta:** Admin → Grupos

1. Crear grupos para el ciclo
   - Grado (1°, 2°, 3°)
   - Grupo (A, B, C, D)

2. Asignar alumnos a grupos
3. Vincular docentes

### Administración de Materias

**Ruta:** Admin → Materias (si existe)

1. Agregar nuevas materias
2. Editar nombre de materias
3. Eliminar materias sin uso

### Sesiones Activas

**Ruta:** Admin → Sesiones Activas

**Funcionalidades:**
- Ver usuarios conectados
- Ver desde qué IP se conectan
- Cerrar sesión remota si es necesario
- Útil para seguridad

---

## 📈 Reportes y Consultas

### Boletas Individuales

1. Ir a **Alumnos**
2. Buscar estudiante
3. Generar boleta
4. Se abre PDF en navegador

### Boletas Grupales

1. Seleccionar grupo
2. Clic en "Generar Boletas Grupales"
3. Se crea ZIP con todas las boletas
4. Descargar archivo

### Reportes de Conducta

Como administrador puedes:
- Ver todos los reportes generados
- Consultar historial de un alumno
- Ver reportes por docente
- Generar PDF de cualquier reporte

### Estadísticas

En el dashboard:
- Porcentaje de aprobados
- Promedios por materia
- Rendimiento por grupo
- Comparativas entre periodos

---

## 🔒 Seguridad y Respaldos

### Buenas Prácticas

1. **Contraseñas:**
   - Cambiar contraseña regularmente
   - Usar contraseñas fuertes
   - No compartir credenciales

2. **Respaldos:**
   - Hacer respaldo de base de datos semanalmente
   - Guardar en lugar seguro
   - Probar restauración periódicamente

3. **Sesiones:**
   - Cerrar sesión al terminar
   - Revisar sesiones activas regularmente
   - Cerrar sesiones sospechosas

### Base de Datos

**Backup manual:**
```bash
mysqldump -u usuario -p nombre_bd > backup_$(date +%Y%m%d).sql
```

**Restauración:**
```bash
mysql -u usuario -p nombre_bd < backup_20260213.sql
```

---

## ❓ Solución de Problemas

### El docente no ve sus grupos

1. Verificar asignaciones en Admin → Asignaciones
2. Verificar que el ciclo escolar esté activo
3. Verificar que el docente esté activo

### Las calificaciones no se guardan

1. Verificar fecha límite
2. Revisar permisos de base de datos
3. Ver logs de error en servidor

### No se generan boletas

1. Verificar que existan calificaciones
2. Revisar configuración de FPDF
3. Verificar permisos de carpeta temp_downloads/

### El sistema está lento

1. Revisar número de sesiones activas
2. Optimizar consultas en base de datos
3. Limpiar carpeta temp_downloads/
4. Verificar recursos del servidor

---

## 📞 Mantenimiento Periódico

### Diario
- Revisar sesiones activas
- Verificar que docentes puedan acceder

### Semanal
- Respaldo de base de datos
- Limpiar temp_downloads/
- Revisar logs de errores

### Mensual
- Actualizar contraseñas administrativas
- Revisar usuarios inactivos
- Generar reportes estadísticos

### Por Ciclo Escolar
- Crear nuevo ciclo
- Configurar trimestres
- Actualizar asignaciones
- Verificar grupos y materias

---

## 🆘 Soporte

### Contacto Técnico
- Email: soporte@escuela.edu.mx
- Teléfono: (555) 123-4567
- Horario: Lunes a Viernes 8:00-16:00

### Recursos Adicionales
- [Tutorial para Maestros](TUTORIAL_MAESTROS.md)
- [Documentación de Reportes](DOCUMENTACION_REPORTES.md)
- [Manual de Usuario en PDF](docs/manual.pdf)

---

**Última actualización:** Febrero 2026  
**Versión del tutorial:** 1.0
