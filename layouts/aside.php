<aside class="ds-sidebar">
    <div class="sidebar-content">
        <nav class="sidebar-nav">
            <ul class="ds-sidebar__nav">
                <li class="ds-sidebar__logo">
                    <img class="ds-sidebar__logo-img" src="../img/logo.webp" alt="Gregorio Torres Logo">
                </li>
                <!-- NAVEGACIÓN -->
                <?php
                $currentPage = basename($_SERVER['SCRIPT_FILENAME'], '.php');
                ?>
                <li class="ds-sidebar__item">
                    <a href="../admin/dashboard.php" class="ds-sidebar__link<?= $currentPage === 'dashboard' ? ' ds-sidebar__link--active' : '' ?>">
                        <i class="bi bi-house-door-fill ds-sidebar__icon"></i>
                        <span class="ds-sidebar__label">Inicio</span>
                    </a>
                </li>
                <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'AD' || (isset($_SESSION['idRole']) && $_SESSION['idRole'] == 3))): ?>
                <li class="ds-sidebar__item ds-sidebar__item--collapsible">
                    <div class="ds-sidebar__link" data-bs-toggle="collapse" href="#usuariosMenu" role="button"
                        aria-expanded="<?= in_array($currentPage, ['teachers', 'students']) ? 'true' : 'false' ?>" aria-controls="usuariosMenu">
                        <i class="bi bi-people-fill ds-sidebar__icon"></i>
                        <span class="ds-sidebar__label">Usuarios</span>
                        <i class="bi bi-chevron-down ds-sidebar__chevron"></i>
                    </div>
                    <div class="collapse<?= in_array($currentPage, ['teachers', 'students']) ? ' show' : '' ?>" id="usuariosMenu">
                        <a href="../admin/teachers.php" class="ds-sidebar__link ds-sidebar__link--sub<?= $currentPage === 'teachers' ? ' ds-sidebar__link--active' : '' ?>">
                            <i class="bi bi-person-fill ds-sidebar__icon ds-sidebar__icon--sub"></i>
                            <span class="ds-sidebar__label">Docentes</span>
                        </a>
                        <a href="../admin/students.php" class="ds-sidebar__link ds-sidebar__link--sub<?= $currentPage === 'students' ? ' ds-sidebar__link--active' : '' ?>">
                            <i class="bi bi-person-fill ds-sidebar__icon ds-sidebar__icon--sub"></i>
                            <span class="ds-sidebar__label">Alumnos</span>
                        </a>
                    </div>
                </li>
                <li class="ds-sidebar__item">
                    <a href="../admin/assignments.php" class="ds-sidebar__link<?= $currentPage === 'assignments' ? ' ds-sidebar__link--active' : '' ?>">
                        <i class="bi bi-list-task ds-sidebar__icon"></i>
                        <span class="ds-sidebar__label">Asignaciones</span>
                    </a>
                </li>
                <!-- GESTIÓN -->
                <li class="ds-sidebar__divider"></li>
                <li class="ds-sidebar__section">
                    <span class="ds-sidebar__section-label">Gestión</span>
                </li>
                <li class="ds-sidebar__item">
                    <a href="#" class="ds-sidebar__link" data-bs-toggle="modal"
                        data-bs-target="#modalFechaLimite">
                        <i class="bi bi-calendar-date-fill ds-sidebar__icon"></i>
                        <span class="ds-sidebar__label">Plazo de Calificaciones</span>
                    </a>
                </li>
                <li class="ds-sidebar__item">
                    <a href="#" class="ds-sidebar__link" data-bs-toggle="modal"
                        data-bs-target="#modalAñoEscolar">
                        <i class="bi bi-calendar-event-fill ds-sidebar__icon"></i>
                        <span class="ds-sidebar__label">Ciclo Escolar</span>
                    </a>
                </li>
                <li class="ds-sidebar__item">
                    <a href="#" class="ds-sidebar__link" data-bs-toggle="modal" data-bs-target="#modalPeriodos">
                        <i class="bi bi-calendar3-range ds-sidebar__icon"></i>
                        <span class="ds-sidebar__label">Períodos Escolares</span>
                    </a>
                </li>
                <li class="ds-sidebar__item">
                    <a href="#" class="ds-sidebar__link" data-bs-toggle="modal" data-bs-target="#modalGrupos">
                        <i class="bi bi-diagram-3-fill ds-sidebar__icon"></i>
                        <span class="ds-sidebar__label">Grupos</span>
                    </a>
                </li>
                <?php endif; ?>
                <!-- Modales movidos a layouts/modals.php (fuera del sidebar para evitar stacking context) -->
            </ul>
        </nav>
    </div>
