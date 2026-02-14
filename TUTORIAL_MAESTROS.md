# 👨‍🏫 Tutorial para Maestros
## Sistema Gestor de Calificaciones

### 📋 Índice
1. [Inicio de Sesión](#inicio-de-sesión)
2. [Dashboard](#dashboard)
3. [Gestión de Calificaciones](#gestión-de-calificaciones)
4. [Boletas y Reportes](#boletas-y-reportes)
5. [Reportes de Conducta](#reportes-de-conducta)
6. [Preguntas Frecuentes](#preguntas-frecuentes)

---

## 🔐 Inicio de Sesión

### Primera vez en el sistema
1. Acceder a la URL del sistema
2. Ingresar usuario y contraseña proporcionados por el administrador
3. **Importante:** En el primer inicio deberás cambiar tu contraseña
4. Elegir una contraseña segura (mínimo 8 caracteres)

### Recordar sesión
- Marcar la casilla "Recordarme" para mantener la sesión activa por 30 días
- Útil para no tener que iniciar sesión cada vez

---

## 📊 Dashboard

Al iniciar sesión verás:

### Resumen General
- **Total de Materias:** Número de materias que impartes
- **Total de Grupos:** Grupos asignados
- **Total de Estudiantes:** Alumnos bajo tu cargo

### Gráficos
- **Porcentaje de Aprobados por Grupo:** Visualiza el rendimiento de cada grupo
- **Promedios por Materia:** Comparativa de calificaciones entre tus materias

### Navegación Rápida
- **Lista de Alumnos:** Ver todos tus estudiantes
- **Materias:** Gestionar materias y grupos
- **Calificaciones:** Capturar calificaciones

---

## 📝 Gestión de Calificaciones

### Acceder a Calificaciones

1. **Menu lateral** → Calificaciones
2. Seleccionar:
   - **Ciclo Escolar**
   - **Grupo**
   - **Trimestre** (1º, 2º o 3º)

### Capturar Calificaciones

#### Opción 1: Tabla General
1. Ir a **"Lista de Alumnos"**
2. Seleccionar filtros (ciclo, grupo, trimestre)
3. Clic en nombre del alumno para expandir
4. Ver calificaciones de todas las materias

#### Opción 2: Por Materia
1. Ir a **"Materias"**
2. Seleccionar la materia específica
3. Ver lista de alumnos del grupo
4. Capturar calificaciones directamente

### Definir Criterios de Evaluación

Antes de capturar calificaciones, puedes definir criterios:

1. Clic en **"Criterios de Evaluación"**
2. Agregar criterios:
   - Nombre: Ej. "Tareas", "Examen", "Participación"
   - Porcentaje: Ej. 30%, 50%, 20%
3. **Total debe sumar 100%**
4. Guardar criterios

### Capturar Notas

1. En la tabla de calificaciones:
   - Ingresar calificación (0-10)
   - El sistema calcula automáticamente el promedio
2. Si definiste criterios:
   - Captura la nota de cada criterio
   - El promedio se calcula según porcentajes
3. Guardar cambios

### Fechas Límite

⚠️ **Importante:**
- El administrador establece fechas límite para captura
- Después de la fecha límite, no podrás modificar calificaciones
- Revisa el dashboard para ver las fechas activas

---

## 📄 Boletas y Reportes

### Generar Boleta Individual

1. Ir a **"Lista de Alumnos"**
2. Buscar al estudiante
3. Clic en botón **"Ver Boleta"** (icono de documento)
4. La boleta se abre en el navegador
5. Puedes imprimirla o descargarla (Ctrl+P)

**Contenido de la boleta:**
- Información del estudiante (nombre, matrícula, grado)
- Calificaciones de las 3 evaluaciones
- Promedio final por materia
- Promedio general
- Sello y fecha de generación

### Generar Boletas Grupales

1. Ir a **"Lista de Alumnos"**
2. Seleccionar ciclo escolar y grupo
3. Clic en **"Descargar Boletas Grupales"**
4. El sistema genera un ZIP con todas las boletas
5. Descargar archivo (se guarda en `temp_downloads/`)

**Ventajas:**
- Genera todas las boletas del grupo en un solo clic
- Cada boleta en archivo PDF separado
- Nombradas automáticamente: `Boleta_NombreAlumno.pdf`

### Visualizar Promedios

En la tabla de alumnos puedes ver:
- Promedio de cada trimestre
- Promedio final
- Badge de color según rendimiento:
  - 🟢 Verde: 9-10 (Excelente)
  - 🔵 Azul: 8-8.9 (Muy bien)
  - 🟡 Amarillo: 7-7.9 (Bien)
  - 🟠 Naranja: 6-6.9 (Suficiente)
  - 🔴 Rojo: < 6 (Insuficiente)

---

## 📋 Reportes de Conducta

### Ver Reportes Existentes

1. En **"Lista de Alumnos"**
2. Clic en botón **"Reporte"** (icono de portapapeles cyan)
3. Se abre modal con:
   - Tabla de reportes existentes
   - Fecha, tipo, docente que reportó
   - Opciones: Ver PDF o Ver Detalles

### Crear Nuevo Reporte

1. Clic en botón **"Reporte"** del alumno
2. Si no hay reportes previos:
   - Clic en **"Crear Nuevo Reporte"**
3. Si ya tiene reportes:
   - Clic en **"Agregar Nuevo Reporte"**

### Formulario de Reporte

Llenar los campos:

1. **Fecha:** Fecha del incidente (por defecto hoy)
2. **Tipo:** Acción tomada o tipo de incidente
   - Ejemplos: "Llamada de atención", "Citatorio", "Amonestación"
3. **Descripción:** Detalle del incidente (obligatorio)
   - Describe lo sucedido claramente
4. **Observaciones:** Información adicional (opcional)
   - Contexto, acciones tomadas, etc.

5. Clic en **"Guardar Reporte"**

### Ver PDF del Reporte

1. En la tabla de reportes
2. Clic en **"Ver PDF"**
3. El PDF se abre en nueva pestaña
4. Puedes imprimirlo o guardarlo

**Contenido del PDF:**
- Logo de la escuela
- Información del estudiante
- Detalles del reporte
- Docente que genera el reporte
- Fecha de creación
- Espacios para firmas

---

## ❓ Preguntas Frecuentes

### ¿Cómo cambio mi contraseña?

1. Clic en tu nombre (esquina superior derecha)
2. Seleccionar **"Cerrar Sesión"**
3. Iniciar sesión nuevamente
4. Contactar al administrador para restablecer

### ¿Puedo editar calificaciones después de guardarlas?

Sí, mientras no haya pasado la fecha límite establecida por el administrador.

### ¿Qué hago si un alumno no aparece en mi lista?

1. Verificar que estés en el ciclo y grupo correctos
2. Contactar al administrador para verificar asignaciones
3. Puede que el alumno no esté inscrito en tu materia

### ¿Los reportes de conducta quedan guardados?

Sí, todos los reportes quedan registrados en la base de datos. Los PDFs se generan bajo demanda (no se guardan en disco).

### ¿Puedo ver reportes de otros docentes?

Sí, al abrir el modal de reportes de un alumno, verás todos los reportes creados por cualquier docente.

### ¿Qué pasa si lleno mal un reporte?

Actualmente no se puede editar un reporte una vez guardado. Contacta al administrador si necesitas hacer cambios.

### ¿Puedo generar boletas antes de terminar el ciclo?

Sí, las boletas se generan con la información disponible hasta el momento. Las evaluaciones sin calificaciones aparecerán vacías.

### ¿Cómo se calcula el promedio final?

- Si tienes criterios de evaluación: suma ponderada según porcentajes
- Sin criterios: promedio simple de las tres evaluaciones
- El promedio general es el promedio de todas las materias

### ¿Qué navegadores son compatibles?

El sistema funciona en:
- Google Chrome (recomendado)
- Mozilla Firefox
- Microsoft Edge
- Safari

### ¿Puedo acceder desde mi celular?

Sí, el sistema es responsivo. Sin embargo, para capturar calificaciones se recomienda usar una computadora para mayor comodidad.

---

## 📞 Soporte Técnico

Si tienes problemas técnicos:
- Contacta al administrador del sistema
- Refresca la página (F5)
- Limpia caché del navegador
- Verifica tu conexión a internet

---

**Última actualización:** Febrero 2026  
**Versión del tutorial:** 1.0
