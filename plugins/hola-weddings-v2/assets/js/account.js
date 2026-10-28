
Noty.overrideDefaults({
    timeout: 3000
});

document.addEventListener('alpine:init', () => {
    Alpine.data('requestPaymentLinkBtnController', ( invoiceID ) => ({
        invoiceID,
        loading: false,

        request() {

            if (this.loading) return;

            if (!this.invoiceID) {

                console.error('Missing invoiceID');
                return;
            }

            this.loading = true;

            jQuery.ajax({
                url: ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'hw_send_email_request_invoice_payment_link',
                    invoice_id: this.invoiceID,
                },
            }).done((response) => {

                if (response.success) {

                    let $modal = jQuery('#invoicePaymentMessageModal');

                    if($modal.length == 0){

                        console.alert('Missing #invoicePaymentMessageModal modal');
                        return;
                    }

                    // Tell the modal to show, and what to display
                    $modal.modal('show');
                } else {
                    // You can also show a modal for errors if you want
                    new Noty({
                        text: response?.data?.message || response?.message || 'Request failed',
                        type: 'error'
                    }).show();
                }
            }).fail(() => {

                new Noty({
                    text: 'Request failed',
                    type: 'error'
                }).show();
            }).always(() => {

                this.loading = false;
            });
        },
    }));
});

(function ($) {

    'use strict';

    
    function init() {

        $('#leftside-menu-btn').on('click', function(){
            $('body').toggleClass('leftside-open');
        });

        $('#leftside-close-btn').on('click', function(){
            $('body').removeClass('leftside-open');
        });
    }

    init();

})(jQuery)