</aside>

<!-- Modales del sidebar (fuera del <aside> para evitar stacking context trap) -->
<?php include __DIR__ . "/modals.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // ============================================
        // INICIALIZACIÓN DE FLATPICKR EN ESPAÑOL
        // ============================================
        function initializeFlatpickr(selector, options = {}) {
            const defaultOptions = {
                locale: "es",
                dateFormat: "Y-m-d",        // Formato interno para la base de datos
                altInput: true,              // Usar input alternativo para mostrar
                altFormat: "d/m/Y",          // Formato visual en español: día/mes/año
                allowInput: false,
                disableMobile: true,
                ...options
            };
            return flatpickr(selector, defaultOptions);
        }

        // Inicializar todos los campos de fecha existentes
        initializeFlatpickr("#inputFechaLimite");
        initializeFlatpickr("#nuevoInicio");
        initializeFlatpickr("#nuevoFin");
        initializeFlatpickr("#editInicio");
        initializeFlatpickr("#editFin");

        // Función para inicializar Flatpickr en campos dinámicos de trimestres
        window.initializeTrimesterDates = function() {
            const trimestreInputs = document.querySelectorAll('[id^="trimestre_inicio_"], [id^="trimestre_fin_"]');
            trimestreInputs.forEach(input => {
                if (!input._flatpickr) {
                    initializeFlatpickr(input);
                }
            });
        };
        // ============================================
        
        const inputFecha = document.getElementById('inputFechaLimite');
        const btnGuardar = document.getElementById('btnGuardarFecha');
        const btnQuitar = document.getElementById('btnQuitarFecha');
        const info = document.getElementById('fechaLimiteInfo');
        const fechaEspanol = document.getElementById('fechaEspanol');
        
        // Función para formatear fecha en español
        function formatearFechaEspanol(fechaISO) {
            if (!fechaISO) return 'No definida';
            const meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
            const [anio, mes, dia] = fechaISO.split('-');
            return `${parseInt(dia)} de ${meses[parseInt(mes) - 1]} de ${anio}`;
        }
        
        // Actualizar fecha en español cuando cambia el input
        if (inputFecha && fechaEspanol) {
            inputFecha.addEventListener('change', function() {
                // Obtener valor desde Flatpickr si existe
                const flatpickrInstance = this._flatpickr;
                const valor = flatpickrInstance ? flatpickrInstance.input.value : this.value;
                fechaEspanol.textContent = formatearFechaEspanol(valor);
            });
        }
        
        if (btnGuardar) {
            btnGuardar.addEventListener('click', function () {
                // Obtener valor desde Flatpickr si existe
                const flatpickrInstance = inputFecha._flatpickr;
                const fecha = flatpickrInstance ? flatpickrInstance.input.value : inputFecha.value;
                
                if (!fecha) {
                    Swal.fire({ icon: 'warning', title: 'Fecha requerida', text: 'Selecciona una fecha límite válida.' });
                    return;
                }
                fetch('../teachers/set_limit_date.php', {
                    method: 'POST',
                headers: {

                        'X-CSRF-Token': document.querySelector('meta[name=\"csrf-token\"]').content, 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'fechaLimite=' + encodeURIComponent(fecha)
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('modalFechaLimite'));
                            if (modal) modal.hide();
                            setTimeout(() => {
                                Swal.fire({ icon: 'success', title: '¡Guardado!', text: 'Fecha límite guardada correctamente.' });
                                // Actualizar en dashboard si existe la función
                                if (typeof window.mostrarFechaLimiteDashboard === 'function') {
                                    window.mostrarFechaLimiteDashboard(fecha);
                                }
                                // También intentar actualizar el elemento directamente
                                const fechaLimiteDashboard = document.getElementById('fechaLimiteDashboard');
                                if (fechaLimiteDashboard) fechaLimiteDashboard.textContent = formatearFechaEspanol(fecha);
                            }, 400);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo guardar la fecha.' });
                        }
                    });
            });
        }
        if (btnQuitar) {
            btnQuitar.addEventListener('click', function () {
                fetch('../teachers/set_limit_date.php', {
                    method: 'POST',
                headers: {

                        'X-CSRF-Token': document.querySelector('meta[name=\"csrf-token\"]').content, 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'quitarLimite=1'
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('modalFechaLimite'));
                            if (modal) modal.hide();
                            setTimeout(() => {
                                Swal.fire({ icon: 'success', title: 'Eliminado', text: 'Fecha límite eliminada.' });
                                // Actualizar en dashboard si existe la función
                                if (typeof window.mostrarFechaLimiteDashboard === 'function') {
                                    window.mostrarFechaLimiteDashboard('');
                                }
                                // También actualizar elementos directamente
                                const fechaLimiteDashboard = document.getElementById('fechaLimiteDashboard');
                                if (fechaLimiteDashboard) fechaLimiteDashboard.textContent = 'No definida';
                                inputFecha.value = '';
                                if (fechaEspanol) fechaEspanol.textContent = 'No definida';
                            }, 400);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo eliminar la fecha.' });
                        }
                    });
            });
        }
    });
