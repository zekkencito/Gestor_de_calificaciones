<head>
    <link rel="stylesheet" href="../css/admin/time.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>

<aside class="sidebar-modern">
    <div class="sidebar-content">
        <nav class="sidebar-nav">
            <ul class="nav flex-column" >
                <li class="nav-item logo-container">
                    <img class="logo" src="../img/logo.webp" alt="Gregorio Torres Logo">
                </li>
                <li class="nav-item">
                    <a href="../admin/dashboard.php" class="nav-link">
                        <i class="bi bi-house-door-fill me-2"></i> Inicio
                    </a>
                </li>
                <li class="nav-item">
                    <div class="nav-link collapsible-link" data-bs-toggle="collapse" href="#usuariosMenu" role="button"
                        aria-expanded="false" aria-controls="usuariosMenu">
                        <i class="bi bi-people-fill" style="margin-left: 0px;"></i> Usuarios <i class="bi bi-chevron-down ms-2" style="font-size: 1rem;"></i>
                    </div>
                    <div class="collapse" id="usuariosMenu">
                        <a href="../admin/teachers.php" class="nav-link sub-link pt-2">
                            <i class="bi bi-person-fill me-2"></i> Docentes
                        </a>
                        <a  href="../admin/students.php" class="nav-link sub-link pt-2">
                            <i class="bi bi-person-fill me-2"></i> Alumnos
                        </a>
                    </div>
                </li>
                <li class="nav-item">
                    <a href="../admin/assignments.php" class="nav-link">
                        <i class="bi bi-list-task me-2"></i> Asignaciones
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-bs-toggle="modal"
                        data-bs-target="#modalFechaLimite">
                        <i class="bi bi-calendar-date-fill me-2"></i> Plazo de Calificaciones
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-bs-toggle="modal"
                        data-bs-target="#modalAñoEscolar">
                        <i class="bi bi-calendar-event-fill me-2"></i> Ciclo escolar
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#modalPeriodos">
                        <i class="bi bi-calendar3-range me-2"></i> Períodos Escolares
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#modalGrupos">
                        <i class="bi bi-diagram-3-fill me-2"></i> Grupos
                    </a>
                </li>
                <div class="modal fade" id="modalFechaLimite" tabindex="-1" aria-labelledby="modalFechaLimiteLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header bg-primary text-white border-0">
                                <h5 id="tituloModal" class="modal-title">
                                    <i class="bi bi-calendar-date-fill me-2"></i>
                                    Configurar Plazo de Calificaciones
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                    aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body">
                                <?php
                                require_once "../conection.php";
                                $fechaLimite = null;
                                $res = $conexion->query("SELECT limitDate FROM limitDate WHERE idLimitDate = 1 LIMIT 1");
                                if ($row = $res->fetch_assoc()) {
                                    $fechaLimite = $row['limitDate'];
                                }
                                ?>
                                <div class="mb-3">
                                    <label for="inputFechaLimite" class="form-label fw-semibold">
                                        <i class="bi bi-calendar-check me-1"></i>
                                        Fecha límite de calificaciones:
                                    </label>
                                    <input type="text" class="form-control border-secondary flatpickr-date" id="inputFechaLimite"
                                        value="<?php echo $fechaLimite; ?>" placeholder="Seleccionar fecha" readonly>
                                    <div id="fechaFormateada" class="mt-2 p-2 bg-light rounded border">
                                        <small class="text-muted">
                                            <i class="bi bi-calendar3 me-1"></i>
                                            <strong>Fecha seleccionada:</strong> 
                                            <span id="fechaEspanol" class="text-primary">
                                                <?php 
                                                if ($fechaLimite) {
                                                    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
                                                    list($anio, $mes, $dia) = explode('-', $fechaLimite);
                                                    echo intval($dia) . ' de ' . $meses[intval($mes) - 1] . ' de ' . $anio;
                                                } else {
                                                    echo 'No definida';
                                                }
                                                ?>
                                            </span>
                                        </small>
                                    </div>
                                    <div id="fechaLimiteInfo" class="form-text text-success mt-2"></div>
                                </div>
                            </div>
                            <div class="modal-footer border-0 bg-light">
                                <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">
                                    <i class="bi bi-x-circle me-1"></i>
                                    Cancelar
                                </button>
                                <button class="btn btn-danger" type="button" id="btnQuitarFecha">
                                    <i class="bi bi-trash me-1"></i>
                                    Quitar Fecha
                                </button>
                                <button class="btn btn-primary" type="button" id="btnGuardarFecha">
                                    <i class="bi bi-check-circle me-1"></i>
                                    Guardar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="modalAñoEscolar" tabindex="-1" aria-labelledby="modalAñoEscolarLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header bg-primary text-white border-0">
                                <h5 id="tituloModal" class="modal-title">
                                    <i class="bi bi-calendar-event-fill me-2"></i>
                                    Ciclo Escolar <span id="añoActualDisplay"></span>
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                    aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body">
                                <!-- Vista cuando NO existe ciclo escolar -->
                                <div id="noCicloEscolar" style="display: none;">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        No existe un ciclo escolar para el año <strong id="añoActual"></strong>. 
                                        <br>Define las fechas de inicio y fin para crear el ciclo escolar de este año.
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-5">
                                            <label for="nuevoInicio" class="form-label fw-semibold">
                                                <i class="bi bi-calendar-check me-1"></i>
                                                Fecha de Inicio:
                                            </label>
                                            <input type="text" class="form-control border-secondary flatpickr-date" id="nuevoInicio" placeholder="Seleccionar fecha" readonly>
                                        </div>
                                        <div class="col-md-5">
                                            <label for="nuevoFin" class="form-label fw-semibold">
                                                <i class="bi bi-calendar-x me-1"></i>
                                                Fecha de Fin:
                                            </label>
                                            <input type="text" class="form-control border-secondary flatpickr-date" id="nuevoFin" placeholder="Seleccionar fecha" readonly>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button class="btn btn-primary w-100" id="btnCrearCiclo">
                                                <i class="bi bi-plus-circle me-1"></i>
                                                Crear
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Vista cuando SÍ existe ciclo escolar -->
                                <div id="siCicloEscolar" style="display: none;">
                                    <div class="alert alert-success">
                                        <i class="bi bi-check-circle me-2"></i>
                                        Ciclo escolar del año <strong id="añoActual2"></strong> configurado.
                                    </div>
                                    <div class="card">
                                        <div class="card-body">
                                            <h6 class="card-title">Fechas del Ciclo Escolar</h6>
                                            <div class="row g-3" id="editarCicloForm">
                                                <div class="col-md-6">
                                                    <label for="editInicio" class="form-label fw-semibold">
                                                        <i class="bi bi-calendar-check me-1"></i>
                                                        Fecha de Inicio:
                                                    </label>
                                                    <input type="text" class="form-control border-secondary flatpickr-date" id="editInicio" placeholder="Seleccionar fecha" readonly>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="editFin" class="form-label fw-semibold">
                                                        <i class="bi bi-calendar-x me-1"></i>
                                                        Fecha de Fin:
                                                    </label>
                                                    <input type="text" class="form-control border-secondary flatpickr-date" id="editFin" placeholder="Seleccionar fecha" readonly>
                                                </div>
                                                <div class="col-12">
                                                    <button class="btn btn-success" id="btnGuardarCiclo">
                                                        <i class="bi bi-check-circle me-1"></i>
                                                        Guardar Cambios
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="anioEscolarInfo" class="form-text text-success mt-3"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="modalGrupos" tabindex="-1" aria-labelledby="modalGruposLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header bg-primary text-white border-0">
                                <h5 id="tituloModal" class="modal-title">
                                    <i class="bi bi-diagram-3-fill me-2"></i>
                                    Administrar Grupos
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                    aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle text-center mb-0">
                                        <thead class="table-dark">
                                            <tr>
                                                <th><i class="bi bi-collection me-1"></i>Grupo</th>
                                                <th><i class="bi bi-mortarboard me-1"></i>Grado</th>
                                                <th><i class="bi bi-gear me-1"></i>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tablaGrupos"></tbody>
                                    </table>
                                </div>
                                <hr class="my-4">
                                <h6 class="fw-semibold mb-3">
                                    <i class="bi bi-plus-circle me-2"></i>
                                    Agregar Nuevo Grupo
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label for="nuevoGrupo" class="form-label fw-semibold">
                                            <i class="bi bi-collection me-1"></i>
                                            Grupo:
                                        </label>
                                        <input type="text" class="form-control border-secondary" id="nuevoGrupo"
                                            maxlength="2" placeholder="Ej: A">
                                    </div>
                                    <div class="col-md-5">
                                        <label for="nuevoGrado" class="form-label fw-semibold">
                                            <i class="bi bi-mortarboard me-1"></i>
                                            Grado:
                                        </label>
                                        <input type="text" class="form-control border-secondary" id="nuevoGrado"
                                            maxlength="2" placeholder="Ej: 1">
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button class="btn btn-primary w-100" id="btnAgregarGrupo">
                                            <i class="bi bi-plus-circle me-1"></i>
                                            Agregar
                                        </button>
                                    </div>
                                </div>
                                <div id="grupoInfo" class="form-text text-success mt-2"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Administrar Períodos Escolares -->
                <div class="modal fade" id="modalPeriodos" tabindex="-1" aria-labelledby="modalPeriodosLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header bg-primary text-white border-0">
                                <h5 id="tituloModal" class="modal-title">
                                    <i class="bi bi-calendar3-range me-2"></i>
                                    Trimestres del Ciclo Escolar <span id="añoPeriodosDisplay"></span>
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                    aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Define las fechas de inicio y fin para cada uno de los 3 trimestres del ciclo escolar actual.
                                </div>
                                
                                <!-- Contenedor de trimestres -->
                                <div id="trimestresContainer"></div>
                                
                                <div id="periodoInfo" class="form-text text-success mt-3"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </ul>
        </nav>
    </div>
