var pdfUrl='';
var bindCalls=0;
var ajaxCalls=0;

function storeSortTable(tableName, tableElem)
{
    if (typeof tableElem != 'object' || !tableElem.jquery) {
        tableElem = $(tableElem);
    }
    if (tableElem.length == 0) { // Elem not available
        return '';
    }

    // will make sure we have value / selections for the fields when we extract the html
    tableElem.find('[type=text], textarea, input').each(function () {
        this.defaultValue = this.value;
    });
    tableElem.find('[type=checkbox], [type=radio]').each(function () {
        this.defaultChecked = this.checked;
    });
    tableElem.find('select option').each(function () {
        this.defaultSelected = this.selected;
    });

    let tableHTML = tableElem.html();
    if (typeof tableHTML === "undefined") {
        return;
    }

    // remove filtered rows from the clone
    let elems = $(tableHTML);
    elems.find('tr.filtered').remove();

    // Remove the form inputs, leave only the text, was causing timeout on mpdf
    elems.find('tr.tablesorter-filter-row td input.dateRange:hidden').remove();
    elems.find('tr.tablesorter-filter-row td label').each(function () {
        let e = $(this);
        e.replaceWith(' ' + e.html() + ' ');
    });
    elems.find('tr.tablesorter-filter-row td input, tr.tablesorter-filter-row td select').each(function () {
        let e = $(this);
        e.replaceWith(' ' + e.val() + ' ');
    });

    // convert back to string
    tableHTML = '';
    elems.each(function () {
        tableHTML += this.outerHTML;
    });

    // reduce string size by removing extra spaces, the size causes slowness on xss filtering with some libpcre versions
    tableHTML = tableHTML.replace(/\s*(<\/{0,1}(?:thead|tbody|tr|th|td)(?:| [^>]*)>)\s*/igm, '$1');
    tableHTML = tableHTML.replace(/\s*(\s<\/{0,1}(?:p|br|div|i|b)(?:| [^>]*)>)/igm, '$1');
    tableHTML = tableHTML.replace(/(<\/{0,1}(?:p|br|div|i|b)(?:| [^>]*)>\s)\s*/igm, '$1');

      bindCalls++;
      if($('.icon-pdf').parent().attr('href')!="#")
      {
          pdfUrl=$('.icon-pdf').parent().attr('href');
          $('.icon-pdf').parent().attr('href',"#");

      }
      //calling ajax function to store tableHTML in case of pdf button is clicked
      //this is the ajax action once the confirm submit button is clicked;
      tableName=tableName.replace("table#","#");

      tableHTML=tableHTML.replace("on<x>click=","");
    $.ajax({
        type: 'POST',
        url: 'tiki-ajax_services.php',
        dataType: 'json',
        data: {
                controller: 'pdf',
                action: 'storeTable',
                tableName:tableName,
                tableHTML:tableHTML

        },
        success: function (data) {
        $('#tikifeedback').hide();
                    ajaxCalls++;
                if(ajaxCalls==bindCalls)
                { 
                  window.location.href=pdfUrl;
                  $('.icon-pdf').parent().attr('href',pdfUrl);
                }


        }
    });
    }
    var interval;
    function checkPDFFile(){
        $.ajax({
            type: 'POST',
            url: 'tiki-ajax_services.php',
            dataType: 'json',
            data: {
                controller: 'pdf',
                action: 'checkPDFFile',
            },
            success: function (data) {
                if(data!=false) {
                    setTimeout(function() {$('#pdfinfo').fadeOut('slow')}, data);
                    clearInterval(interval);
                }
            }
        });
    }

    //binding spinner call
    $('.icon-pdf').parent().bind( "click", function() {
        //show spinner
        $('#pdfinfo').fadeIn();
        interval=setInterval(checkPDFFile, 3000);
    });