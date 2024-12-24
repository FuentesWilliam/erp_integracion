<div class="logs-container">
    <h2>Fecha logs: {$date}</h2>

    <!-- Formulario para seleccionar la fecha -->
    <form action="{$current_url}" method="post">
        <label for="log_date">Seleccionar fecha:</label>
        <input type="date" name="log_date" value="{$date}" />
        <button type="submit" class="btn btn-primary">{l s='Mostrar Logs'}</button>
    </form>


    <!-- Tabla para mostrar los logs -->
    <table class="table">
        <thead>
            <tr>
                <th>Fecha y hora</th>
                <th>Estado</th>
                <th>Acción</th>
                <th>Detalles</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$logs.data item=log}
                <tr>
                    <td>{$log.timestamp}</td>
                    <td>
                        {if $log.status == 'success'}
                            <span class="label label-success">Éxito</span>
                        {elseif $log.status == 'failure'}
                            <span class="label label-danger">Fallo</span>
                        {else}
                            <span class="label label-warning">Pendiente</span>
                        {/if}
                    </td>
                    <td>{$log.message}</td>
                    <td>{$log.context}</td>
                </tr>
            {/foreach}
        </tbody>
    </table>

    <!-- Mensaje si no hay logs -->
    {if !$logs.data}
        <p>No se encontraron logs para la fecha seleccionada.</p>
    {/if}
</div>