</aside>

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
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'fechaLimite=' + encodeURIComponent(fecha)
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('modalFechaLimite'));
                            if (modal) modal.hide();
                            setTimeout(() => {
                                Swal.fire({ icon: 'success', title: '¡Guardado!', text: 'Fecha límite guardada correctamente.' });
                                const fechaLimiteDashboard = document.getElementById('fechaLimiteDashboard');
                                if (fechaLimiteDashboard) fechaLimiteDashboard.textContent = fecha;
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
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'quitarLimite=1'
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('modalFechaLimite'));
                            if (modal) modal.hide();
                            setTimeout(() => {
                                Swal.fire({ icon: 'success', title: 'Eliminado', text: 'Fecha límite eliminada.' });
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
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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

    function crearCicloEscolar() {
        // Obtener valores desde las instancias de Flatpickr en formato Y-m-d
        const nuevoInicioFp = document.getElementById('nuevoInicio')._flatpickr;
        const nuevoFinFp = document.getElementById('nuevoFin')._flatpickr;
        
        const inicio = nuevoInicioFp ? nuevoInicioFp.input.value : document.getElementById('nuevoInicio').value;
        const fin = nuevoFinFp ? nuevoFinFp.input.value : document.getElementById('nuevoFin').value;
        
        if (!inicio || !fin) {
            document.getElementById('anioEscolarInfo').textContent = 'Debes ingresar ambas fechas.';
            document.getElementById('anioEscolarInfo').className = 'form-text text-danger mt-3';
            return;
        }
        
        fetch('../admin/manage_school_years.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=add&startDate=${inicio}&endDate=${fin}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('anioEscolarInfo').textContent = 'Ciclo escolar creado con 3 trimestres automáticamente.';
                document.getElementById('anioEscolarInfo').className = 'form-text text-success mt-3';
                setTimeout(() => {
                    cargarCicloEscolarActual();
                }, 1500);
            } else {
                document.getElementById('anioEscolarInfo').textContent = data.error || 'Error al crear el ciclo escolar.';
                document.getElementById('anioEscolarInfo').className = 'form-text text-danger mt-3';
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
            document.getElementById('anioEscolarInfo').textContent = 'Debes ingresar ambas fechas.';
            document.getElementById('anioEscolarInfo').className = 'form-text text-danger mt-3';
            return;
        }
        
        fetch('../admin/manage_school_years.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=edit&idSchoolYear=${currentSchoolYearId}&startDate=${inicio}&endDate=${fin}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('anioEscolarInfo').textContent = 'Fechas actualizadas correctamente.';
                document.getElementById('anioEscolarInfo').className = 'form-text text-success mt-3';
            } else {
                document.getElementById('anioEscolarInfo').textContent = data.error || 'Error al actualizar.';
                document.getElementById('anioEscolarInfo').className = 'form-text text-danger mt-3';
            }
        });
    }

    function cargarGrupos() {
        fetch('../admin/manage_groups.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:'action=list'
        })
            .then(r => r.json())
            .then(data => {
                const tbody = document.getElementById('tablaGrupos');
                tbody.innerHTML = '';
                if (data.success && data.groups.length) {
                    data.groups.forEach(g => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `<td>${g.group_}</td><td>${g.grade}</td>
                        <td><button style="height: 5vh;" class='buttonDelete1' onclick='eliminarGrupo(${g.idGroup})'>Borrar</button></td>`;
                        tbody.appendChild(tr);
                    });
                } else {
                    tbody.innerHTML = `<tr><td colspan='3'>Sin registros</td></tr>`;
                }
            });
    }

    function agregarGrupo() {
        const grupo = document.getElementById('nuevoGrupo').value.trim();
        const grado = document.getElementById('nuevoGrado').value.trim();
        if (!grupo || !grado) {
            document.getElementById('grupoInfo').textContent = 'Debes ingresar grupo y grado.';
            return;
        }
        fetch('../admin/manage_groups.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=add&group_=${encodeURIComponent(grupo)}&grade=${encodeURIComponent(grado)}`
        }).then(r => r.json()).then(data => {
            if (data.success) {
                document.getElementById('grupoInfo').textContent = 'Grupo añadido correctamente.';
                cargarGrupos();
            } else {
                document.getElementById('grupoInfo').textContent = data.error || 'Error al añadir.';
            }
        });
    }

    function eliminarGrupo(id) {
        if (!confirm('¿Seguro de borrar este grupo?')) return;
        fetch('../admin/manage_groups.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=delete&idGroup=${id}`
        }).then(r => r.json()).then(data => {
            if (data.success) cargarGrupos();
        });
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
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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
                container.innerHTML = `<div class="alert alert-warning">${data.error || 'Error al cargar trimestres'}</div>`;
                return;
            }
            
            if (data.quarters.length === 0) {
                container.innerHTML = `<div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Primero debes crear el ciclo escolar del año actual.
                </div>`;
                return;
            }
            
            // Generar tarjetas para los 3 trimestres
            container.innerHTML = '';
            data.quarters.forEach((q, index) => {
                const card = document.createElement('div');
                card.className = 'card mb-3';
                card.innerHTML = `
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">
                            <i class="bi bi-calendar3 me-2"></i>${q.name}
                        </h6>
                    </div>
                    <div class="card-body">
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
                                <button class="btn btn-success w-100" 
                                        onclick="guardarFechasTrimestre(${q.idSchoolQuarter})">
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
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('trimestresContainer').innerHTML = `
                <div class="alert alert-danger">Error de conexión</div>
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
            document.getElementById('periodoInfo').textContent = 'Debes ingresar ambas fechas.';
            document.getElementById('periodoInfo').className = 'form-text text-danger mt-3';
            return;
        }
        
        fetch('../admin/manage_school_quarters.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=edit&idSchoolQuarter=${id}&startDate=${inicio}&endDate=${fin}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('periodoInfo').textContent = 'Fechas del trimestre actualizadas correctamente.';
                document.getElementById('periodoInfo').className = 'form-text text-success mt-3';
            } else {
                document.getElementById('periodoInfo').textContent = data.error || 'Error al actualizar.';
                document.getElementById('periodoInfo').className = 'form-text text-danger mt-3';
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
            modalGrupos.addEventListener('show.bs.modal', cargarGrupos);
            document.getElementById('btnAgregarGrupo').onclick = agregarGrupo;
        }
        
        const modalPeriodos = document.getElementById('modalPeriodos');
        if (modalPeriodos) {
            modalPeriodos.addEventListener('show.bs.modal', cargarTrimestres);
        }
    });
