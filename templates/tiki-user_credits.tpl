{* $Id$ *}
{title url="tiki-user_credits.php" help="Credits"}{tr}User Credits{/tr}{/title}

<div class="table-responsive">
    <table class="table">
        <tr>
            <td><b>{tr}Type{/tr}</b></td>
            <td><b>{tr}Creation Date{/tr}</b><br>{tr}(YYYY-MM-DD HH:MM:SS){/tr}</td>
            <td><b>{tr}Expiration Date{/tr}</b><br>{tr}(YYYY-MM-DD HH:MM:SS){/tr}</td>
            <td><b>{tr}Used{/tr}</b><br>{tr}(level credits always 0){/tr}</td>
            <td><b>{tr}Total{/tr}</b></td>
        </tr>
        {foreach key=id item=data from=$credits}
        <tr>
            <td>{$data.credit_type|escape}</td>
            <td>{$data.creation_date|escape}</td>
            <td>{$data.expiration_date|escape}</td>
            <td>{$data.used_amount|escape}</td>
            <td>{$data.total_amount|escape}</td>
        </tr>
        {/foreach}
    </table>
</div>

{include file='include_credits_expiry.tpl' userPlans=$userPlans}

{include file='include_credits_usage_report.tpl' userfilter=$userfilter consumption_data=$consumption_data credit_types=$credit_types startDate=$startDate endDate=$endDate page='tiki-user_credits.php'}
