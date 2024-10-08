<div class="modal-header">{tr}Create new IoT App{/tr}</div>
<div class="modal-body">
    <form id="create-new-iot-app-form" class="needs-validation">
        <div class="mb-3">
            <label for="app-name" class="form-label">{tr}App Name{/tr}</label>
            <input type="text" required minlength="3" class="form-control" id="app-name"
                aria-describedby="app-name-help">
            <div class="invalid-feedback">{tr}Please provide a valid app name{/tr}</div>
        </div>
        <div class="mb-3">
            <label for="tracker-id" class="form-label">{tr}Select Tracker{/tr}</label>
            <select required class="form-select" id="tracker-id" aria-describedby="tracker-id-help">
                {foreach from=$trackerIdsList item=tracker}
                    <option value="{$tracker.trackerId}">{$tracker.name}</option>
                {/foreach}
            </select>

            <div id="tracker-id-help" class="form-text">{tr}Tracker Backend for your IoT app data{/tr}</div>
            <div class="invalid-feedback">{tr}The tracker is a required field{/tr}</div>
        </div>
    </form>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{tr}Close{/tr}</button>
    <button type="button" class="btn btn-primary">{tr}Save{/tr}</button>
</div>
