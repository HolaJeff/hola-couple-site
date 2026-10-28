Noty.overrideDefaults({
    timeout: 2000
});

jQuery(function($){

    var btn = $('#quote-invoices-zoho-import');

    // hook events
    btn.on('click', function(){

        btn.prop('disabled', true).children('.text').text('Importing...');

        $.ajax({
            url: ajaxurl,
            data: {
                action: 'hwzi_import_quote_zoho_payments',
                post_id: btn.data('post-id')
            },
            method: 'POST',
            dataType: 'json'
        }).done(function(response) { // Success
            
            if (response.success) {
                
                new Noty({
                    text: response.message,
                    type: 'success'
                }).show();

                update_quote_metabox_table(btn.data('post-id'));
            }else{
                
                new Noty({
                    text: 'Error: ' + response.message,
                    type: 'error'
                }).show();
            }
    
        }).always(function(response) { // Always
    
            btn.prop('disabled', false).children('.text').text('Import from Zoho');
        });
    });

    /**
     * reload invoices table
     * @param {string} post_id 
     */
    function update_quote_metabox_table(post_id){

        var metabox = $('#invoices'),
            container = $('#quote-invoices-wrapper');

        blockUI(metabox, 'Refreshing...');

        $.ajax({
            url: ajaxurl,
            data: {
                action: 'hwzi_update_quote_invoices_table',
                post_id: post_id
            },
            method: 'GET',
            dataType: 'json'
        }).done(function(response) { // Success
            
            if (response.success) {
                
                container.html(response.content);
            }else{
                
                new Noty({
                    text: 'Error: ' + response.message,
                    type: 'error'
                }).show();
            }
    
        }).always(function(response) { // Always
    
            unblockUI(metabox);
        });
        
    }

    /**
     * Creates a new payment
     */

    $('button[data-create-payment]').on('click', function(){
        
        var btn = $(this);
        btn.prop('disabled', true).addClass('loading').find('.text').text('Creating...');

        $.ajax({
            url: ajaxurl,
            data: {
                action: 'hwzi_export_invoice_to_zoho',
                post_id: btn.data('post-id')
            },
            method: 'POST',
            dataType: 'json'
        }).done(function(response) { // Success
            
            if (response.success) {
                
                new Noty({
                    text: response.message,
                    type: 'success'
                }).show();

                btn.closest('.inside').html(response.html);
            }else{
                
                new Noty({
                    text: 'Error: ' + response.message,
                    type: 'error'
                }).show();
            }
    
        }).fail(function() { // fails

            new Noty({
                text: 'An error occurred while processing the request',
                type: 'error'
            }).show();

            btn.prop('disabled', false).removeClass('loading').find('.text').text('Export to Zoho');
        });
    });
});