
<div class="panel">
    <h3>{l s='ERP Integración - Menú de Sincronización' mod='erp_integracion'}</h3>
    <ul class="nav nav-pills">
        <li>
            <a href="{$sync_stock_url}" class="btn btn-default">
                {l s='Sincronizar Stock' mod='erp_integracion'}
            </a>
        </li>
        <li>
            <a href="{$sync_prices_url}" class="btn btn-default">
                {l s='Sincronizar Precios' mod='erp_integracion'}
            </a>
        </li>
        <li>
            <a href="{$sync_sales_url}" class="btn btn-default">
                {l s='Sincronizar Ventas' mod='erp_integracion'}
            </a>
        </li>
    </ul>
</div>

<div id="stock-loading" class="alert alert-info">
    {l s='Cargando la información de stock, por favor espere...' mod='erp_integracion'}
</div>

<div id="stock-data" style="display:none;">
    <!-- Los datos de stock se cargarán aquí -->
</div>

<div id="pagination-controls">
    <button id="prev-page" disabled>Anterior</button>
    <span id="page-info">Página 1</span>
    <button id="next-page">Siguiente</button>
</div>