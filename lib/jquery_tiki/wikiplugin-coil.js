(function ($) {
    let coilPaywall = $('.coil-paywall');
    let coilWebmonetization = $('.coil-webmonetization');
    let page = $('.pagetitle a').attr('href');

    showPaymentMessage(coilPaywall, coilWebmonetization);

    if (document.monetization) {
        document.monetization.addEventListener('monetizationstart', event => {
            if (!document.monetization.state === 'started') {
                showPaymentMessage(coilPaywall, coilWebmonetization);
            }
        });
        document.monetization.addEventListener('monetizationprogress', event => {
            if (event.detail && event.detail.amount && event.detail.amount > 0) {
                showMonetizedContent(coilPaywall, coilWebmonetization);
            } else {
                showPaymentMessage(coilPaywall, coilWebmonetization);
            }
        });
        document.monetization.addEventListener('monetizationstop', event => {
            showPaymentMessage(coilPaywall, coilWebmonetization);
        });
        document.monetization.addEventListener('monetizationpending', event => {
            showPaymentMessage(coilPaywall, coilWebmonetization);
        });
    }

    function showPaymentMessage(coilPaywall, coilWebmonetization)
    {
        coilPaywall.show();
        if (coilWebmonetization.length > 0) {
            coilWebmonetization.empty();
        }
    }

    function showMonetizedContent(coilPaywall, coilWebmonetization)
    {
        if (coilWebmonetization.html() === '') {
            let url = page.indexOf('?') === -1 ? page + '?getcoildata' : page + '&getcoildata';

            ajaxLoadingShow('page-data');
            $.ajax({
                type: 'GET',
                url: url,
                success: function (data) {
                    let payedData = $(data).find('.coil-webmonetization');
                    let coilWebmonetization = $('.coil-webmonetization');

                    $.each(payedData, function (k, v) {
                        $(coilWebmonetization[k]).html($(v).html());
                    });

                    coilWebmonetization.show();
                    ajaxLoadingHide();

                    coilPaywall.hide();
                }
            });
        }
    }
})(jQuery);
