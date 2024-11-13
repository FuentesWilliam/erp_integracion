{extends file="layouts/layout.tpl"}

{block name="content"}
    <h1>Sincronización Manual</h1>
    
    {if isset($confirmations)}
        <div class="alert alert-success">
            <ul>
                {foreach from=$confirmations item=confirmation}
                    <li>{$confirmation}</li>
                {/foreach}
            </ul>
        </div>
    {/if}

    {if isset($errors)}
        <div class="alert alert-danger">
            <ul>
                {foreach from=$errors item=error}
                    <li>{$error}</li>
                {/foreach}
            </ul>
        </div>
    {/if}

    <form action="{$sync_url}" method="post">
        <button type="submit" class="btn btn-primary">Sincronizar Ahora</button>
    </form>
{/block}