</script>

<style>
    .sidebar-modern {
        background-color:rgb(236, 236, 236); /* Fondo gris claro */
        width: 190px; /* Ancho ligeramente mayor */
        min-height: 100vh; /* Para que ocupe toda la altura */
        box-shadow: 1px 10px 10px #192E4E; /* Sombra sutil */
        display: flex;
        flex-direction: column;
        padding-left: 5px;
    }

    .sidebar-content {
        padding: 5px;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .sidebar-nav {
        flex-grow: 1;
    }

    .logo-container {
        padding-top: 9px;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #e9ecef;
        margin-bottom: 1.5rem;
    }

    .logo {
        height: 79px;
        width: 70.6px;
        display: block;
        margin: 0 auto;
    }

    .nav-link {
        padding: 0.75rem 1rem;
        color:rgb(0, 0, 0); /* Texto gris oscuro */
        text-decoration: none;
        display: flex;
        align-items: center;
        transition: background-color 0.15s ease-in-out, color 0.15s ease-in-out;
        border-radius: 0.25rem;
    }

    .nav-link:hover {
        background-color:rgb(217, 220, 224);
        color: rgb(38, 75, 130);
    }

    .nav-link i {
        font-size: 1.5rem;
        margin-right: 0.5rem;
    }

    .collapsible-link {
        cursor: pointer;
        padding: 0.6rem 1rem;
        color:rgb(0, 0, 0);
        display: flex;
        align-items: center;
        border-radius: 0.25rem;
        font-size: 0.9rem;
    }

    .collapsible-link:hover {
        background-color:rgb(217, 220, 224);
        color: rgb(38, 75, 130);
    }

    .collapsible-link i {
        margin-left: auto;
    }

    .sub-link {
        padding-left: 2rem;
        font-size: 0.9rem;
    }
</style>