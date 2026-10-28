<?php
use \HW\Helpers\Countries;
?>
<h3>Make Payment</h3>
<form id="cv-test-payment" method="get" action="<?= network_admin_url( 'admin.php?page=classic-vacations-tests&tab=make-payment' ) ?>"> 
    <div class="row gx-3">
        <div class="col-5">
            <div class="p-3 bg-white rounded">
                <h3><?= __('Payment Information', 'hola-weddings') ?></h3>
                <div class="row mb-3">
                    <div class="form-group col-9 mb-2">
                        <label for="ref-id" class="form-label"><?= __('Reference ID', 'hola-weddings') ?></label>
                        <input type="text" name="ref_id" value="" class="form-control d-inline-block" id="ref-id" placeholder="<?= __('Insert Ref ID', 'hola-weddings') ?>">
                    </div>
                    <div class="form-group col-4">
                        <label for="group-id" class="form-label"><?= __('Group ID', 'hola-weddings') ?></label>
                        <input type="text" name="group_id" value="54552" class="form-control d-inline-block" id="group-id" placeholder="<?= __('Insert Group ID', 'hola-weddings') ?>">
                    </div>
                    <div class="form-group col-4">
                        <label for="booking-id" class="form-label"><?= __('Booking ID', 'hola-weddings') ?></label>
                        <input type="text" name="booking_id" value="1" class="form-control d-inline-block" id="booking-id" placeholder="<?= __('Insert Booking ID', 'hola-weddings') ?>">
                    </div>
                    <div class="form-group col-4">
                        <label for="amount" class="form-label"><?= __('Amount', 'hola-weddings') ?></label>
                        <input type="text" name="amount" value="1" class="form-control d-inline-block" id="amount" placeholder="<?= __('Insert Amount', 'hola-weddings') ?>">
                    </div>
                    <div class="form-group col-4">
                        <label for="currency" class="form-label"><?= __('Amount', 'hola-weddings') ?></label>
                        <input type="text" name="currency" value="USD" class="form-control d-inline-block" id="currency" placeholder="<?= __('Insert Currency', 'hola-weddings') ?>">
                    </div>
                </div>
            </div>
        </div>
        <div class="col-5">
            <div class="p-3 bg-white rounded">
                <h3><?= __('Card Information', 'hola-weddings') ?></h3>
                <div class="row mb-3">
                    <div class="form-group col-11 mb-2">
                        <label for="c-holder" class="form-label"><?= __('Card Holder Name', 'hola-weddings') ?></label>
                        <input type="text" name="card[holder]" value="Jean Francois Lanno" class="form-control d-inline-block" id="c-holder" placeholder="<?= __('Insert Card Holder Name', 'hola-weddings') ?>">
                    </div>
                    <div class="form-group col-8">
                        <label for="c-number" class="form-label"><?= __('Card Number', 'hola-weddings') ?></label>
                        <input type="text" name="card[number]" value="4520885019956041" class="form-control d-inline-block" id="c-number" placeholder="<?= __('Insert Card Number', 'hola-weddings') ?>">
                    </div>
                    <div class="form-group col-1">
                        <label for="e_month" class="form-label"><?= __('Month', 'hola-weddings') ?></label>
                        <input type="number" name="card[month]" value="02" class="form-control d-inline-block" id="e_month" placeholder="<?= __('MM', 'hola-weddings') ?>">
                    </div>
                    <div class="form-group col-1">
                        <label for="e_year" class="form-label"><?= __('Year', 'hola-weddings') ?></label>
                        <input type="number" name="card[year]" value="24" class="form-control d-inline-block" id="e_year" placeholder="<?= __('YYYY', 'hola-weddings') ?>">
                    </div>
                    <div class="form-group col-2 mt-2">
                        <label for="c_ccv" class="form-label"><?= __('CCV', 'hola-weddings') ?></label>
                        <input type="number" name="card[ccv]" value="059" class="form-control d-inline-block" id="c_ccv" placeholder="<?= __('123', 'hola-weddings') ?>">
                    </div>
                    <div class="form-group col-11">
                        <label class="form-label d-block mt-2"><?= __('Card Type', 'hola-weddings') ?></label>
                        <div class="form-check form-check-inline">
                            <input type="radio" name="card[type]" value="001" class="form-check-input float-none" id="visa" checked>
                            <label for="visa" class="form-check-label align-top"><?= __('Visa', 'hola-weddings') ?></label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input type="radio" name="card[type]" value="002" class="form-check-input float-none" id="mastercard">
                            <label for="mastercard" class="form-check-label align-top"><?= __('MasterCard', 'hola-weddings') ?></label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input type="radio" name="card[type]" value="003" class="form-check-input float-none" id="amex">
                            <label for="amex" class="form-check-label align-top"><?= __('AMEX', 'hola-weddings') ?></label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input type="radio" name="card[type]" value="004" class="form-check-input float-none" id="discover">
                            <label for="discover" class="form-check-label align-top"><?= __('Discover', 'hola-weddings') ?></label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input type="radio" name="card[type]" value="005" class="form-check-input float-none" id="diners">
                            <label for="diners" class="form-check-label align-top"><?= __('Diners Club', 'hola-weddings') ?></label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-5 mt-3">
            <div class="p-3 bg-white rounded">
                <h3><?= __('Billing Information', 'hola-weddings') ?></h3>
                <div class="row">
                    <div class="form-group col-5 mb-2">
                        <label for="first_name" class="form-label"><?= __('First Name', 'hola-weddings') ?></label>
                        <input type="text" name="billing[first_name]" value="Jean Francois" class="form-control d-inline-block" id="first_name" placeholder="<?= __('First Name', 'hola-weddings') ?>">
                    </div>
                    <div class="form-group col-5 mb-2">
                        <label for="last_name" class="form-label"><?= __('Last Name', 'hola-weddings') ?></label>
                        <input type="text" name="billing[last_name]" value="Lanno" class="form-control d-inline-block" id="last_name" placeholder="<?= __('Last Name', 'hola-weddings') ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-10 mb-2">
                        <label for="email" class="form-label"><?= __('Email', 'hola-weddings') ?></label>
                        <input type="text" name="billing[email]" value="jeff@holaweddings.com" class="form-control d-inline-block" id="first_name" placeholder="<?= __('Email', 'hola-weddings') ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-8 mb-2">
                        <label for="address1 " class="form-label"><?= __('Address', 'hola-weddings') ?></label>
                        <input type="text" name="billing[address1]" value="912 rue Bellerive" class="form-control d-inline-block" id="address1 " placeholder="<?= __('Address', 'hola-weddings') ?>">
                    </div>
                    <div class="form-group col-2 mb-2">
                        <label for="postal_code" class="form-label"><?= __('Postal Code', 'hola-weddings') ?></label>
                        <input type="text" name="billing[postal_code]" value="J4j 1A7" class="form-control d-inline-block" id="postal_code" placeholder="<?= __('Postal Code', 'hola-weddings') ?>">
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-10 form-group">
                        <label for="country" class="form-label"><?= __('Country', 'hola-weddings') ?></label>
                        <select id="country" type="text" class="form-select" placeholder="<?= __('Country', 'hola-weddings') ?>" name="billing[country]" >
                            <optgroup label="<?= __('Most Used', 'hola-weddings') ?>">
                                <option value="US">United States</option>
                            </optgroup>
                            <optgroup label="<?= __('All Countries', 'hola-weddings') ?>">
                                <?php foreach(Countries::getList() as $country):
                                    // skip us
                                    if($country['code'] == 'US'){
                                        continue;
                                    }
                                    ?>
                                    <option value="<?= $country['code'] ?>" <?= $country['code'] == 'CA' ? 'selected' : '' ?>><?= $country['name'] ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-5 mb-2">
                        <label for="city" class="form-label"><?= __('City', 'hola-weddings') ?></label>
                        <input type="text" name="billing[locality]" value="Longueuil" class="form-control d-inline-block" id="city" placeholder="<?= __('City', 'hola-weddings') ?>">
                    </div>
                    <div class="form-group col-5 mb-2">
                        <label for="state" class="form-label"><?= __('State', 'hola-weddings') ?></label>
                        <input type="text" name="billing[administrative_area]" value="QC" class="form-control d-inline-block" id="last_name" placeholder="<?= __('State', 'hola-weddings') ?>">
                        <div class="form-text">
                            Use 2 letters state code
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <button class="btn btn-primary mt-3"><?= __('Make Payment', 'hola-weddings') ?></button>
    <input type="hidden" name="action" value="hw_cv_process_test_payment" />