</script>

<script>
    // ==================== NUEVO SISTEMA DE CICLO ESCOLAR ====================
    let currentSchoolYearId = null;

    function formatearFechaEspanol(fechaISO) {
        if (!fechaISO) return '';
        const meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        const [anio, mes, dia] = fechaISO.split('-');
        return `${parseInt(dia)} de ${meses[parseInt(mes) - 1]} de ${anio}`;
    }

    function cargarCicloEscolarActual() {
        fetch('../admin/manage_school_years.php', {
            method: 'POST',
                headers: {

                'X-CSRF-Token': document.querySelector('meta[name=\"csrf-token\"]').content, 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=getCurrentYear'
        })
        .then(r => r.json())
        .then(data => {
            const añoActual = data.currentServerYear;
            document.getElementById('añoActual').textContent = añoActual;
            document.getElementById('añoActual2').textContent = añoActual;
            document.getElementById('añoActualDisplay').textContent = añoActual;
            
            if (data.exists && data.year) {
                // Ya existe un ciclo escolar para este año
                currentSchoolYearId = data.year.idSchoolYear;
                document.getElementById('noCicloEscolar').style.display = 'none';
                document.getElementById('siCicloEscolar').style.display = 'block';
                
                // Usar setDate() de Flatpickr para cargar fechas correctamente
                const editInicioFp = document.getElementById('editInicio')._flatpickr;
                const editFinFp = document.getElementById('editFin')._flatpickr;
                if (editInicioFp) editInicioFp.setDate(data.year.startDate);
                if (editFinFp) editFinFp.setDate(data.year.endDate);
            } else {
                // No existe ciclo escolar, mostrar formulario de creación
                document.getElementById('noCicloEscolar').style.display = 'block';
                document.getElementById('siCicloEscolar').style.display = 'none';
            }
        });
    }

    function setAnioInfo(msg, type) {
        const el = document.getElementById('anioEscolarInfo');
        if (!el) return;
        const icons = { success: 'bi-check-circle', error: 'bi-exclamation-triangle', warning: 'bi-exclamation-triangle' };
        el.innerHTML = '<i class="bi ' + (icons[type] || 'bi-info-circle') + ' me-2"></i>' + msg;
        el.className = 'ds-alert ds-alert--' + type + ' mt-3';
        el.style.display = 'flex';
    }

    function setPeriodoInfo(msg, type) {
        const el = document.getElementById('periodoInfo');
        if (!el) return;
        const icons = { success: 'bi-check-circle', error: 'bi-exclamation-triangle', warning: 'bi-exclamation-triangle' };
        el.innerHTML = '<i class="bi ' + (icons[type] || 'bi-info-circle') + ' me-2"></i>' + msg;
        el.className = 'ds-alert ds-alert--' + type + ' mt-3';
        el.style.display = 'flex';
    }

    function crearCicloEscolar() {
        // Obtener valores desde las instancias de Flatpickr en formato Y-m-d
        const nuevoInicioFp = document.getElementById('nuevoInicio')._flatpickr;
        const nuevoFinFp = document.getElementById('nuevoFin')._flatpickr;
        
        const inicio = nuevoInicioFp ? nuevoInicioFp.input.value : document.getElementById('nuevoInicio').value;
        const fin = nuevoFinFp ? nuevoFinFp.input.value : document.getElementById('nuevoFin').value;
        
        if (!inicio || !fin) {
            setAnioInfo('Debes ingresar ambas fechas.', 'error');
            return;
        }
        
        fetch('../admin/manage_school_years.php', {
            method: 'POST',
                headers: {

                'X-CSRF-Token': document.querySelector('meta[name=\"csrf-token\"]').content, 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=add&startDate=${inicio}&endDate=${fin}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                setAnioInfo('Ciclo escolar creado con 3 trimestres automáticamente.', 'success');
                setTimeout(() => {
                    cargarCicloEscolarActual();
                }, 1500);
            } else {
                setAnioInfo(data.error || 'Error al crear el ciclo escolar.', 'error');
            }
        });
    }

    function guardarCicloEscolar() {
        // Obtener valores desde las instancias de Flatpickr en formato Y-m-d
        const inicioFp = document.getElementById('editInicio')._flatpickr;
        const finFp = document.getElementById('editFin')._flatpickr;
        
        const inicio = inicioFp ? inicioFp.input.value : document.getElementById('editInicio').value;
        const fin = finFp ? finFp.input.value : document.getElementById('editFin').value;
        
        if (!inicio || !fin) {
            setAnioInfo('Debes ingresar ambas fechas.', 'error');
            return;
        }
        
        fetch('../admin/manage_school_years.php', {
            method: 'POST',
                headers: {

                'X-CSRF-Token': document.querySelector('meta[name=\"csrf-token\"]').content, 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=edit&idSchoolYear=${currentSchoolYearId}&startDate=${inicio}&endDate=${fin}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                setAnioInfo('Fechas actualizadas correctamente.', 'success');
            } else {
                setAnioInfo(data.error || 'Error al actualizar.', 'error');
            }
        });
    }

    // ==================== GRUPOS — Tabs + Pills ====================
    let _gruposData = [];
    let _gradoActivo = 1;

    function cargarGrupos() {
        fetch('../admin/manage_groups.php', {
            method: 'POST',
                headers: {

                'X-CSRF-Token': document.querySelector('meta[name=\"csrf-token\"]').content, 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=list'
        })
        .then(r => r.json())
        .then(data => {
            _gruposData = data.success ? data.groups : [];
            renderGruposPills();
        });
    }

    function renderGruposPills() {
        const container = document.getElementById('grpPills');
        if (!container) return;
        container.innerHTML = '';

        const grupos = _gruposData.filter(g => parseInt(g.grade) === _gradoActivo);

        if (grupos.length === 0) {
            container.innerHTML = '<div class="grp-pills__empty">No hay grupos en este grado</div>';
            return;
        }

        grupos.forEach(g => {
            const pill = document.createElement('div');
            pill.className = 'grp-pill';
            pill.innerHTML = `<span class="grp-pill__label">${g.group_}</span><button class="grp-pill__remove" onclick="eliminarGrupo(${g.idGroup})" title="Eliminar ${g.group_}">&times;</button>`;
            container.appendChild(pill);
        });
    }

    function agregarGrupo() {
        const input = document.getElementById('nuevoGrupo');
        const grupo = input.value.trim();
        if (!grupo) {
            setGrupoInfo('Ingresa una letra de grupo.', false);
            input.focus();
            return;
        }
        fetch('../admin/manage_groups.php', {
            method: 'POST',
                headers: {

                'X-CSRF-Token': document.querySelector('meta[name=\"csrf-token\"]').content, 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=add&group_=${encodeURIComponent(grupo)}&grade=${_gradoActivo}`
        }).then(r => r.json()).then(data => {
            if (data.success) {
                input.value = '';
                setGrupoInfo('Grupo añadido correctamente.', true);
                cargarGrupos();
            } else {
                setGrupoInfo(data.error || 'Error al añadir.', false);
            }
        });
    }

    function eliminarGrupo(id) {
        Swal.fire({
            title: '¿Eliminar grupo?',
            text: 'Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#192E4E',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (!result.isConfirmed) return;
            fetch('../admin/manage_groups.php', {
                method: 'POST',
                headers: {

                    'X-CSRF-Token': document.querySelector('meta[name=\"csrf-token\"]').content, 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=delete&idGroup=${id}`
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Grupo eliminado',
                        text: 'El grupo se eliminó correctamente.',
                        confirmButtonColor: '#192E4E'
                    });
                    cargarGrupos();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.error || 'No se pudo eliminar el grupo.',
                        confirmButtonColor: '#192E4E'
                    });
                }
            }).catch(() => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo conectar con el servidor.',
                    confirmButtonColor: '#192E4E'
                });
            });
        });
    }

    function setGrupoInfo(msg, ok) {
        const el = document.getElementById('grupoInfo');
        if (!el) return;
        el.textContent = msg;
        el.className = ok ? 'grp-info grp-info--ok' : 'grp-info grp-info--err';
    }

    function seleccionarGrado(grado) {
        _gradoActivo = grado;
        document.querySelectorAll('.grp-tab').forEach(t => {
            t.classList.toggle('active', parseInt(t.dataset.grade) === grado);
        });
        const label = document.getElementById('grpAddLabel');
        if (label) label.textContent = `Agregar grupo al Grado ${grado}`;
        renderGruposPills();
    }

    // ==================== FUNCIONES PARA LOS 3 TRIMESTRES ====================
    function formatearFechaTabla(fechaISO) {
        if (!fechaISO || fechaISO === 'null') return 'Sin definir';
        const [anio, mes, dia] = fechaISO.split('-');
        return `${dia}/${mes}/${anio}`;
    }

    function cargarTrimestres() {
        fetch('../admin/manage_school_quarters.php', {
            method: 'POST',
                headers: {

                'X-CSRF-Token': document.querySelector('meta[name=\"csrf-token\"]').content, 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=list'
        })
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('trimestresContainer');
            const añoDisplay = document.getElementById('añoPeriodosDisplay');
            
            if (data.currentYear) {
                añoDisplay.textContent = data.currentYear;
            }
            
            if (!data.success) {
                container.innerHTML = `<div class="ds-alert ds-alert--warning"><i class="bi bi-exclamation-triangle me-2"></i>${data.error || 'Error al cargar trimestres'}</div>`;
                return;
            }
            
            if (data.quarters.length === 0) {
                container.innerHTML = `<div class="ds-alert ds-alert--warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Primero debes crear el ciclo escolar del año actual.
                </div>`;
                return;
            }
            
            // Generar tarjetas para los 3 trimestres
            container.innerHTML = '';
            data.quarters.forEach((q, index) => {
                const card = document.createElement('div');
                card.className = 'ds-card mb-3';
                card.innerHTML = `
                    <div class="ds-card__header" style="background-color: var(--ds-primary-800);">
                        <h6 class="ds-card__title" style="color: var(--ds-white);">
                            <i class="bi bi-calendar3"></i>${q.name}
                        </h6>
                    </div>
                    <div class="ds-card__body">
                        <p class="text-muted mb-3">${q.description || 'Sin descripción'}</p>
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label for="trimestre_inicio_${q.idSchoolQuarter}" class="form-label fw-semibold">
                                    <i class="bi bi-calendar-check me-1"></i>Fecha de Inicio:
                                </label>
                                <input type="text" class="form-control border-secondary flatpickr-date" 
                                       id="trimestre_inicio_${q.idSchoolQuarter}" 
                                       value="${q.startDate || ''}" placeholder="Seleccionar fecha" readonly>
                            </div>
                            <div class="col-md-5">
                                <label for="trimestre_fin_${q.idSchoolQuarter}" class="form-label fw-semibold">
                                    <i class="bi bi-calendar-x me-1"></i>Fecha de Fin:
                                </label>
                                <input type="text" class="form-control border-secondary flatpickr-date" 
                                       id="trimestre_fin_${q.idSchoolQuarter}" 
                                       value="${q.endDate || ''}" placeholder="Seleccionar fecha" readonly>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button class="ds-btn ds-btn--primary w-100" 
                                        data-quarter="${q.idSchoolQuarter}">
                                    <i class="bi bi-check-circle me-1"></i>
                                    Guardar
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                container.appendChild(card);
            });
            
            // Inicializar Flatpickr en los campos de fecha de trimestres recién creados
            if (typeof window.initializeTrimesterDates === 'function') {
                window.initializeTrimesterDates();
            }

            // Bind save buttons (delegated from container)
            container.querySelectorAll('.ds-btn[data-quarter]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    guardarFechasTrimestre(this.dataset.quarter);
                });
            });
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('trimestresContainer').innerHTML = `
                <div class="ds-alert ds-alert--error">Error de conexión</div>
            `;
        });
    }

    function guardarFechasTrimestre(id) {
        // Obtener valores desde las instancias de Flatpickr en formato Y-m-d
        const inicioEl = document.getElementById(`trimestre_inicio_${id}`);
        const finEl = document.getElementById(`trimestre_fin_${id}`);
        
        const inicioFp = inicioEl._flatpickr;
        const finFp = finEl._flatpickr;
        
        const inicio = inicioFp ? inicioFp.input.value : inicioEl.value;
        const fin = finFp ? finFp.input.value : finEl.value;
        
        if (!inicio || !fin) {
            setPeriodoInfo('Debes ingresar ambas fechas.', 'error');
            return;
        }
        
        fetch('../admin/manage_school_quarters.php', {
            method: 'POST',
                headers: {

                'X-CSRF-Token': document.querySelector('meta[name=\"csrf-token\"]').content, 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=edit&idSchoolQuarter=${id}&startDate=${inicio}&endDate=${fin}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                setPeriodoInfo('Fechas del trimestre actualizadas correctamente.', 'success');
            } else {
                setPeriodoInfo(data.error || 'Error al actualizar.', 'error');
            }
        });
    }

    // ==================== INICIALIZACIÓN ====================
    document.addEventListener('DOMContentLoaded', function () {
        const modalAnioEscolar = document.getElementById('modalAñoEscolar');
        if (modalAnioEscolar) {
            modalAnioEscolar.addEventListener('show.bs.modal', cargarCicloEscolarActual);
            const btnCrear = document.getElementById('btnCrearCiclo');
            if (btnCrear) btnCrear.onclick = crearCicloEscolar;
            const btnGuardar = document.getElementById('btnGuardarCiclo');
            if (btnGuardar) btnGuardar.onclick = guardarCicloEscolar;
        }
        
        const modalGrupos = document.getElementById('modalGrupos');
        if (modalGrupos) {
            modalGrupos.addEventListener('show.bs.modal', function() {
                _gradoActivo = 1;
                seleccionarGrado(1);
                cargarGrupos();
            });
            document.getElementById('btnAgregarGrupo').onclick = agregarGrupo;
            document.getElementById('nuevoGrupo').addEventListener('keydown', function(e) {
                if (e.key === 'Enter') { e.preventDefault(); agregarGrupo(); }
            });
            document.querySelectorAll('.grp-tab').forEach(function(tab) {
                tab.addEventListener('click', function() {
                    seleccionarGrado(parseInt(this.dataset.grade));
                });
            });
        }
        
        const modalPeriodos = document.getElementById('modalPeriodos');
        if (modalPeriodos) {
            modalPeriodos.addEventListener('show.bs.modal', cargarTrimestres);
        }
    });
</script>

<!-- Sidebar overlay for mobile -->
<div class="ds-sidebar-overlay" id="ds-sidebar-overlay"></div>

<!-- Sidebar toggle script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var sidebar = document.querySelector('.ds-sidebar');
    var overlay = document.getElementById('ds-sidebar-overlay');
    var toggle = document.getElementById('ds-sidebar-toggle');

    if (sidebar) {
        sidebar.classList.remove('ds-sidebar--open');
    }
    if (overlay) {
        overlay.classList.remove('ds-sidebar-overlay--visible');
    }
    document.body.classList.remove('ds-sidebar-is-open');

    if (toggle && sidebar && overlay) {
        toggle.addEventListener('click', function() {
            sidebar.classList.toggle('ds-sidebar--open');
            overlay.classList.toggle('ds-sidebar-overlay--visible');
            document.body.classList.toggle('ds-sidebar-is-open');
        });

        overlay.addEventListener('click', function() {
            sidebar.classList.remove('ds-sidebar--open');
            overlay.classList.remove('ds-sidebar-overlay--visible');
            document.body.classList.remove('ds-sidebar-is-open');
        });
    }
});
</script>