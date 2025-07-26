{title help="PeerTube" admpage="video"}{tr}Upload Video{/tr}{/title}
<form action="tiki-peertube_upload.php" method="post" enctype="multipart/form-data">
    <div>
        <label>{tr}Video File{/tr}: <input type="file" name="video" accept="video/*" required></label>
    </div>
    <div>
        <label>{tr}Title{/tr}: <input type="text" name="name"></label>
    </div>
    <div>
        <label>{tr}Description{/tr}: <textarea name="description"></textarea></label>
    </div>
    <input type="submit" class="btn btn-primary" value="{tr}Upload{/tr}">
</form>
