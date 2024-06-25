<div class="userWizardIconleft">{icon name='user-edit' alt="{tr}User Wizard{/tr}"}</div>
{tr}Use this form to fill in some extra information about you.{/tr}<br/>
<br/><br/>

{jq notonready=true} {* remove the button to save from the user tracker to leave only the one from the user wizard*}
    $("input[name=action0]").hide();
{/jq}

<div class="adminWizardContent">
    <fieldset>
        <legend>{tr}Extra information about you{/tr}</legend>
        <div class="userWizardIconright">{icon name='database' alt="{tr}User Tracker{/tr}"}</div>
        {if $userTrackerData}
            {$userTrackerData}
        {/if}
    </fieldset>
</div>
