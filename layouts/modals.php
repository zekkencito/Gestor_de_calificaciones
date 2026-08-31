<?php
// modals.php — Modales del sidebar administrativo
// Fuera del sidebar para evitar stacking context traps.
// Solo incluir donde $conexion ya esté disponible.
if (!isset($conexion)) {
    require_once __DIR__ . "/../conection.php";
}
?>

<!-- Modal: Plazo de Calificaciones -->
<div class="modal fade ds-modal" id="modalFechaLimite" tabindex="-1" aria-labelledby="modalFechaLimiteLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-calendar-date-fill me-2"></i>
                    Configurar Plazo de Calificaciones
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <?php
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
                    <div id="fechaLimiteInfo" class="ds-alert ds-alert--success mt-2" style="display: none;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="ds-btn ds-btn--secondary" type="button" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>
                    Cancelar
                </button>
                <button class="ds-btn ds-btn--danger" type="button" id="btnQuitarFecha">
                    <i class="bi bi-trash me-1"></i>
                    Quitar Fecha
                </button>
                <button class="ds-btn ds-btn--primary" type="button" id="btnGuardarFecha">
                    <i class="bi bi-check-circle me-1"></i>
                    Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Ciclo Escolar -->
<div class="modal fade ds-modal" id="modalAñoEscolar" tabindex="-1" aria-labelledby="modalAñoEscolarLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-calendar-event-fill me-2"></i>
                    Ciclo Escolar <span id="añoActualDisplay"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <!-- Vista cuando NO existe ciclo escolar -->
                <div id="noCicloEscolar" style="display: none;">
                    <div class="ds-alert ds-alert--info">
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
                            <button class="ds-btn ds-btn--primary w-100" id="btnCrearCiclo">
                                <i class="bi bi-plus-circle me-1"></i>
                                Crear
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Vista cuando SÍ existe ciclo escolar -->
                <div id="siCicloEscolar" style="display: none;">
                    <div class="ds-alert ds-alert--success">
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
                                <div class="d-flex justify-content-end">
                                    <button class="ds-btn ds-btn--primary" id="btnGuardarCiclo">
                                        <i class="bi bi-check-circle me-1"></i>
                                        Guardar Cambios
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="anioEscolarInfo" class="ds-alert ds-alert--success mt-3" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Grupos -->
<div class="modal fade ds-modal" id="modalGrupos" tabindex="-1" aria-labelledby="modalGruposLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-diagram-3-fill me-2"></i>
                    Administrar Grupos
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <!-- Tabs de grado -->
                <div class="grp-tabs" id="grpTabs" role="tablist">
                    <button class="grp-tab active" data-grade="1" role="tab">1°</button>
                    <button class="grp-tab" data-grade="2" role="tab">2°</button>
                    <button class="grp-tab" data-grade="3" role="tab">3°</button>
                    <button class="grp-tab" data-grade="4" role="tab">4°</button>
                    <button class="grp-tab" data-grade="5" role="tab">5°</button>
                    <button class="grp-tab" data-grade="6" role="tab">6°</button>
                </div>

                <!-- Pills de grupo (rendered by JS) -->
                <div class="grp-pills" id="grpPills"></div>

                <!-- Add form contextualizado -->
                <div class="grp-add">
                    <div class="grp-add__label" id="grpAddLabel">Agregar grupo al Grado 1</div>
                    <div class="grp-add__row">
                        <input type="text" class="grp-add__input" id="nuevoGrupo"
                            maxlength="2" placeholder="Letra" autocomplete="off">
                        <button class="ds-btn ds-btn--primary ds-btn--sm" id="btnAgregarGrupo">
                            <i class="bi bi-plus-lg"></i>
                            Agregar
                        </button>
                    </div>
                </div>

                <div id="grupoInfo" class="grp-info" role="status"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Períodos Escolares -->
<div class="modal fade ds-modal" id="modalPeriodos" tabindex="-1" aria-labelledby="modalPeriodosLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-calendar3-range me-2"></i>
                    Trimestres del Ciclo Escolar <span id="añoPeriodosDisplay"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="ds-alert ds-alert--warning">
                    <i class="bi bi-info-circle me-2"></i>
                    Define las fechas de inicio y fin para cada uno de los 3 trimestres del ciclo escolar actual.
                </div>
                
                <!-- Contenedor de trimestres -->
                <div id="trimestresContainer"></div>
                
                <div id="periodoInfo" class="ds-alert ds-alert--success mt-3" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Grupos modal styles — scoped to .ds-modal#modalGrupos -->
<style>
/* Tabs de grado */
.grp-tabs {
    display: flex;
    gap: var(--ds-space-2);
    margin-bottom: var(--ds-space-4);
}

