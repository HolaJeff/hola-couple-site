jQuery(function($){

	$('#wp-admin-bar-hw_clear_hotel_cache a').on('click', function(e){

		e.preventDefault();

		var btn = jQuery(this),
			parentBtn = btn.closest('.ab-sub-wrapper').prev();
	
		jQuery.ajax({
			url: ajaxurl,
			dataType : 'json',
			method: 'POST',
			beforeSend: function(){
				parentBtn.text('Refreshing hotel cache...');
			},
			data: {
				action: 'hw_refresh_hotel_cache',
			}
		}).done(function(response){
	
			if(response.success){
				
				alert('Hotel cache refreshed!');
			}else{
	
				log( 'Error: ' + response.message );
			}
	
		}).always(function(){
	
			parentBtn.text('Cache');
		});
	})

	$('#wp-admin-bar-hw_clear_rooms_cache a').on('click', function(e){

		e.preventDefault();
		
		var btn = jQuery(this),
			parentBtn = btn.closest('.ab-sub-wrapper').prev();
	
		jQuery.ajax({
			url: ajaxurl,
			dataType : 'json',
			method: 'POST',
			beforeSend: function(){
				parentBtn.text('Clearing rooms cache...');
			},
			data: {
				action: 'hw_clear_rooms_cache',
			}
		}).done(function(response){
	
			if(response.success){
				
				alert('Rooms cache deleted!');
			}else{
	
				log( 'Error: ' + response.message );
			}
	
		}).always(function(){
	
			parentBtn.text('Cache');
		});
	})

	$('#refresh-hotel-data').on('click', function(){

		var btn = $(this),
			btn_label = btn.html();

		jQuery.ajax({
			url: ajaxurl,
			dataType : 'json',
			method: 'POST',
			beforeSend: function(){
				btn.text('Updating...');
			},
			data: {
				action: 'hw_update_hotel',
			}
		}).done(function(response){

			if(response.success){
				
				alert('Hotel information updated!');
			}else{

				log( 'Error: ' + response.message );
			}

		}).always(function(){

			btn.html(btn_label);
		});
	});

	$('[data-refresh-permalinks]').on('click', function(){

		var btn = $(this);

		jQuery.ajax({
			url: ajaxurl,
			dataType : 'json',
			method: 'POST',
			beforeSend: function(){
				btn.prop('disabled', true).children('span').html('Flushing permalinks...');
			},
			data: {
				action: 'hw_network_refresh_permalinks',
			}
		}).done(function(response){

			if(response.success){
				
				new Noty({
                    text: 'Permalinks flushed. ' + response.message,
                    type: 'success'
                }).show();
			}else{

				new Noty({
                    text: 'Error: ' + response.message,
                    type: 'error'
                }).show();
			}

		}).always(function(){

			btn.prop('disabled', false).children('span').html('Flush Permalinks');
		});
	});

});

