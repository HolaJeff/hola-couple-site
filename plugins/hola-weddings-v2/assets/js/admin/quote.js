jQuery(function($){

    /**
     * Calculate age
     * @param {string} dateString 
     * @returns 
     */
    function calc_age(dateString) {
        var today = new Date();
        var birthDate = new Date(dateString);
        var age = today.getFullYear() - birthDate.getFullYear();
        var m = today.getMonth() - birthDate.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        return age;
    }

    /**
     * Billing information
     */
    $(document).on('change', '#country', function(){

		let target = $(this).data('target'),
			country_code = $(this).val();

		if(target.length > 0){
			if(country_code === 'US'){
				$(target).find('.us-states').show().prop('disabled', false);
				$(target).find('.other-state').hide().prop('disabled', true);
			}else{
				$(target).find('.us-states').hide().prop('disabled', true);
				$(target).find('.other-state').show().prop('disabled', false);
			}
		}
    });

	let guests_counter = $('#guests-table').find('tr').length;
	let room_block_id = $('#room-block').val();

	$(document).on('click', '[data-remove-guest]', function(){

		$(this).closest('tr').remove();
	});

	$('[data-add-guest]').on('click', function(){

		guests_counter++;

		let html = $.parseHTML(`
		<tr>
            <td>
                <select class="form-select" name="data[guests][${guests_counter}][title]">
                    <option value="mr">Mr</option>
                    <option value="mrs">Mrs</option>
                    <option value="miss">Miss</option>
                    <option value="mstr">Mstr</option>
                </select>
            </td>
			<td>
				<input class="form-control" type="text" name="data[guests][${guests_counter}][name]" />
			</td>
			<td>
				<input class="form-control guest-age-field" autocomplete="off" type="text" />
                <input type="hidden" name="data[guests][${guests_counter}][date]" />
            </td>
			<td>
				<input class="form-control age-field" type="text" name="data[guests][${guests_counter}][age]" readonly />
			</td>
			<td>
				<button type="button" data-remove-guest class="btn btn-outline-danger"><span class="dashicons dashicons-trash"></span></button>
			</td>
		</tr>
		`);
        
        appendDatepickertoGuest($(html).find('.guest-age-field')[0]);

		$('#guests-table').find('tbody').append(html);
	});

	/*$(document).on('change', '#room-block', function(){

		if($(this).val() == room_block_id){

			$('[data-room-block-recalculate]').hide().find('input').prop('disabled', true);
		}else{

			$('[data-room-block-recalculate]').show().find('input').prop('disabled', false);
		}
	});*/

    // Init checkin date
    $( "#checkin-field" ).datepicker({
        dateFormat: "mm-dd-yy",
        altFormat: "yy-mm-dd",
        altField: '#checkin-alt-field',
        onSelect: function(){
            var date2 = $( this ).datepicker('getDate');
            
            date2.setDate(date2.getDate() + 1);
            
            if($( '#checkout-field' ).datepicker('getDate') == null || ($( "#checkout-field" ).datepicker('getDate') != null && $( "#checkout-field" ).datepicker('getDate') <= $( this ).datepicker('getDate'))){
                
                $( "#checkout-field" ).datepicker('setDate',date2);
            }
            
            $( "#checkout-field" ).datepicker('option', 'minDate', date2);
        },
    });


    // Init checkout datepicker
    $( "#checkout-field" ).datepicker({
        dateFormat: "mm-dd-yy", 
        altFormat: "yy-mm-dd",
        altField: '#checkout-alt-field',
        onSelect: function(){
           if($( this).datepicker('getDate') <= $( "#checkin-field" ).datepicker('getDate')){
               var date2 = $( "#checkin-field" ).datepicker('getDate');
               date2.setDate(date2.getDate()+1);
               $( this).datepicker('setDate',date2);
           }
        },
    });

    $('.guest-age-field').each(function(){

        appendDatepickertoGuest(this);
    });

    function appendDatepickertoGuest(el){
        let altField = $(el).next();

        $(el).datepicker({
            dateFormat: "mm-dd-yy", 
            altFormat: "yy-mm-dd",
            altField: altField,
            onSelect: function(){
               let date = $( el ).datepicker('getDate');
               let day = ( date.getDate() < 10 ? '0' :'') + date.getDate();
               let month = ( ( date.getMonth() + 1 ) < 10 ? '0' :'') + ( date.getMonth()  + 1 );
               let age = calc_age(`${date.getFullYear()}/${month}/${day}`);
               $(el).closest('tr').find('.age-field').val(age);
            },
        });
    }
});