</form>
<script>
jQuery.validator.setDefaults({
    debug: true,
    errorElement: 'span',
    errorPlacement: function (error, element) {
        error.addClass('invalid-feedback');
        element.parent().append(error);
    },
    highlight: function (element, errorClass, validClass) {
        jQuery(element).addClass('is-invalid');
    },
    unhighlight: function (element, errorClass, validClass) {
        jQuery(element).removeClass('is-invalid');
    }
});

Noty.overrideDefaults({
    timeout: 10000
});

jQuery(function($){

    var form = $('#cv-test-payment');

    // Validation rules
   var validator = form.validate({
        ignore: '*:not([name])',
        debug: true,
        rules: {
            "ref_id": "required",
            /*"group_id": "required",
            "booking_id": "required",
            "amount": "required",
            "currency" : "required",
            "hwzi_import[hotel_id]": "required",
            "hwzi_import[wedding_date]": {
                required: true,
                dateISO: true
            },
            "hwzi_import[start_date]": {
                required: true,
                dateISO: true
            },
            "hwzi_import[end_date]": {
                required: true,
                dateISO: true
            },
            "hwzi_import[transfer_fee]": {
                required: true,
                number: true
            }*/
        },
    });

    // hook events
    form.on('submit', function(e){

        e.preventDefault();

        block_form('Validating ...');
        
        if(!validator.form()){
            unblock_form();
            return;
        }

        block_form( 'Processing ...');

        $.ajax({
            url: ajaxurl,
            data: $(this).serialize(),
            method: 'POST',
            contentType: 'application/x-www-form-urlencoded',
            dataType: 'json'
        }).done(function(response) { // Success
            
            if (response.success) {
                
                new Noty({
                    text: response.message,
                    type: 'success'
                }).show();
            }else{
                
                new Noty({
                    text: 'Error: ' + response.message,
                    type: 'error'
                }).show();
            }
    
        }).fail(function(response){
            
            if(response.responseJSON.message){
                new Noty({
                    text: 'Error: ' + response.responseJSON.message,
                    type: 'error'
                }).show();
            }
        }).always(function(response) { // Always
    
            unblock_form();
        });

        return false;
    });

    /**
     * 
     * Block the form
     * 
     * @param {string} message 
     */
    function block_form(message){

        blockUI(form, message);
    }

    /**
     * unblock the form
     */
    function unblock_form(){

        unblockUI(form);
    }
});
</script>