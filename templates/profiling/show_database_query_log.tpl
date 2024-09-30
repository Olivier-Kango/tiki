
{*https://codepen.io/n3k1t/pen/OJMGgyq*}

{function name=displayLogTable level=0 data=null parentHash=null}
    <table class='table table-condensed table-striped table-hover tikiDatabaseQueryTable accordion'>
        <tr>
            <th>{$queryLogLabels['DESCRIPTION_TEXT']}</th>
            <th>{$queryLogLabels['PERCENT_OF_PARENT_GROUP_TIME']}</th>
            <th>{$queryLogLabels['PERCENT_OF_REQUEST_TIME']}</th>
            <th>{$queryLogLabels['TOTAL_TIME_MS']}</th>
            <th>{$queryLogLabels['TOTAL_COUNT']}</th>
        </tr>
        {foreach $data as $hash => $entry}
            <tr class="accordion-item accordion-header">
                <td class="tikiDatabaseQueryDescriptionColumn">
                {if $entry['children']}
                    <button class="accordion-button viewMore {if $parentHash}collapsed{/if}" type="button" data-bs-toggle="collapse" data-bs-target="#{$hash}_child_content" aria-controls="{$hash}_child_content" aria-expanded="{if $parentHash}false{else}true{/if}">{$entry['DESCRIPTION_TEXT']|escape}</button>
                {/if}
                {if $entry['EXECUTABLE_SQL_TEXT']}
                    <div class="accordion" id="{$hash}_executable_version">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#{$hash}_executable_version_collapse" aria-expanded="false" aria-controls="{$hash}_executable_version_collapse">
                                {$entry['DESCRIPTION_TEXT']|escape}
                               </button>
                            </h2>
                            <div id="{$hash}_executable_version_collapse" class="accordion-collapse collapse" data-bs-parent="#{$hash}">
                                <div class="accordion-body">
                                <h3>{tr}Executable version:{/tr}</h3>
                                <pre>
                                {$entry['EXECUTABLE_SQL_TEXT']|escape}
                                </pre>
                                </div>
                            </div>
                        </div>
                    </div>
                {/if}
                </td>
                <td>{if $parentHash}
                {($entry['PERCENT_OF_PARENT_GROUP_TIME'])|round:2}
                {/if}</td>
                <td>{($entry['PERCENT_OF_REQUEST_TIME'])|round:2}</td>
                <td>{($entry['TOTAL_TIME_MS'])|round:2}</td>
                <td>{$entry['TOTAL_COUNT']}</td>
            </tr>
            {if $entry['children']}
                <tr class='{if $parentHash}collapse{else}show{/if} accordion-collapse' id='{$hash}_child_content' data-bs-parent="{$parentHash}_child_content">
                    <td colspan="5">{displayLogTable data=$entry['children'] parentHash=$hash}</td>
                </tr>
            {/if}
        {/foreach}
    </table>
{/function}


{if $queryLogData}
    <h2>Database query report</h2>
    <div id="database_query_log">
    {displayLogTable data=$queryLogData[3]}
    </div>
{/if}
