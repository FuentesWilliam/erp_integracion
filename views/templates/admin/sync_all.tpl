<div class="panel">
    <h3>{l s='ERP Integración - Lista de Stock' mod='erp_integracion'}</h3>
    <nav class="navegador">
        <!-- Botón de regreso -->
        <a href="{$main_menu_url}" class="btn-circle" aria-label="{l s='Regresar al menú principal' mod='erp_integracion'}"><i class="process-icon-back"></i></a>

        <!-- Botones de acción -->
        <button class="btn btn-warning btn-loading" id="update-sync">
            <span class="button-text">{l s="Actualizar ERP" mod='erp_integracion'}</span>
            <span class="spinner"></span>
        </button>

        <button class="btn btn-primary btn-loading" id="update-stock-products" disabled>
            <span class="button-text">{l s="Actualizar Stock" mod='erp_integracion'}</span>
            <span class="spinner"></span>
        </button>

        <button class="btn btn-primary btn-loading" id="update-price-products" disabled>
            <span class="button-text">{l s="Actualizar Precios" mod='erp_integracion'}</span>
            <span class="spinner"></span>
        </button>
    </nav>
</div>

<!-- Contenedor de carga -->
<div id="data-loading" class="alert alert-info">
    {l s='Cargando la información, por favor espere...' mod='erp_integracion'}
</div>

<div id="erp-alert" class="alert" style="display: none;"></div>

<!-- Panel de productos encontrados -->
<div class="panel">
    <h3>{l s='Productos encontrados en Prestashop' mod='erp_integracion'}</h3>

    <!-- Contenedor de datos de stock -->
    <div id="stock-data" style="display:none;">
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
    <a href="{$download_not_found}" class="btn btn-warning">Descargar Informe</a>
</div>