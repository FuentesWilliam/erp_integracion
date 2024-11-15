<div class="panel">
    <h3>{l s='ERP Integración - Lista de Precios' mod='erp_integracion'}</h3>
    <ul class="nav nav-pills">
        <!-- Botón de regreso -->
        <li><a href="{$main_menu_url}" class="btn-circle" aria-label="{l s='Regresar al menú principal' mod='erp_integracion'}"><i class="process-icon-back"></i></a></li>
        <!-- Botones de acción -->
        <li><button id="" class="btn btn-primary">Actualizar ERP</button></li>
        <li><button id="" class="btn btn-primary">Actualizar Precios</button></li>
    </ul>
</div>

<!-- Contenedor de carga -->
<div id="price-loading" class="alert alert-info">
    {l s='Cargando la información de precios, por favor espere...' mod='erp_integracion'}
</div>

<!-- Panel de productos encontrados -->
<div class="panel">
    <h3>{l s='Productos encontrados en Prestashop' mod='erp_integracion'}</h3>

    <!-- Contenedor de datos de price -->
    <div id="price-data" style="display:none;">
        <!-- Aquí se insertará la tabla de datos generada por JavaScript -->
    </div>

    <!-- Controles de paginación -->
    <div id="pagination-controls" class="d-flex justify-content-between align-items-center mt-3">
        <button id="prev-page" class="btn btn-secondary" disabled>Anterior</button>
        <span id="page-info" class="align-self-center">Página 1</span>
        <button id="next-page" class="btn btn-secondary">Siguiente</button>
    </div>
</div>

<!-- Panel de productos no encontrados -->
<div class="panel">
    <h3>{l s='No encontrados en Prestashop' mod='erp_integracion'}</h3>
    <a href="" class="btn btn-warning">Descargar Informe</a>
</div>