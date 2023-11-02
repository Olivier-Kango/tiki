<script src="lib/pwa/app.js"></script>
<script>
    window.pagespwa = {$pagespwa};
    (function () {
        setTimeout(function () {
            $.loadCache(window.pagespwa);
        }, 500)
    })();
</script>
<div class="pwa-queue"
     style="position: fixed;bottom: 20px;right: 20px;display: flex;flex-direction: column;align-items: flex-end;z-index: 10!important">
    <div style="display: inline-flex; justify-items: right; align-items: baseline;">
        <p style="padding: 10px"><span id="pwa-n-requests"></span> requests</p>
        <button onclick="" id="sync-pwa" class="btn btn-info" href="#">Sync</button>
    </div>
</div>
