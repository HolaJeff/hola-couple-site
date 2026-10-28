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
    timeout: 2000
});

jQuery(function($){

    var form = $('#import-potential-form');

    // init datepickers
    form.find('#wedding-date').datepicker({
        dateFormat: "mm-dd-yy",
        altFormat: "yy-mm-dd",
        altField: '#wedding-date-alt',
    });
    form.find('#start-date').datepicker({
        dateFormat: "mm-dd-yy",
        altFormat: "yy-mm-dd",
        altField: '#start-date-alt',
    });
    form.find('#end-date').datepicker({
        dateFormat: "mm-dd-yy",
        altFormat: "yy-mm-dd",
        altField: '#end-date-alt',
    });
    form.find('#cutoff-date').datepicker({
        dateFormat: "mm-dd-yy",
        altFormat: "yy-mm-dd",
        altField: '#cutoff-date-alt',
    });

    // Validation rules
    var validator = $("#import-potential-form").validate({
        ignore: '*:not([name])',
        debug: true,
        rules: {
            "hwzi_import[wedding_id]": "required",
            "hwzi_import[wedding_name]": "required",
            "hwzi_import[subdomain]": "required",
            "hwzi_import[contact_name]": "required",
            "hwzi_import[spouse_name]" : "required",
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
            }
        },
        messages: {
            "hwzi_import[wedding_id]": "Missing wedding ID",
            "hwzi_import[wedding_name]": "Insert valid wedding name",
            "hwzi_import[subdomain]": "Insert valid subdomain",
            "hwzi_import[contact_name]": "Insert valid contact name",
            "hwzi_import[spouse_name]" : "Insert valid spouse name",
            "hwzi_import[hotel_id]": "Select valid hotel",
            "hwzi_import[wedding_date]": {
                required: "Select a date",
                dateISO: "Select valid wedding date"
            },
            "hwzi_import[start_date]":{
                required: "Select a date",
                dateISO: "Select valid booking start date"
            },
            "hwzi_import[end_date]": {
                required: "Select a date",
                dateISO: "Select valid booking end date"
            },
            "hwzi_import[transfer_fee]": {
                required: "Insert valid trasnfer fee",
                number: "Insert valid number"
            },
        }
    });

    // hook events
    $('#import-potential-btn').on('click', function(){

        hwzi_block_import_form('Validating ...');
        
        if(!validator.form()){
            hwzi_unblock_import_form();
            return;
        }

        hwzi_block_import_form( 'Importing ...');

        $.ajax({
            url: ajaxurl,
            data: form.serialize(),
            method: 'POST',
            contentType: 'application/x-www-form-urlencoded',
            dataType: 'json'
        }).done(function(response) { // Success
            
            if (response.success) {
                
                new Noty({
                    text: 'Site created correctly! Redirecting ...',
                    type: 'success'
                }).show();

                setTimeout(function(){
                    window.location.replace(form.attr('action'));
                }, 3000)
            }else{
                
                new Noty({
                    text: 'Error: ' + response.message,
                    type: 'error'
                }).show();
            }
    
        }).always(function(response) { // Always
    
            hwzi_unblock_import_form();
        });
    });

    /**
     * 
     * Block the form
     * 
     * @param {string} message 
     */
    function hwzi_block_import_form(message){

        blockUI($('#import-potential-form'), message);
    }

    /**
     * unblock the form
     */
    function hwzi_unblock_import_form(){

        unblockUI($('#import-potential-form'));
    }
});