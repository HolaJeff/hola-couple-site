Noty.overrideDefaults({
    timeout: 2000
});

// set alpine state
document.addEventListener('alpine:init', () => {
    Alpine.data('settingsController', () => ({
        invoicePaymentBtnAction: null,
        forceQuoteStatus: false,
        enableInventory: false,
        enableAdditionalNotes: false,
        isTransferEnabled: false,
        enableBasicPrice: false,
        enablePaymentForm: false,
        depositType: null,
        transferMultiplier: null,
        hasCeremony: false,
        hasCocktail: false,
        hasReception: false,
        isTeenEnabled: false,
        enableGoogleLogin: false,

        init() {
            this.invoicePaymentBtnAction = this.$el.querySelector('[name="hw_invoice_payment_btn_action"]')?.value;
            this.depositType = this.$el.querySelector('[name="hw_deposit_type"]')?.value;
            this.transferMultiplier = this.$el.querySelector('[name="hw_transfer_multiplier"]')?.value;
            
            this.forceQuoteStatus = !!this.$el.querySelector('[name="hw_force_quote_status"]')?.checked;
            this.enableInventory = !!this.$el.querySelector('[name="hw_enable_inventory"]')?.checked;
            this.enableAdditionalNotes = !!this.$el.querySelector('[name="hw_enable_additional_notes"]')?.checked;
            this.isTransferEnabled = !!this.$el.querySelector('[name="hw_has_transfer"]')?.checked;
            this.isTeenEnabled = !!this.$el.querySelector('[name="hw_enable_teen"]')?.checked;
            this.enableGoogleLogin = !!this.$el.querySelector('[name="hw_enable_google_login"]')?.checked;
            this.enableBasicPrice = !!this.$el.querySelector('[name="hw_enable_basic_price"]')?.checked;
            this.enablePaymentForm = !!this.$el.querySelector('[name="hw_enable_payment"]')?.checked;
            this.hasCeremony = !!this.$el.querySelector('[name="hw_events[ceremony][active]"]')?.checked;
            this.hasCocktail = !!this.$el.querySelector('[name="hw_events[cocktail][active]"]')?.checked;
            this.hasReception = !!this.$el.querySelector('[name="hw_events[reception][active]"]')?.checked;
        }
    }))
})

jQuery( function($) {
    $( "#hw_date, #hw_start_date, #hw_end_date, #hw_deposit_loss_date, #hw_2n_deposit_loss_date, #hw_not_refundable_date, .calendar, #new-rule-from, #new-rule-to" ).datepicker({
        dateFormat: "yy-mm-dd",
    });
    
    $('#room-list').sortable({
        handle: '.handler',
        items: '> .room'
    });
    
    $('#hw_hotel_id').on('change', function(){
        
        var id = $(this).val();
        
        $.ajax({
            url: ajaxurl,
            method: 'post',
            data:{
                hotel_id: id,
                action: 'get_hotel_rooms'
            }
        }).done(function(response){
            
            if(response.success){
                $('#rooms-container').html(response.data);
            }else{
                alert(response.message);
            }
        });
    });
    
	/**
	 * Set the last clicked tab
	 */
    $('.nav-tabs .nav-link').on('click', function(){
	   Cookies.set('last-tab', $(this).data('bs-target'))
    });

	/**
	 * Init functions
	 */
    function init(){
        activateTab();
    }
    
	/**
	 * Activate the last tab on load
	 */
    function activateTab(){
        let last_tab = Cookies.get('last-tab')
        if(last_tab != ''){
            $('.nav-link[data-bs-target="'+last_tab+'"').tab('show');
        }
    }
    
	/**
	 * triggers room import in settings
	 */
	$('[data-hw-api="import-room"').click(function(){

		var el = $(this),
			id = el.data('room-id');

		var handleError = function(){
			// restore the button
			el.toggleClass('loading').prop('disabled', false)[0].firstChild.textContent = 'Import';
		}

		// show the spinner and change the text
		$(this).toggleClass('loading').prop('disabled', true)[0].firstChild.textContent = 'Importing...';
		
		$.ajax({
            url: ajaxurl,
            method: 'post',
            data:{
                room_id: id,
                action: 'hw_import_room'
            }
        }).done(function(response){
            
            if(response.success){
                new Noty({
                    text: 'Room created!',
                    type: 'success'
                }).show();
				el.parent().html(`<a href="${response.editLink}">Edit Room Block</a>`)
            }else{
                new Noty({
                    text: 'Error: ' + response.message,
                    type: 'error'
                }).show();
				handleError();
            }
        }).fail(handleError);
	});

	$('#settings-form').on('submit', function(e){

		e.preventDefault();

		var $form = $(this);

		const blockForm = function(message){

			blockUI($form, message);
		}

		const unlockForm = function(){
	
			unblockUI($form);
		}
		
		blockForm('Saving ...');

        $.ajax({
            url: ajaxurl,
            data: $form.serialize(),
            method: 'POST',
            contentType: 'application/x-www-form-urlencoded',
            dataType: 'json'
        }).done(function(response) { // Success
            
            if (response.success) {
                
                new Noty({
                    text: 'Settings Saved!',
                    type: 'success'
                }).show();
            }else{
                
                new Noty({
                    text: 'Error: ' + response.message,
                    type: 'error'
                }).show();
            }
    
        }).always(function(response) { // Always
    
            unlockForm();
        });
	});
    
    init();
} );