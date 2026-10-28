import eSignature from "../../Form/Signature/Signature";
import TermsConditions from "../../Form/TermsConditions/TermsConditions";
import { clearInputs, format_card } from "../../Utils";
import ErrorAlert from "./erroralert.class";
import UIBlocker from "./uiblocker.class";

class POS{

    constructor(){

        this._invoice = null;
        this.el = jQuery('#pos');
        this.form = this.el.find('form');
        this.billing_form = this.el.find('#billing-form');
        this.card_form = this.el.find('#card-form');
        this.submitBtn = this.el.find('#submit');
        this.errorAlert = new ErrorAlert(this);
        this.uiblocker = new UIBlocker();
        this.disableRedirectEl = this.el.find('#disable_redirect');
        this.disableFormValidationEl = this.el.find('#skip_form_validation');
        this.eSignature = new eSignature(this.el.find('#signature')[0]);
        this.termConditionsInput = this.el.find('#terms_and_conditions');
        this.termConditionsInput.instance = new TermsConditions(this.termConditionsInput);

        // vars
        this.disableFormValidation = false;
        this.disableRedirect = false;

        this.bindEvents();

        this.submitBtn.removeAttr('disabled');
        console.log('POS initialized');
    }

    bindEvents(){

        var _self = this;

        this.el.on( 'click', '#clear-card-fields', () => _self.clearCardForm());
    
        this.el.on( 'click', '#clear-billing-fields', () => _self.clearBillingForm());
    
        this.el.on( 'change', 'input[name="use_saved_card"]', function(){

            if( parseInt(jQuery(this).val()) !== 1 ){

                _self.card_form.children('.fields').show();
            }else{

                _self.card_form.children('.fields').hide();
            }
        });

        this.disableFormValidationEl.on('change', function(){
            _self.disableFormValidation = jQuery(this).is(':checked');
        });

        this.disableRedirectEl.on('change', function(){
            _self.disableRedirect = jQuery(this).is(':checked');
        });

        jQuery(document).on( 'click', '#pos-load-billing-data', () => _self.loadBillingData() );

        // submit
        this.submitBtn.on( 'click', () => _self.processPayment() );

        // Card Form
        jQuery(document).on('change', '.card-type', function(){

            var ccvEl = _self.card_form.find('#card-ccv'),
                cardEl = _self.card_form.find('#card-number'),
                ccvMax = _self.card_form.find('.card-type:checked').val() == '003' ? 3 : 2,
                cardMax = _self.card_form.find('.card-type:checked').val() == '003' ? 14 : 15;

            if(jQuery(this).val() == '003'){
                ccvEl.prop('max', 9999);
            }else{
                ccvEl.prop('max', 999);
            }

            let ccvVal = ccvEl.val();
            if(ccvVal.length >= ccvMax){
                ccvEl.val(ccvVal.substring(0, ccvMax+1));
            }

            let cardVal = cardEl.val().split("-").join("");
            if(cardVal.length >= cardMax){
                cardEl.val(format_card(cardVal.substring(0, cardMax+1)));
            }
        });

        this.card_form.find('#card-number').on('keypress', function(evt){
            
            let el = jQuery(this),
                val = el.val().split("-").join(""),
                max = _self.card_form.find('.card-type:checked').val() == '003' ? 14 : 15;
            
            evt = (evt) ? evt : window.event;
            var charCode = (evt.which) ? evt.which : evt.keyCode;
            if (charCode > 31 && (charCode < 48 || charCode > 57)) {
                return false;
            }
                
            if(val.length > max){
                return false;
            }
        });
        
        this.card_form.find('#card-number').on('keyup', function(){
            
            let el = jQuery(this),
                val = el.val().split("-").join("");
            
            if(val.length <= 0){
                return false;
            }
            
            jQuery(this).val(format_card(val));
        });
        
        this.card_form.find('#card-month').on('keypress', function(){
            
            let el = jQuery(this),
                val = el.val();
                
            if(val.length > 1){
                return false;
            }
        });
        
        this.card_form.find('#card-year').on('keypress', function(){
            
            let el = jQuery(this),
                val = el.val();
                
            if(val.length > 3){
                return false;
            }
        });
        
        this.card_form.find('#card-ccv').on('keypress', function(){
            
            let el = jQuery(this),
                val = el.val(),
                max = _self.card_form.find('.card-type:checked').val() == '003' ? 3 : 2;
                
            if(val.length > max){
                return false;
            }
        });
    }

    /**
     * process the payment 
     * 
     * @returns void
     */
    processPayment(){

        var _self = this;

        if( this.disableFormValidation == false && !this.validator.form()){
            return;
        }

        jQuery.ajax({
            url: ajax.ajax_url,
            dataType : 'json',
            method: 'POST',
            beforeSend: function(){
                _self.uiblocker.show();
            },
            data: _self.form.serialize()
        }).done(function(response){

            _self.processPaymentResponse(response);
        }).fail(function(){

            _self.displayError('An error occurred while processing the payment');
            _self.uiblocker.close();

        });
    }

    /**
     * Process the response
     * @param {Object} response 
     */
    processPaymentResponse(response){

        if(response.success){

            // avoid to redirect if disabled
            if(!this.disableRedirect){
                let redirect_url = response.redirect ? response.redirect : hw_pos.reservation_url;
                setTimeout(() => {
                    window.location.href = redirect_url;
                }, 2000);
            }else{
                this.uiblocker.close();
            }
        }else{

            this.displayError(response.error.message);
            this.uiblocker.close();

            if(response.reload){
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
        }
    }

    /**
     * display the error message above the form
     */
    displayError(message){

        this.errorAlert.setMessage(message).show();
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

        clearInputs(this.card_form.find('.fields :input:not(.card-type)'));
    }

    clearBillingForm(){

        clearInputs(this.billing_form.find(':input'));
    }

    loadBillingData(){

        var _self = this;

        if(billing_information === null || billing_information === undefined){
            return;
        }

        jQuery.each(billing_information, function( index, value){
            
            switch(index){
                case 'zip':
                    index = 'postal_code';
                    break;
            }

            _self.billing_form.find('#'+index).val(value);
        });
    }

    hasSignature(){
        return this.eSignature.hasSignature();
    }

    termConditionsAccepted(){
        return this.termConditionsInput.instance.isAccepted();
    }
};

export default POS;