.grp-tab {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 36px;
    font-family: var(--ds-font-family);
    font-size: var(--ds-text-sm);
    font-weight: var(--ds-font-semibold);
    color: var(--ds-gray-500);
    background-color: var(--ds-gray-50);
    border: 1px solid var(--ds-gray-200);
    border-radius: var(--ds-radius-sm);
    cursor: pointer;
    transition:
        background-color var(--ds-duration-fast) var(--ds-ease-out),
        border-color var(--ds-duration-fast) var(--ds-ease-out),
        color var(--ds-duration-fast) var(--ds-ease-out);
    user-select: none;
}

.grp-tab:hover {
    background-color: var(--ds-gray-100);
    color: var(--ds-gray-700);
}

.grp-tab.active {
    background-color: var(--ds-primary-800);
    border-color: var(--ds-primary-800);
    color: var(--ds-white);
}

/* Pills de grupo */
.grp-pills {
    display: flex;
    flex-wrap: wrap;
    gap: var(--ds-space-2);
    min-height: 40px;
    padding: var(--ds-space-3);
    background-color: var(--ds-gray-50);
    border: 1px solid var(--ds-gray-100);
    border-radius: var(--ds-radius-md);
    margin-bottom: var(--ds-space-4);
}

.grp-pills__empty {
    width: 100%;
    text-align: center;
    font-size: var(--ds-text-sm);
    color: var(--ds-gray-400);
    padding: var(--ds-space-2) 0;
}

.grp-pill {
    display: inline-flex;
    align-items: center;
    gap: 2px;
    height: 32px;
    padding: 0 var(--ds-space-2) 0 var(--ds-space-3);
    background-color: var(--ds-white);
    border: 1px solid var(--ds-gray-200);
    border-radius: var(--ds-radius-sm);
    font-family: var(--ds-font-family);
    font-size: var(--ds-text-sm);
    font-weight: var(--ds-font-semibold);
    color: var(--ds-gray-700);
    transition:
        border-color var(--ds-duration-fast) var(--ds-ease-out);
    animation: grp-pill-in var(--ds-duration-normal) var(--ds-ease-out) both;
}

.grp-pill:hover {
    border-color: var(--ds-gray-300);
}

.grp-pill__label {
    line-height: 1;
}

.grp-pill__remove {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    padding: 0;
    margin-left: 2px;
    background: none;
    border: none;
    border-radius: var(--ds-radius-sm);
    color: var(--ds-gray-400);
    font-size: 1rem;
    line-height: 1;
    cursor: pointer;
    transition:
        background-color var(--ds-duration-fast) var(--ds-ease-out),
        color var(--ds-duration-fast) var(--ds-ease-out);
}

.grp-pill__remove:hover {
    background-color: var(--ds-error-bg);
    color: var(--ds-error);
}

@keyframes grp-pill-in {
    from { opacity: 0; transform: scale(0.9); }
    to   { opacity: 1; transform: scale(1); }
}

/* Add form */
.grp-add {
    display: flex;
    align-items: center;
    gap: var(--ds-space-3);
    padding: var(--ds-space-3);
    background-color: var(--ds-gray-50);
    border: 1px solid var(--ds-gray-100);
    border-radius: var(--ds-radius-md);
}

.grp-add__label {
    font-size: var(--ds-text-xs);
    font-weight: var(--ds-font-semibold);
    color: var(--ds-gray-500);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    white-space: nowrap;
    flex-shrink: 0;
}

.grp-add__row {
    display: flex;
    gap: var(--ds-space-2);
    flex: 1;
    justify-content: flex-end;
}

.grp-add__input {
    width: 56px;
    height: 32px;
    padding: 0 var(--ds-space-2);
    font-family: var(--ds-font-family);
    font-size: var(--ds-text-sm);
    font-weight: var(--ds-font-semibold);
    text-align: center;
    text-transform: uppercase;
    color: var(--ds-gray-700);
    background-color: var(--ds-white);
    border: 1px solid var(--ds-gray-300);
    border-radius: var(--ds-radius-sm);
    outline: none;
    transition:
        border-color var(--ds-duration-fast) var(--ds-ease-out),
        box-shadow var(--ds-duration-fast) var(--ds-ease-out);
}

.grp-add__input:focus {
    border-color: var(--ds-primary-600);
    box-shadow: var(--ds-shadow-focus);
}

/* Status message */
.grp-info {
    font-size: var(--ds-text-xs);
    margin-top: var(--ds-space-2);
    min-height: 1em;
}

.grp-info--ok {
    color: var(--ds-success);
}

.grp-info--err {
    color: var(--ds-error);
}

/* Responsive — mobile */
@media (max-width: 576px) {
    .grp-tabs {
        gap: var(--ds-space-1);
    }

    .grp-tab {
        height: 32px;
        font-size: var(--ds-text-xs);
    }

    .grp-add {
        flex-direction: column;
        align-items: stretch;
    }

    .grp-add__label {
        text-align: left;
    }

    .grp-add__row {
        justify-content: stretch;
    }

    .grp-add__input {
        flex: 1;
        width: auto;
    }
}
</style>
