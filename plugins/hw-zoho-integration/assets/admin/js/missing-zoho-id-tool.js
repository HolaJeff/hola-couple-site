jQuery(function($){

    // Init checkin date
    $( "#date-filter" ).datepicker({
        dateFormat: "mm-dd-yy",
        altFormat: "yy-mm-dd",
        altField: '#date-filter-alt',
    });

    // hook events
    $('#missing-zoho-id-tool form').on('submit', function(e){

        e.preventDefault();

        var form = $(this),
            tool = form.parent(),
            resultContainer = tool.find('[data-results]'),
            controlsToDisable = form.find('input,button'); 

        $.ajax({
            url: ajaxurl,
            data: form.serialize(),
            processData: false,
            method: 'GET',
            dataType: 'json',
            beforeSend: () => {
                resultContainer.html('Loading data ...');
                controlsToDisable.prop('disabled', true);
            }
        }).done(function(response) { // Success
            
            if (response.success) {
                
                resultContainer.html(response.html);
            }else{
                
                new Noty({
                    text: 'Error: ' + response.message,
                    type: 'error'
                }).show();
            }
    
        }).always(function(response) { // Always
    
            controlsToDisable.prop('disabled', false);
        });
    });

    $(document).on('submit', '#missing-zoho-id-tool .update-zoho-id-form', function(e){

        e.preventDefault();

        var form = $(this),
            input = form.find('input[name="zoho_id"]'),
            value = input.val(),
            controlsToDisable = form.find('input,button'); 

        if(value.trim() === ''){
            input.addClass('is-invalid');
            return;
        }else{
            input.removeClass('is-invalid');
        }

        $.ajax({
            url: ajaxurl,
            data: form.serialize(),
            processData: false,
            method: 'POST',
            dataType: 'json',
            beforeSend: () => {
                controlsToDisable.prop('disabled', true);
            }
        }).done(function(response) { // Success
            
            if (response.success) {

                new Noty({
                    text: response.message,
                    type: 'success'
                }).show();

                input.addClass('is-valid')
            }else{
                
                new Noty({
                    text: 'Error: ' + response.message,
                    type: 'error'
                }).show();
            }
    
        }).always(function(response) { // Always
    
            controlsToDisable.prop('disabled', false);
        });
    });
});