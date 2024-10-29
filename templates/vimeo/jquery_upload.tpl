{* Used by smarty_function_vimeo_uploader() *}

<div class="mb-3 row">
    <div class="col-md-12">
        <div class="mb-3 row">
            <label>
            {tr}Title{/tr}
            </label>
            <input id="vimeofiletitle" type="text" name="title" class="form-control" required />
        </div>
        <div class="card bg-body-tertiary fileupload mb-0">
            <div class="card-body">
                <p class="text-center">{icon name="cloud-upload"} {tr}Browse for video file to upload{/tr}
                    {* The file input field used as target for the file upload widget *}
                    <br /><br /><input id="fileupload" type="file" accept="video/*" name="file_data">
                </p>
            </div>
        </div>
        <div>
            <label>{tr}Upload Progress{/tr}</label>
        </div>
        <div class="progress" id="progress">
            <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div>
        </div>
        </div>
    </div>
    </div>
</div>
<div class="mb-3 row">
    <div id="files" class="files text-center col-md-12"></div>
</div>
