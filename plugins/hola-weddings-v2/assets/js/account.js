
Noty.overrideDefaults({
    timeout: 3000
});

document.addEventListener('alpine:init', () => {
    Alpine.data('requestPaymentLinkBtnController', ( invoiceID, nonce ) => ({
        invoiceID,
        nonce,
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
                    // Minted per invoice by the button template; the endpoint
                    // also checks the invoice actually belongs to this user.
                    nonce: this.nonce,
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

    // Sidebar state — shared across the topbar, sidebar and backdrop.
    Alpine.store('accountSidebar', {
        collapsed: false, // desktop icon-rail
        open: false,      // mobile drawer

        init() {
            this.collapsed = localStorage.getItem('hw-account-sidebar-collapsed') === '1';

            // Never leave the drawer open when growing to desktop.
            window.matchMedia('(min-width: 992px)').addEventListener('change', (e) => {
                if (e.matches) this.open = false;
            });
        },

        toggle() {
            if (window.matchMedia('(min-width: 992px)').matches) {
                this.collapsed = !this.collapsed;
                localStorage.setItem('hw-account-sidebar-collapsed', this.collapsed ? '1' : '0');
            } else {
                this.open = !this.open;
            }
        },

        close() {
            this.open = false;
        },
    });
});