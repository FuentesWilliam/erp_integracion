<div class="panel">
    <h3>Logs de Sincronización</h3>

    <form method="get">
        <input type="hidden" name="controller" value="AdminLogs">
        <input type="hidden" name="token" value="{$token}">
        <label for="log_date">Seleccionar fecha:</label>
        <input type="date" name="log_date" id="log_date" value="{$date}">
        <button type="submit" class="btn btn-primary">Mostrar Logs</button>
    </form>

    {if $logs|count > 0}
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha/Hora</th>
                    <th>Mensaje</th>
                    <th>Estado</th>
                    <th>ID</th>
                    <th>Contexto</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$logs item=log}
                    <tr>
                        <td>{$log.timestamp}</td>
                        <td>{$log.message}</td>
                        <td>{$log.status}</td>
                        <td>{$log.id}</td>
                        <td>{$log.context}</td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    {else}
        <p>No se encontraron logs para la fecha seleccionada.</p>
    {/if}
</div>
