<h2>{tr}Historical Usage Report{/tr}</h2>

<form method="post" action="{$page}">
    <input type="hidden" name="userfilter" value="{$userfilter|escape}">
    <div class="table-responsive">
        <table class='table'>
            <tr>
                <td>
                    {html_select_date time=$startDate prefix="startDate_" end_year="-10" day_value_format="%02d" field_order=$prefs.display_field_order}
                <br>
                    {html_select_date time=$endDate prefix="endDate_" end_year="-10" day_value_format="%02d" field_order=$prefs.display_field_order}
                </td>
                <td>
                    <select  class="form-control" name="action_type">
                        <option value="">{tr}all types{/tr}</option>
                        {foreach key=id item=data from=$credit_types}
                            <option value="{$id}" {if $act_type eq '{$id}'}selected="selected"{/if}>{$id|escape}</option>
                        {/foreach}
                    </select>
                </td>

                <td>&nbsp;</td>
                <td><input class="btn btn-primary" type="submit" value="{tr}filter{/tr}"><br/><br/></td>
            </tr>
            <tr>
                <th>{tr}Type{/tr}</th>
                <th>{tr}Usage Date{/tr}</th>
                <th colspan='2'>{tr}Amount Used{/tr}</th>
            </tr>
            {foreach item=con_data from=$consumption_data}
                <tr>
                    <td>{$con_data.credit_type}</td>
                    <td>{$con_data.usage_date|date_format:"%d-%m-%Y %H:%M:%S"}</td>
                    <td colspan='2'>{$con_data.used_amount}</td>
                </tr>
            {/foreach}
        </table>
    </div>
</form>
