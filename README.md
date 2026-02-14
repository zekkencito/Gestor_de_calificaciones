# 📚 Sistema Gestor de Calificaciones
## Escuela Gregorio Torres Quintero No. 2308

### Descripción
Sistema web integral para la gestión de calificaciones, estudiantes, docentes y reportes de conducta. Diseñado para facilitar la administración escolar y el seguimiento académico de los alumnos.

### ✨ Características Principales

#### Para Administradores
- Gestión completa de docentes, estudiantes y materias
- Asignación de grupos y materias a docentes
- Administración de ciclos escolares y trimestres
- Configuración de fechas límite para calificaciones
- Generación de boletas individuales y grupales en PDF
- Visualización de estadísticas y reportes
- Control de sesiones activas y seguridad

#### Para Docentes
- Captura de calificaciones por trimestre
- Definición de criterios de evaluación personalizados
- Generación de boletas individuales y grupales
- Creación y consulta de reportes de conducta
- Visualización de promedios por materia y alumno
- Dashboard con resumen de grupos y materias

### 🗂️ Estructura del Sistema

```
Gestor_de_calificaciones/
├── index.php              # Página de login
├── conection.php          # Configuración de BD
├── admin/                 # Módulo de administración
│   ├── dashboard.php
│   ├── teachers.php
│   ├── students.php
│   ├── assignments.php
│   └── manage_school_years.php
├── teachers/              # Módulo de docentes
│   ├── dashboard.php
│   ├── list.php
│   ├── grades.php
│   ├── subjects.php
│   └── generate_report_pdf.php
├── css/                   # Estilos
├── js/                    # Scripts
└── img/                   # Recursos gráficos
```

### 🚀 Instalación

1. **Clonar el repositorio**
   ```bash
   git clone https://github.com/usuario/Gestor_de_calificaciones.git
   ```

2. **Configurar base de datos**
   - Crear base de datos MySQL
   - Ejecutar el script SQL de instalación
   - Configurar credenciales en `conection.php`

3. **Configurar servidor web**
   - PHP 7.4 o superior
   - MySQL 5.7 o superior
   - Extensiones: mysqli, gd, mbstring

4. **Permisos**
   - Dar permisos de escritura a carpeta `temp_downloads/`

### 👤 Usuario Demo para GitHub

**Docente:**
- Usuario: `profesordemo`
- Contraseña: `github123`
- Rol: Maestro (gestión de calificaciones)

> ⚠️ **Nota:** Cambiar estas credenciales en producción por seguridad.

### 📖 Documentación

- **[Tutorial para Maestros](TUTORIAL_MAESTROS.md)** - Guía completa de uso del módulo de docentes
- **[Tutorial para Administradores](TUTORIAL_ADMIN.md)** - Guía completa de administración del sistema
- **[Documentación de Reportes](DOCUMENTACION_REPORTES.md)** - Sistema de reportes de conducta

### 🔒 Seguridad

- Contraseñas hasheadas con `password_hash()`
- Prepared statements en todas las consultas SQL
- Validación de sesiones en cada página
- Control de sesión única por usuario
- Sistema de "Recordarme" con tokens seguros
- Cambio de contraseña obligatorio al primer ingreso

### 🛠️ Tecnologías Utilizadas

- **Backend:** PHP 7.4+
- **Base de Datos:** MySQL/MariaDB
- **Frontend:** HTML5, CSS3, JavaScript ES6
- **UI Framework:** Bootstrap 5.3.3
- **Librerías:** 
  - FPDF (generación de PDFs)
  - SweetAlert2 (notificaciones)
  - Chart.js (gráficos)
  - Bootstrap Icons

### 📋 Requisitos del Sistema

- PHP >= 7.4
- MySQL >= 5.7 o MariaDB >= 10.2
- Apache/Nginx con mod_rewrite
- Navegador moderno (Chrome, Firefox, Edge)

### 🤝 Contribuciones

Las contribuciones son bienvenidas. Por favor:
1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add: nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

### 📝 Licencia

Este proyecto es de código abierto y está disponible bajo la licencia MIT.

### 📞 Soporte

Para reportar problemas o solicitar características:
- Abrir un **Issue** en GitHub
- Consultar la documentación en los tutoriales

---

**Versión:** 2.0  
**Última actualización:** Febrero 2026