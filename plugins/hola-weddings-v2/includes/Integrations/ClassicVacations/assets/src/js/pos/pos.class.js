class POS{
    constructor(){

        this._invoice = null;
        this.el = jQuery('#cv-pos');
        this.form = this.el.find('form');
        this.quote_field = this.el.find('#pos-quote-field');
        this.invoice_field = this.el.find('#pos-invoice-field');
        this.billing_form = this.el.find('#billing-form');
        this.card_form = this.el.find('#card-form');
        this.submitBtn = this.el.find('#submit');
        this.billing_information = null;

        this.bindEvents();
    }

    bindEvents(){

        var _self = this;

        this.quote_field.on('change', () => { 
            _self.loadInvoices() 
        } );

        this.invoice_field.on('change', () => { 
            _self.loadInvoice() 
        });

        jQuery(document).on('change', 'input[name="amount"]', function(){
        
            if(this.value === 'custom'){
                jQuery('#pos-payment-custom-amount').show();
            }else{
                jQuery('#pos-payment-custom-amount').hide();
            }
        });

        jQuery(document).on( 'click', '#clear-card-fields', () => _self.clearCardForm());
    
        jQuery(document).on( 'click', '#clear-billing-fields', () => _self.clearBillingForm());
    
        jQuery(document).on( 'change', 'input[name="use_saved_card"]', function(){

            if( parseInt(jQuery(this).val()) !== 1 ){

                _self.card_form.children('.fields').show();
            }else{

                _self.card_form.children('.fields').hide();
            }
        });

        jQuery(document).on( 'click', '#pos-load-billing-data', () => _self.loadBillingData() );

        // submit
        this.submitBtn.on( 'click', () => _self.processPayment() );
    }

    /**
     * process the payment 
     * 
     * @returns void
     */
    processPayment(){

        var _self = this;

        if(!this.validator.form()){
            return;
        }

        jQuery.ajax({
            url: ajaxurl,
            dataType : 'json',
            method: 'POST',
            beforeSend: function(){
                _self.block_form();
            },
            data: _self.form.serialize()
        }).done(function(response){

            if(response.success){
                
                _self.loadInvoice();
            }else{

                new Noty({
                    text: response.message,
                    type: 'error'
                }).show();
            }
        }).fail(function(){

            new Noty({
                text: 'An error occurred while processing the payment',
                type: 'error'
            }).show();

        }).always(function(){

            _self.unblock_form();
        });
    }

    /**
     * load invoice data
     * 
     * @returns void
     */
    loadInvoice(){

        var _self = this,
            value = this.invoice_field.val();
 
        if(value === undefined){
            alert('Select an invoice');
            return;
        }

        this.billing_form.hide();
        this.card_form.hide();

        jQuery.ajax({
            url: ajaxurl,
            dataType : 'json',
            method: 'GET',
            beforeSend: function(){
                _self.block_form();
            },
            data: {
                action: 'hw_cv_pos_get_invoice_checkout',
                invoice_id: value
            }
        }).done(function(response){

            if(response.success){
                
                _self.processInvoiceResponse(response.data);
            }else{

                new Noty({
                    text: response.message,
                    type: 'error'
                }).show();
            }
        }).fail(function(){

            new Noty({
                text: 'An error occurred with the request',
                type: 'error'
            }).show();

        }).always(function(){

            _self.unblock_form();
        });
    }

    /**
     * load invoice list after choosing the quote
     * 
     * @returns void
     */
    loadInvoices(){

        var _self = this,
            value = this.quote_field.val();

        if(value === undefined){
            alert('Select a quote');
            return;
        }

        this.invoice_field.html('');

        jQuery.ajax({
			url: ajaxurl,
			dataType : 'json',
			method: 'GET',
			beforeSend: function(){
				_self.invoice_field.prop('disabled', true);
                jQuery('#invoice-checkout').find('.ajax-container').html('');
			},
			data: {
				action: 'hw_cv_pos_get_invoices_by_quote',
                quote_id: value
			}
		}).done(function(response){

			if(response.success){
				
                _self.processQuoteResponse(response.data);
			}else{

				new Noty({
                    text: response.message,
                    type: 'error'
                }).show();
			}
		}).fail(function(){

			new Noty({
                text: 'An error occurred with the request',
                type: 'error'
            }).show();

		});
    }

    /**
     * Process quote response
     * 
     * @param {array} response 
     */
    processQuoteResponse(response){

        if(response.hasOwnProperty('invoice_options')){

            if(response.invoice_options !== ''){

                this.invoice_field.html(response.invoice_options).prop('disabled', false);
            }else{

                this.invoice_field.html('<option>No invoices found</option>');
                
                new Noty({
                    text: 'No invoices found.',
                    type: 'error'
                }).show();
            }

            if(response.card_digits !== ''){
                jQuery('#card-digits').text(response.card_digits);
            }
        }

        if(response.hasOwnProperty('billing_information')){

            this.billing_information = response.billing_information;
        }else{

            this.billing_information = null;
        }

        this.loadBillingData();
    }

    /**
     * process invoice data response
     * 
     * @param {Array} response 
     */
    processInvoiceResponse(response){

        var checkoutAjaxContainer = jQuery('#invoice-checkout').find('.ajax-container');

        checkoutAjaxContainer.html(response.html)

        this._invoice = response.invoice;
        
        this.update();
    }

    /**
     * update the POS
     */
    update(){

        if(this._invoice === null){

            this.card_form.hide();
            this.billing_form.hide();
            this.disableSubmitBtn();
            return;
        }

        if(this._invoice.due_amount <= 0){

            this.disableSubmitBtn();
        }else{

            this.card_form.show();
            this.billing_form.show();
            this.enableSubmitBtn();
        }
    }

    /**
     * return the invoice
     * 
     * @returns Object
     */
    getInvoice(){
        return this._invoice;
    }

    /**
     * return whether the card is 
     * 
     * @returns bool
     */
    useSavedCard(){
        return !this.el.find('#use_saved_card').is(':checked');
    }

    /**
     * block the form
     * 
     * @param {string} message 
     */
    block_form(message){
        blockUI(this.form, message);
    }

    /**
     * unblock the form
     * 
     * @param {Object} el 
     */
    unblock_form(){
        unblockUI(this.form);
    }

    disableSubmitBtn(){

        this.submitBtn.prop('disabled', true);
    }

    enableSubmitBtn(){

        this.submitBtn.prop('disabled', false);
    }

    clearCardForm(){

        clearInputs(this.card_form.find(':input'));
    }

    clearBillingForm(){

        clearInputs(this.billing_form.find(':input'));
    }

    loadBillingData(){

        var _self = this;

        if(this.billing_information === null){
            return;
        }

        jQuery.each(this.billing_information, function( index, value){
            
            switch(index){
                case 'zip':
                    index = 'postal_code';
                    break;
            }

            _self.billing_form.find('#'+index).val(value);
        });
    }
};

export default POS;