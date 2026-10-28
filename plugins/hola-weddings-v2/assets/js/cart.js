
"use strict"

var HWCart = window.HWCart || ( function( $ ) {
    
    var app = {
        
        init: function(){
            
            this.wedding_date = hw.wedding_date ?? '';
            this.adults = parseInt( $('#adults-selector').val() );
            this.teen = $('#teens-selector').length > 0 ? parseInt( $('#teens-selector').val() ) : 0 ;
            this.children = $('#child-selector').length > 0 ? parseInt( $('#child-selector').val() ) : 0;
            this.infants = $('#infants-selector').length > 0 ? parseInt( $('#infants-selector').val() ) : 0;
            this.room_list =  $('.room-list-selector');
            this.insurance_selector = $('#insurance-plan');
            this.insurance_mode = this.insurance_selector.data('mode');
            this.insurance_plans = this.insurance_selector.data('plans');
            this.cart = {
                items: [],
                total: 0
            };
            this.service_fee = 0;
            this.rooms = this.room_list.find('.room');
            this.is_room_available = false;
            this.insurance_total = 0;
            this.require_signature = true;
            this.tc_read = false;
            this.tc_accepted = false;
            this.mobileSummaryOpen = false;
            
            this.actions();
            this.initCart();
            this.addEventListeners();
        },

        addEventListeners: function(){

            var _self = this;

            $('#termsConditionsModal .modal-body').on('scroll', function(){

                if ((Math.floor(this.scrollHeight -  jQuery(this).scrollTop()) - 50 ) <=  Math.floor( jQuery(this).outerHeight() )) {
                    
                    app.tc_read = true;
                    $(this).parent().find('.accept-btn').removeAttr('disabled');
                }
            });

            $('#termsConditionsModal .accept-btn').on('click', function(){

                if($(this).is(':disabled') === false){

                    $('#tcCheck').prop('checked', true).removeClass('is-invalid');
                    $('#termsConditionsModal').modal('hide')
                    app.tc_accepted = true;
                }
            });

            $('#termsConditionsModal [data-scroll-bottom]').on('click', function(){

                let scrollHeight = $(this).closest('.modal').find('.modal-body')[0].scrollHeight;
                $(this).closest('.modal').find('.modal-body').animate ({
                    scrollTop: scrollHeight
                });
            });

            $('#tcCheck').on('change', function(){

                if(app.tc_accepted === false){
                    $(this).prop('checked', false);
                    $('#termsConditionsModal').modal('show')
                }
            });

            $('#country').on('change', function(){

                app.update_cart();
            });

            $('[data-display-cart]').on('click', function(){
                !app.mobileSummaryOpen ? app.openMobileSummary() : app.closeMobileSummary();
            });

            $('#mobile-cart-summary [data-close]').on('click', function(){
                app.closeMobileSummary();
            });
        },
        
        initCart: function(){

            // update age fields 
            
            $.each($('#quoteForm .guest'), (index, value) => {
                let checkoutDate = ymdToLocalDate(hw_cart.checkout);
                new GuestField(value, checkoutDate);
            });

            app.update_cart();
            app.toggle_payment();
        },
        
        actions: function(){
            
            this.room_list.find('.room-input').on('change',  app.update_cart );
            this.room_list.find('.room-input').on('change',  app.toggle_payment );
            
            this.insurance_selector.on('change', app.update_cart );
            $(document).on('change', '#transferOptIn', app.update_cart );
            
            $(document).on( 'cart_updated', app.display_cart );
            $(document).on( 'cart_updated', app.update_disclaimer );
            $(document).on( 'cart_updated', () => {

                // update mobile nav total
                $('#mobile-cart-summary-nav .total').html(format_price(app.cart.total))
            });
            
        },

        openMobileSummary: function(){
            app.mobileSummaryOpen = true;
            $('#mobile-cart-summary').addClass('active');
        },

        closeMobileSummary: function(){
            app.mobileSummaryOpen = false;
            $('#mobile-cart-summary').removeClass('active');
        },

        update_disclaimer: function(){

            let $el = $('.payment-disclaimer .price'),
                deposit_type = app.get_deposit_type(),
                due_amount = 0;

            switch(deposit_type){
                case "custom":
                case "deposit":
                    due_amount = parseInt($('#deposit-custom-amount').val());
                    break;
                case "full":
                    due_amount = app.cart.total;
                    break;
            }

            $el.html(format_price(due_amount));
        },

        get_deposit_type: function(){

            return $('input[name="deposit[amount]"]:checked').val();
        },
        
        toggle_payment: function(){
            
            if(hw_cart.is_payment_enabled){
                
                if(app.is_room_available){
                    
                    $('.show-if-payment').show();
                }else{
                    
                    $('.show-if-payment').hide();
                }
            }
        },
        
        update_cart: function(){
            
            app.clear_cart();
            
            app.update_rooms();
            app.update_insurance();
            app.handle_transfer();
            
            $(document).trigger('cart_updated');
        },

        handle_transfer: function(){

            // optional transfers: the guest can untick them (form-preferences.php)
            let transfer_box = document.getElementById('transferOptIn');
            app.service_fee = 0;

            if(hw_cart.has_transfer && app.cart.items.length > 0 && (!transfer_box || transfer_box.checked)){

                let transfer_mode = hw_cart.transportation_mode,
                    price = parseInt(hw_cart.service_fee),
                    cart_item = null;

                if(parseInt(price) > 0){
                    switch(transfer_mode){
                        case 'room':
                            cart_item = {
                                'quantity': 1,
                                'title' : messages.airport_transportation_round_trip,
                                'price' : price,
                            };
                            break;
                        case 'person':
                            let transfer_multiplier = parseInt(app.adults) + parseInt(app.teen),
                                child_multiplier = parseInt(app.children),
                                // in case the price is zero fallback to adult price
                                child_price = parseInt(hw_cart.child_service_fee) > 0 ? parseInt(hw_cart.child_service_fee) : price;
                            
                            // do calculations
                            let adult_total = transfer_multiplier * price,
                                child_total = child_multiplier * child_price;

                            let title = `<small class="transfer-breakdown d-block">
                                <div>${transfer_multiplier} x ${messages.adult} <span class="person-total">(${ format_price( adult_total ) })</span></div>
                                ${child_total > 0 ? '<div>' + child_multiplier + ' x ' + messages.child + ' <span class="person-total">('+  format_price( child_total ) +')</span>' + '</div>' : '' }
                            </small>`;

                            cart_item = {
                                'quantity': transfer_multiplier + child_multiplier,
                                'title' : messages.airport_transportation_round_trip + title,
                                'price' : adult_total + child_total,
                            };
                            break;
                    }
                }else{
                    cart_item = {
                        'quantity': 1,
                        'title' : messages.airport_transportation_round_trip,
                        'price' : 'included',
                    };
                }
                
                app.service_fee = cart_item.price;
                app.add_item(cart_item);                
            }
        },
        
        update_rooms: function( ){

            let room = this.get_selected_room();

            if(room === false){
                return;
            }
            
            let name = room.data('name'),
                total = room.data('prices').total,
                rates = Object.values(room.data('prices').rates),
                is_available = parseInt(room.data('available'));
            this.is_room_available = is_available ? true : false;
            
            let title = name + ( hw_cart.mode == 'room' ? ' ( ' + hw_cart.nights + (( hw_cart.nights > 1) ? ' nights' : ' Night' ) + ' )' : ' Package' );
            title += `<small class="person-breakdown d-block">
                        <div>${app.adults} x ${messages.adult} <span class="person-total">(${ format_price( app.get_person_type_total('adult', rates) ) })</span></div>
                        ${app.teen > 0 ? '<div>' + app.teen + ' x ' + messages.teen + '<span class="person-total">('+  format_price( app.get_person_type_total('teen', rates) ) +')</span>' + '</div>' : '' }
                        ${app.children > 0 ? '<div>' + app.children + ' x ' + messages.child + ' <span class="person-total">('+  format_price( app.get_person_type_total('child', rates) ) +')</span>' + '</div>' : '' }
                    </small>`;
            
            let cart_item = {
                'quantity': 1,
                'title' : title,
                'price' : total,
            };
            
            app.room_total = total;
            app.add_item(cart_item);    
             
             return;     
        },

        get_person_type_total: function( person_type, rates) {
            
            let sum = 0;

            rates.forEach((item) => {
                
                sum += parseInt(item[person_type]);
            });

            return sum;
        },
        
        add_item: function( item ){
            
            app.cart.items.push(item);
            
            if(item.price != 'included'){
                app.cart.total = app.cart.total + item.price;
            }
        },
        
        clear_cart: function(){

            this.is_room_available = false;

            this.cart = {
                items: [],
                total: 0
            };
        },
        
        get_room_id: function(){
            
            return  hw_cart.mode == 'room' ? this.room_list.find('.room-input:checked').val() : this.room_list.find('.room-input:checked').next().data('room-id');  
        },
        
        get_selected_room: function(){

            let room_id = this.get_room_id();
             
            if( room_id == '' || room_id === undefined || room_id == 0 ){
                    
                return false;
            }

            return $('#room_' + room_id);
        },
        
        get_person_type_rates_total: function( room, person_type ){

            let rates = Object.values(room.data('prices').rates),
                total = 0;

            if(rates.length > 0){
                rates.forEach((values ) => {
                    total = total + parseInt( values[person_type] );
                });
            }

            return total;
        },
        
        update_insurance: function(){
            
            let value = app.insurance_selector.val();

            // reset the running total, otherwise a previously selected plan keeps
            // inflating the minimum deposit after the insurance is removed
            app.insurance_total = 0;

            // is customer is not from us hide insurance section and display the wording
            if( $('#country').val() != 'US'){
                
                $('#insurance-section').hide();
                return;
            }else{

                $('#insurance-section').show();
            }
            
            if( value == 0){
                
                return;
            }
            
            let adults_multiplier = parseInt(app.adults) + ( hw_cart.is_teen_enabled ? parseInt(app.teen) : 0 );
            
            if(app.insurance_mode == 'night'){
                
                if(hw_cart.mode == 'package' && this.room_list.find('.room-input:checked').length == 0){
                    
                    return;
                }
                
                var nights_multiplier = hw_cart.mode == 'room' ? hw_cart.nights : this.room_list.find('.room-input:checked').next().data('nights') ,
                    prices = app.get_range(value, nights_multiplier);
                
                let cart_item = {
                    'quantity': adults_multiplier,
                    'title' : messages.adult_insurance + ' ( ' + prices.adult + ' USD )',
                    'price' : prices.adult * adults_multiplier,
                    //'description' : 'Adults: ' + hw.adults + ',Children: ' + hw.children
                };
                                            
                app.add_item(cart_item);
                app.insurance_total = cart_item.price;
                
                if(app.children > 0){
                    
                    cart_item = {};
                    
                    cart_item = {
                        'quantity': app.children,
                        'title' : messages.child_insurance + ' ( ' + prices.child + ' USD )',
                        'price' : prices.child * app.children,
                        //'description' : 'Adults: ' + hw.adults + ',Children: ' + hw.children
                    };
                                                
                    app.add_item(cart_item);
                    app.insurance_total += cart_item.price;
                }
            }
            
            if(app.insurance_mode == 'price'){
                
                let room = app.get_selected_room();
                
                if( room === false ){
                    return;
                }
                
                let prices = app.get_range(value, app.room_total);
                
                let cart_item = {
                    'quantity': adults_multiplier + parseInt(app.children),
                    'title' : messages.insurance + ' ( ' + prices.adult + ' USD )',
                    'price' : prices.adult * ( adults_multiplier + parseInt(app.children) ),
                    //'description' : 'Adults: ' + hw.adults + ',Children: ' + hw.children
                };
                                            
                app.add_item(cart_item);
                app.insurance_total = cart_item.price;
            }

            if(app.insurance_mode == 'room_total'){
                
                let room = app.get_selected_room();
                
                if( room === false ){
                    return;
                }
                
                let prices = app.get_range(value, app.room_total),
                    plan = app.find_insurance_plan(value),
                    cost = 0;

                switch(plan.calculation_type){
                    case 'fixed':
                    default:
                        cost = parseInt(prices.amount);
                        break;
                    case 'percentage':
                        cost = Math.ceil( app.room_total *  ( parseInt(prices.amount) / 100 ) );
                        break;
                }
                
                let cart_item = {
                    'quantity': adults_multiplier + parseInt(app.children),
                    'title' : messages.insurance + ' ( Charged Per Room )',
                    'price' : cost,
                    //'description' : 'Adults: ' + hw.adults + ',Children: ' + hw.children
                };
                                            
                app.add_item(cart_item);
                app.insurance_total = cart_item.price;
            }

            if(app.insurance_mode == 'person_total'){

                let room = app.get_selected_room();
                
                if( room === false ){
                    return;
                }

                let insurance_total = 0;
                let title = messages.insurance + '<small class="person-breakdown d-block">';
                
                // calc adult
                let adult_total = app.get_person_type_rates_total(room, 'adult');
                let adult_tier = app.get_range(value, adult_total);
                insurance_total += app.adults * adult_tier['adult'];
                title += `<div>${app.adults} x ${messages.adult} <span class="person-total">(${ format_price( app.adults * parseInt(adult_tier.adult) ) })</span></div>`;

                // calc teen
                if(app.teen > 0){
                    let teen_total = app.get_person_type_rates_total(room, 'teen');
                    let teen_tier = app.get_range(value, teen_total);
                    insurance_total += app.teen * teen_tier['teen'];
                    title += `${app.children > 0 ? '<div>' + app.children + ' x ' + messages.child + ' <span class="person-total">('+  format_price( app.children * parseInt(children_tier.child) ) +')</span>' + '</div>' : '' }` 
                }

                // calc children
                if(app.children > 0){
                    let child_total = app.get_person_type_rates_total(room, 'child');
                    let children_tier = app.get_range(value, child_total);
                    insurance_total += app.children * children_tier['child'];
                    title += `${app.children > 0 ? '<div>' + app.children + ' x ' + messages.child + ' <span class="person-total">('+  format_price( app.children * parseInt(children_tier.child) ) +')</span>' + '</div>' : '' }`
                }

                // calc infats, infats don't pay so their tier is always the lowest
                if(app.infants > 0){
                    let infant_total = 0;
                    let infants_tier = app.get_range(value, infant_total);
                    insurance_total += app.infants * infants_tier['infant'];
                    title += `${app.infants > 0 ? '<div>' + app.infants + ' x ' + messages.infant + ' <span class="person-total">('+  format_price( app.infants * parseInt(infants_tier.infant) ) +')</span>' + '</div>' : '' }` 
                }

                // put title together
                title += `</small>`;
                
                let cart_item = {
                    'quantity': 1,
                    'title' : title,
                    'price' : insurance_total,
                    //'description' : 'Adults: ' + hw.adults + ',Children: ' + hw.children
                };
                                            
                app.add_item(cart_item);
                app.insurance_total = cart_item.price;
            }

            // new CV insurance calculation
            if(app.insurance_mode == 'total_cost_per_person'){

                let room = app.get_selected_room();
                
                if( room === false ){
                    return;
                }

                let total = app.room_total + app.service_fee;
                let persons_count = app.adults + app.children + app.teen + app.infants;
                let price_per_person = total / persons_count;

                let prices = app.get_range(value, price_per_person);

                if(!prices){
                    console.error('No insurance price range found for the selected room.');
                    return;
                }
                /* console.log(total);
                console.log(persons_count);
                console.log(price_per_person);
                console.log(prices);
                console.log(app.adults, parseInt(prices.adult), app.adults * parseInt(prices.adult))
                console.log(app.teen, parseInt(prices.teen), app.teen * parseInt(prices.teen))
                console.log(app.children, parseInt(prices.child), app.children * parseInt(prices.child))
                console.log(app.infants, parseInt(infant_prices.infant), app.infants * parseInt(infant_prices.infant)) */

                let insurance_total = ( app.adults * parseInt(prices.adult) ) + ( app.teen * parseInt(prices.teen) ) + ( app.children * parseInt(prices.child) ) + ( app.infants * parseInt(prices.infant) );

                let title = messages.insurance + `<small class="person-breakdown d-block">
                    <div>${app.adults} x ${messages.adult} <span class="person-total">(${ format_price( app.adults * parseInt(prices.adult) ) })</span></div>
                    ${app.teen > 0 ? '<div>' + app.teen + ' x ' + messages.teen + '<span class="person-total">('+  format_price( app.teen * parseInt(prices.teen) ) +')</span>' + '</div>' : '' }
                    ${app.children > 0 ? '<div>' + app.children + ' x ' + messages.child + ' <span class="person-total">('+  format_price( app.children * parseInt(prices.child) ) +')</span>' + '</div>' : '' }
                    ${app.infants > 0 ? '<div>' + app.infants + ' x ' + messages.infant + ' <span class="person-total">('+  format_price( app.infants * parseInt(prices.infant) ) +')</span>' + '</div>' : '' }
                </small>`;
                
                let cart_item = {
                    'quantity': 1,
                    'title' : title,
                    'price' : insurance_total,
                };
                            
                app.add_item(cart_item);
                app.insurance_total = cart_item.price;
            }
        },

        find_insurance_plan: function( id ){
            
            return app.insurance_plans.find( function(item){
                
                return item.id == id;
            });
        },
        
        get_range: function( id, target ){
            
            let plan = app.find_insurance_plan(id);

            if(!plan || !plan.prices){
                return false;
            }

            // plan.prices is an object containing objects, turn it into array before iterating
            return Object.values(plan.prices).find(function(price){

                if( parseInt(price.min) <= target && parseInt(price.max) >= target){
                    return price;
                }
            });
        },
        
        display_cart: function(){
            
            var html = '';
            
            if(app.cart.items.length == 0){
                
                html += messages.empty_cart;
            }else{
                $.each(app.cart.items, function(){
                
                    html += '<div class="cart-item">';
                    
                    html += '<div class="quantity">' + this.quantity + ' x</div>';
                    
                    html += '<div class="title">';
                    html += '<strong>'+this.title+'</strong>';
                    //html += '<p>'+this.description+'</p>';
                    html += '</div>';
                    
                    html += '<div class="price">';
                    html += this.price == 'included' ? 'Included' : format_price(this.price);
                    html += '</div>';
                    
                    html += '</div>';
                });
                
                html += '<div class="total d-flex"><div class="label">Total</div><div class="amount"><strong>' + format_price(app.cart.total) + '</strong></div></div>';
            }
            
            $('[data-cart]').html(html);
        },

        get_insurance_total: function(){

            return app.insurance_total;
        },

        validate: function(){

            // check if room was selected
            let room_id = this.get_room_id();
            if(room_id === undefined || room_id === ''){

                alert('Please, Select a room');
                return false;
            }

            // check if the first guest is an adult
            let guests = Array.from(document.querySelectorAll('#quoteForm .guest'));
            if(!guests.every(el => el.guestField.validate())){
                $('html, body').scrollTop($('#guests').offset().top);
                // a silent block reads as "nothing happened" — always say why
                alert('Please complete every guest\'s details (name and full date of birth).');
                return false;
            }


            if($('#tcCheck').is(':checked') === false || app.tc_accepted === false || app.tc_read === false){

                $('#tcCheck').addClass('is-invalid');
                alert('Please accept the Terms & Conditions: tick the checkbox, scroll the popup to the bottom, and press the Accept button (closing the popup with the X does not accept them).');
                // reopen the flow for them: uncheck so the next tick re-opens the popup
                $('#tcCheck').prop('checked', false);
                if($('#tcCheck').length){ $('html, body').scrollTop($('#tcCheck').offset().top - 120); }
                return false;
            }

            return true;
        }
    };
    
    return app;

})(jQuery);

