# 📚 Gestor de Calificaciones

Sistema web para la gestión escolar, enfocado en la administración de estudiantes, docentes, materias y calificaciones. Permite a administradores y profesores gestionar la información académica de manera segura y eficiente.

## 🚀 Funcionalidades principales

### 1. Gestión de usuarios
- **Administradores:** Panel para gestionar estudiantes, docentes, grupos, materias y asignaciones.
- **Profesores:** Panel para consultar y capturar calificaciones, ver materias y grupos asignados.

### 2. Gestión de estudiantes
- Alta, edición y baja de estudiantes.
- Asignación de grupo y ciclo escolar.
- Consulta de información personal y tutor.

### 3. Gestión de docentes
- Alta, edición y baja de docentes.
- Asignación de materias y grupos.
- Consulta de información profesional y académica.

### 4. Gestión de materias y asignaciones
- Alta y edición de materias.
- Asignación de materias a docentes y grupos.
- Consulta de materias por ciclo escolar.

### 5. Captura y consulta de calificaciones
- Registro de calificaciones por criterios de evaluación.
- Consulta de promedios por materia, grupo y estudiante.
- Descarga de boletas en PDF (por grupo y por estudiante).
- Paneles de estadísticas y alertas de fecha límite.

### 6. Seguridad
- Autenticación segura con contraseñas hasheadas (bcrypt).
- Cambio de contraseña obligatorio en primer acceso.
- Control de sesiones y tokens de acceso.
- Roles diferenciados (administrador, docente).

### 7. Otras características
- Filtros avanzados para búsqueda de estudiantes, docentes y asignaciones.
- Gestión de ciclos escolares y trimestres.
- Interfaz moderna y responsiva.
- Preloader y navegación lateral personalizada.

## 📁 Estructura de carpetas

- `/admin`: Panel y scripts para administración (estudiantes, docentes, asignaciones, dashboard).
- `/teachers`: Panel y scripts para profesores (calificaciones, materias, grupos, boletas).
- `/css`, `/js`, `/img`, `/font`: Recursos estáticos.
- `/layouts`: Componentes de interfaz (header, aside).
- `/temp_downloads`: Descargas temporales de PDFs.

## 🧑‍💻 Flujo de usuario

- **Login:** Acceso con usuario y contraseña. Redirección según rol.
- **Administradores:** Gestionan toda la información académica y usuarios.
- **Profesores:** Capturan y consultan calificaciones, descargan boletas, visualizan estadísticas.

## ⚙️ Instalación

1. Clona el repositorio:
	 ```bash
	 git clone https://github.com/zekkencito/Gestor_de_calificaciones.git
	 ```
2. Configura la base de datos y ejecuta los scripts SQL.
3. Ajusta los datos de conexión en `conection.php`.
4. Accede vía navegador a `index.php`.

## 🔒 Seguridad

- Contraseñas nunca se almacenan en texto plano.
- Cambios de contraseña forzados en primer acceso.
- Autenticación y gestión de sesiones robusta.

## 📄 Documentación y contacto

Consulta la documentación incluida en el repositorio para detalles técnicos y de uso.

¿Dudas o soporte? Abre un issue en el [repositorio](https://github.com/zekkencito/Gestor_de_calificaciones/issues).
