import POS from "./pos/pos.class";

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

Noty.overrideDefaults({
    timeout: 3000
});

jQuery(function(){

    var pos = new POS();

    jQuery.validator.addMethod("custom_amount", function(value, element) {
        
            return this.optional(element) || ( value > 0 && value <= pos.getInvoice().due_amount);
        }, 
        "Please specify a valid amount."
    );

    pos.validator = jQuery("#cv-pos-form").validate({
        ignore: '*:not([name])',
        rules: {
            "invoice_id": "required",
            "custom_amount": {
                required: {
                    depends: function() {
                        return jQuery('#pos-amount-custom').is(':checked')
                    }
                },
                custom_amount : true
            },
            "billing[first_name]": "required",
            "billing[last_name]": "required",
            "billing[email]" : {
                required: true,
                email: true
            },
            "billing[address1]": "required",
            "billing[administrative_area]": "required",
            "billing[country]": "required",
            "billing[locality]": "required",
            "billing[postal_code]": "required",
            "card[type]": {
                required: {
                    depends: function() {
                        return pos.useSavedCard();
                    }
                }
            },
            "card[holder]": {
                required: {
                    depends: function() {
                        return pos.useSavedCard();
                    }
                }
            },
            "card[number]": {
                required: {
                    depends: function() {
                        return pos.useSavedCard();
                    }
                },
                minlength: 16
            },
            "card[month]": {
                required: {
                    depends: function() {
                        return pos.useSavedCard();
                    }
                },
                minlength: 2,
                maxlength: 2,
                max: 12,
                digits: true
            },
            "card[year]": {
                required: {
                    depends: function() {
                        return pos.useSavedCard();
                    }
                },
                minlength: 4,
                maxlength: 4,
                digits: true,
                min: 2024
            },
            "card[ccv]": {
                required: {
                    depends: function() {
                        return pos.useSavedCard();
                    }
                },
                minlength: 3,
                maxlength: 4,
                digits: true
            }
        },
        /*messages: {
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
        }*/
    });
})