jQuery.validator.setDefaults({
    debug: true,
    errorElement: 'span',
    errorPlacement: function (error, element) {
        error.addClass('invalid-feedback');
        element.closest('.form-group').append(error);
    },
    highlight: function (element, errorClass, validClass) {
        jQuery(element).addClass('is-invalid');
    },
    unhighlight: function (element, errorClass, validClass) {
        jQuery(element).removeClass('is-invalid');
    }
});

function getDatesDiff(date1, date2){
    
    return (date2.getTime() - date1.getTime()) / (1000 * 3600 * 24);
}

function addDays(date, days){
    let copyDate = new Date(date.getTime());
    copyDate.setDate(copyDate.getDate() + days);
    return copyDate;
}

function removeDays(date, days){
    let copyDate = new Date(date.getTime());
    copyDate.setDate(copyDate.getDate() - days);
    return copyDate;
}

/**
 * when on cart page prevent login to reload the page, 
 * it is done after information inserted from user is stored into session with ajax
 */
hw_addFilter('ajax_login_do_redirect', function(){
    
    return false;
});

/**
 * save form information into session
 */
jQuery(document).on('user_logged_in', hw_save_form_data_to_session);

function hw_save_form_data_to_session(){

    const unwanted_data = ['action', '_wp_http_referer', '_wpnonce'];

    const form_data = jQuery('#quoteForm').serializeArray().reduce(function (json, { name, value }) {

        if(!unwanted_data.includes(name)){
            json[name] = value;
        }

        return json;
    }, {});

    jQuery.ajax({
        url: ajax.ajax_url,
        method: 'POST',
        contentType: 'application/x-www-form-urlencoded',
        dataType: 'json',
        beforeSend: () => {
            blockUI(jQuery('#ajax-login'), 'Saving data before redirect');
        },
        data:{
            ...{action: 'hw_cart_save_form_data_into_session'},
            ...form_data
        }
    }).done(function(response){
        
        if(!response.success){

            console.log(response.message);
        }
    }).always(function(){

        // always reload, the user was logged in. If information wasn't saved user shouldn't know
        location.reload();
    });
}

