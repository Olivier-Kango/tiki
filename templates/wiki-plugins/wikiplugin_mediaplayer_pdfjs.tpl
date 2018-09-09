{strip}
{if !$pdfJsAvailable}
    {remarksbox type=error title="{tr}Missing Package{/tr}" close="n"}
        {tr}To view pdf files Tiki needs npm-asset/pdfjs-dist package.{/tr}
        {tr}Please contact the Administrator to install it.{/tr}
    {/remarksbox}
{else}
    <div>
        <a class="btn btn-default btn-sm" data-role="button" id="prev">{tr}Previous{/tr}</a>
        <a class="btn btn-default btn-sm" data-role="button" id="next">{tr}Next{/tr}</a>
        <span class="float-sm-right small">{tr}Page{/tr}: <span id="page_num"></span> / <span id="page_count"></span></span>
    </div>
    <div>
        <canvas id="pdf-canvas" style="border:1px solid gray" class="col-12"></canvas>
    </div>

{jq}
    // If absolute URL from the remote server is provided, configure the CORS
    // header on that server.
    var url = '{{$url}}';

    var pdfDoc = null,
        pageNum = 1,
        pageRendering = false,
        pageNumPending = null,
        scale = 1,
        canvas = document.getElementById('pdf-canvas'),
        ctx = canvas.getContext('2d');

    /**
     * Get page info from document, resize canvas accordingly, and render page.
     * @param num Page number.
     */
    function renderPage(num) {
        pageRendering = true;
        // Using promise to fetch the page
        pdfDoc.getPage(num).then(function(page) {
            var viewport = page.getViewport(scale);
            canvas.height = viewport.height;
            canvas.width = viewport.width;

            // Render PDF page into canvas context
            var renderContext = {
                canvasContext: ctx,
                viewport: viewport
            };
            var renderTask = page.render(renderContext);

            // Wait for rendering to finish
            renderTask.promise.then(function() {
                pageRendering = false;
                if (pageNumPending !== null) {
                    // New page rendering is pending
                    renderPage(pageNumPending);
                    pageNumPending = null;
                }
            });
        });

        // Update page counters
        document.getElementById('page_num').textContent = num;
    }

    /**
     * If another page rendering in progress, waits until the rendering is
     * finised. Otherwise, executes rendering immediately.
     */
    function queueRenderPage(num) {
        if (pageRendering) {
            pageNumPending = num;
        } else {
            renderPage(num);
        }
    }

    /**
     * Displays previous page.
     */
    function onPrevPage() {
        if (pageNum <= 1) {
            return;
        }
        pageNum--;
        queueRenderPage(pageNum);
    }
    document.getElementById('prev').addEventListener('click', onPrevPage);

    /**
     * Displays next page.
     */
    function onNextPage() {
        if (pageNum >= pdfDoc.numPages) {
            return;
        }
        pageNum++;
        queueRenderPage(pageNum);
    }
    document.getElementById('next').addEventListener('click', onNextPage);

    /**
     * Asynchronously downloads PDF.
     */
    pdfjsLib.getDocument(url).then(function(pdfDoc_) {
        pdfDoc = pdfDoc_;
        document.getElementById('page_count').textContent = pdfDoc.numPages;

        // Initial/first page rendering
        renderPage(pageNum);
    });
{/jq}

{/if}
{/strip}
