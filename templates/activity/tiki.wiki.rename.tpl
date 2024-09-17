{activityframe activity=$activity heading="{tr _0=$activity.user|userlink}%0 renamed a page{/tr}"}
    <p>
        {object_link type=$activity.type id=$activity.object}<br>
        {if !empty($activity.edit_comment)}<span class="description">{$activity.edit_comment|escape}</span>{/if}
    </p>
{/activityframe}