var datepickerEnLocale = {
    days: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
    daysShort: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
    daysMin: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
    months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
    monthsShort: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    today: 'Today',
    clear: 'Clear',
    dateFormat: 'MM.dd.yyyy',
    timeFormat: 'hh:mm aa',
    firstDay: 0
};

var datepickerEsLocale = {
  days: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
  daysShort: ['Dom', 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab'],
  daysMin: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
  months: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
  monthsShort: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
  today: 'Hoy',
  clear: 'Limpiar',
  dateFormat: 'dd.MM.yyyy',
  timeFormat: 'hh:mm aa',
  firstDay: 1
};

jQuery(function($){
    
    var minNight = parseInt(hw_cart.min_nights),
        start_date = ymdToLocalDate(hw.start_date),
        end_date = ymdToLocalDate(hw.end_date),
        signature = new Signature('cart-signature'),
        ci_end_date = removeDays(end_date, minNight),
        selectedCheckinDate = $('#ci-alt').val() !== '' ? ymdToLocalDate($('#ci-alt').val()) : '',
        selectedCheckoutDate = $('#co-alt').val() !== '' ? ymdToLocalDate($('#co-alt').val()) : '';

        
    const checkinDatepicker = new AirDatepicker( '#checkin',{
        selectedDates: selectedCheckinDate,
        locale: getLang() == 'es' ? datepickerEsLocale : datepickerEnLocale,
        minDate: start_date,
        maxDate: ci_end_date,
        autoClose: true,
        toggleSelected: false,
        altField: '#ci-alt',
        altFieldDateFormat: "yyyy-MM-dd",
        onSelect: function({date}){
            
            date.setDate(date.getDate() + minNight);
            let checkoutDate = checkoutDatepicker.selectedDates;
            
            if( checkoutDate == null || ( checkoutDate != null && checkoutDate <= date )){
                
                checkoutDatepicker.selectDate( date )
            }
            
            checkoutDatepicker.update( {'minDate' : date });
            checkoutDatepicker.show();
        },
    } );

     // init checkout date according to min nights
    var start_date = $('#checkin').val() !== '' ? new Date($('#checkin').val()) : start_date,
        co_start_date = addDays(start_date, minNight);

    const checkoutDatepicker = new AirDatepicker( '#checkout',{
        selectedDates: selectedCheckoutDate,
        locale: getLang() == 'es' ? datepickerEsLocale : datepickerEnLocale,
        minDate: co_start_date,
        maxDate: end_date,
        autoClose: true,
        toggleSelected: false,
        altField: '#co-alt',
        altFieldDateFormat: "yyyy-MM-dd",
        onSelect: function({date}){

            $( '#checkout' ).removeClass('is-invalid').next().text('');

            // check if the date is minor or equal
            var checkin_date = checkinDatepicker.selectDates,
                checkout_date = date;

            if( checkout_date <= checkin_date){

                checkin_date.setDate(checkin_date.getDate()+1);
                checkoutDatepicker.selectDate(checkin_date);
            }else{
                // check if the date respects the min nights
                var days = (checkout_date - checkin_date) / (1000 * 3600 * 24);  
                if(Math.ceil(days) < minNight){
                    
                    checkin_date.setDate(checkin_date.getDate() + minNight);
                    checkoutDatepicker.selectDate(checkin_date);
                     $( '#checkout' ).addClass('is-invalid').next().text(messages.checkout_min_nights);
                }
            }
        },
    } );

    /*$("#quoteForm").validate({
        ignore: '*:not([name])',
        rules: {
            "room_id": {
                required: true,
            },
            "customer[first_name]": "required",
            "customer[last_name]": "required",
            "customer[phone_number]": "required",
            "customer[email]" : {
                required: true,
                email: true
            },
            "customer[state]": "required",
            "customer[country]": "required",
            "terms_and_conditions": "required",
            "payment[card-type]": {
                required: {
                    depends: function() {
                        return HWCart.is_room_available;
                    }
                }
            },
            "payment[card-owner]": {
                required: {
                    depends: function() {
                        return HWCart.is_room_available;
                    }
                }
            },
            "payment[card-number]": {
                required: {
                    depends: function() {
                        return HWCart.is_room_available;
                    }
                },
                minlength: 16
            },
            "payment[card-month]": {
                required: {
                    depends: function() {
                        return HWCart.is_room_available;
                    }
                },
                minlength: 2,
                maxlength: 2,
                digits: true
            },
            "payment[card-year]": {
                required: {
                    depends: function() {
                        return HWCart.is_room_available;
                    }
                },
                minlength: 4,
                maxlength: 4,
                digits: true
            },
            "payment[card-ccv]": {
                required: {
                    depends: function() {
                        return HWCart.is_room_available;
                    }
                },
                minlength: 3,
                maxlength: 3,
                digits: true
            }
        },
        messages: {
            "room_id": messages.room_id,
            "customer[first_name]": messages.customer_first_name,
            "customer[last_name]": messages.customer_last_name,
            "customer[phone_number]": messages.customer_phone_number,
            "customer[email]" : {
                required: messages.customer_email.required,
                email: messages.customer_email.email
            },
            "customer[state]": messages.customer_state,
            "customer[country]": messages.customer_country,
            "terms_and_conditions" : messages.terms_and_conditions,
            "payment[card-type]" : messages.payment_card_type,
            "payment[card-owner]" : messages.payment_card_owner,
            "payment[card-number]": {
                required: messages.payment_card_number.required,
                minlength: messages.payment_card_number.minlenght,
            },
            "payment[card-month]": {
                required: messages.payment_card_month.required,
                minlength: messages.payment_card_month.minlength,
                maxlength: messages.payment_card_month.maxlength,
                digits: messages.payment_card_month.digits
            },
            "payment[card-year]": {
                required: messages.payment_card_year.required,
                minlength: messages.payment_card_year.minlength,
                maxlength: messages.payment_card_year.maxlength,
                digits: messages.payment_card_year.digits
            },
            "payment[card-ccv]": {
                required: messages.payment_card_ccv.required,
                minlength: messages.payment_card_ccv.minlength,
                maxlength: messages.payment_card_ccv.maxlength,
                digits: messages.payment_card_ccv.digits
            }
        }
    });*/
    
    $('.cart-card').hcSticky({
        stickTo: $('#sidebar .inner'),
        responsive: {
            992: {
              disable: true
            }
        }
    });

    /**
     * Billing information
     */
    toggleState($('#country').val());

    $(document).on('change', '#country', function(){

        toggleState($(this).val());
    });

    // classic insurance do not sell Elite plan to NY people, hard code this case
    $(document).on('change', '#us-states', function(){

        let us_state_val = $(this).val();
        if(hw_cart.insurance_id == 2){
            if(us_state_val === 'NY'){
                HWCart.insurance_selector.val('0').find('option[value="4"]').attr('disabled', true);
            }else{
                HWCart.insurance_selector.find('option[value="4"]').attr('disabled', false);
            }
        }
    } );

    function toggleState(country_code){

        if(country_code === 'US'){
            $('#us-states').show().prop('disabled', false);
            $('#other-state').hide().prop('disabled', true);

            
        }else{
            $('#us-states').hide().prop('disabled', true);
            $('#other-state').show().prop('disabled', false);
        }
    }
    
    /*
     * Payment form
     */
    $(document).on('change', '.card-type', function(){

        var ccvEl = $('#card-ccv'),
            cardEl = $('#card-number'),
            ccvMax = $('.card-type:checked').val() == 'amex' ? 3 : 2,
            cardMax = $('.card-type:checked').val() == 'amex' ? 14 : 15;

        if($(this).val() == 'amex'){
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

    $('#card-number').on('keypress', function(evt){
        
        let el = $(this),
            val = el.val().split("-").join(""),
            max = $('.card-type:checked').val() == 'amex' ? 14 : 15;
        
        evt = (evt) ? evt : window.event;
        var charCode = (evt.which) ? evt.which : evt.keyCode;
        if (charCode > 31 && (charCode < 48 || charCode > 57)) {
            return false;
        }
            
        if(val.length > max){
            return false;
        }
    });

    function format_card(val){

        return val.match(new RegExp('.{1,4}', 'g')).join("-");;
    }
    
    $('#card-number').on('keyup', function(){
        
        let el = $(this),
            val = el.val().split("-").join("");
        
        if(val.length <= 0){
            return false;
        }
        
        $(this).val(format_card(val));
    });
    
    $('#card-month').on('keypress', function(){
        
        let el = $(this),
            val = el.val();
            
        if(val.length > 1){
            return false;
        }
    });
    
    $('#card-year').on('keypress', function(){
        
        let el = $(this),
            val = el.val();
            
        if(val.length > 3){
            return false;
        }
    });
    
    $('#card-ccv').on('keypress', function(){
        
        let el = $(this),
            val = el.val(),
            max = $('.card-type:checked').val() == 'amex' ? 3 : 2;
            
        if(val.length > max){
            return false;
        }
    });

    // Deposit
    $('input[name="deposit[amount]"]').on('change', handle_deposit );

    function calc_deposit_amount(){

        // always have a minimum deposit, get the value from the options
        let deposit = parseInt(hw_cart.min_deposit);

        if(hw_cart.deposit_type == 'dynamic'){
  
            let nights_multiplier = parseInt(hw_cart.deposit_nights_multiplier),
                user_nights = parseInt(hw_cart.nights),
                room = HWCart.get_selected_room();

            if(room === false){
                return false;
            }
            
            let totalsPerDay = Object.values(room.data('prices').total_per_day);

            // fix nights multiplier in case the user chose a smaller number of nights
            if(nights_multiplier > user_nights){

                nights_multiplier = user_nights;
            }

            deposit = totalsPerDay[0] * nights_multiplier;
        }

        // always add the insurance amount
        return deposit + HWCart.get_insurance_total();
    }

    function handle_deposit(){
        
        let val = $('input[name="deposit[amount]"]:checked').val(),
            is_custom = val === 'custom';

        $('#deposit-custom-amount-wrapper').children('input').prop('readonly', !is_custom);

        let deposit = calc_deposit_amount();

        // always refresh the minimum, the insurance may have been added/removed
        // while another payment option was selected
        if(deposit !== false){

            $('#deposit-custom-amount').prop('min', deposit);
            $('label[for="deposit"] .price').html(format_price(deposit));

            // keep the field in sync with the new minimum
            if(val === 'deposit' || parseInt($('#deposit-custom-amount').val()) < deposit){

                $('#deposit-custom-amount').val(deposit);
            }
        }

        if(val === 'full'){

            $('#deposit-custom-amount').val(HWCart.cart.total);
        }

        HWCart.update_disclaimer();
    }

    $(document).on( 'cart_updated' , handle_deposit);

    $('#deposit-custom-amount').on( 'focusout', function(){

        let val = $(this).val(),
            min_deposit = calc_deposit_amount();

        if(val < min_deposit){

            $(this).val(min_deposit);
        }

        HWCart.update_disclaimer();
    } );

    
    $('.room-info-cta').on('click', function(){
        
        var id = $(this).closest('.inner').data('room-id');
        
        $.ajax({
            url: ajax.ajax_url,
            method: 'get',
            data:{
                room_id: id,
                action: 'get_room_overview'
            }
        }).done(function(response){
            
            if(response.success){
                
                Fancybox.show(
                    [
                      {
                        src: response.data,
                        type: "html",
                      },
                    ]
                  );
                
            }else{
                alert(response.message);
            }
        });
        
        return false;
    });
    
    $('.package-info-cta').on('click', function(){
        
        var id = $(this).data('id');
        
        $.ajax({
            url: ajax.ajax_url,
            method: 'get',
            data:{
                package_id: id,
                action: 'get_package_overview'
            }
        }).done(function(response){
            
            if(response.success){
                
                Fancybox.show(
                    [
                      {
                        src: response.data,
                        type: "html",
                      },
                    ]
                  );
                
            }else{
                alert(response.message);
            }
        });
        
        return false;
    });

    /**
     * Password
     */
    $('#confirm-password').on('keyup', function(){

        let password = $('#password').val(),
            el = $(this),
            confirmation = el.val();

        if(password !== confirmation){
            el.next().html(messages.customer_password_confirmation_wrong);
            el.removeClass('is-valid').addClass('is-invalid');
        }else{
            el.next().html(messages.customer_password_confirmation_ok);
            el.removeClass('is-invalid').addClass('is-valid');
        }
    })

    /**
     * Block the form while submitting to avoid double submissions
     */
    $('#quoteForm').on('submit', function(event){
        
        if(HWCart.validate() === false){

            return false;
        }

        // check if, when required, signature was added
        if(HWCart.require_signature){
            let signature_input_val = $('#cart-signature input[data-hidden-input]').val();
            if(signature_input_val === '' || signature_input_val === undefined || signature_input_val === null){

                //$('html, body').scrollTop( );
                alert('Signature Required!');
                return false;
            }
        }


        $('#submit-btn').prop('disabled', true);
        blockUI( $(this), 'Submitting...');
        
        return true;
    });

    HWCart.init();
});
            