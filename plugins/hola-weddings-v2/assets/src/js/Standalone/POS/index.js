import POS from "./pos.class";

jQuery.validator.setDefaults({
    debug: true,
    errorElement: 'span',
    errorPlacement: function (error, element) {
        let form_group = element.closest('.form-group');
        error.addClass('invalid-feedback');
        form_group.append(error);
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

    pos.validator = jQuery("#pos-form").validate({
        ignore: '*:not([name])',
        rules: {
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
                required: true
            },
            "card[holder]": {
                required: true
            },
            "card[number]": {
                required: true,
                minlength: 16
            },
            "card[month]": {
                required: true,
                minlength: 2,
                maxlength: 2,
                max: 12,
                digits: true
            },
            "card[year]": {
                required: true,
                minlength: 4,
                maxlength: 4,
                digits: true,
                min: 2024
            },
            "card[ccv]": {
                required: true,
                minlength: 3,
                maxlength: 4,
                digits: true
            },
            "terms_and_conditions" : {
                required: (field) => {
                    return !pos.termConditionsAccepted() || !jQuery(field).is(':checked');
                }
            },
            "signature" : {
                required: () => {
                    return !pos.hasSignature();
                }
            }
        },
        messages: {
            "billing[first_name]": hw_pos.messages.billing_first_name,
            "billing[last_name]": hw_pos.messages.billing_last_name,
            "billing[phone_number]": hw_pos.messages.billing_phone_number,
            "billing[email]" : {
                required: hw_pos.messages.billing_email.required,
                email: hw_pos.messages.billing_email.email
            },
            "billing[administrative_area]": hw_pos.messages.billing_state,
            "billing[country]": hw_pos.messages.billing_country,
            "billing[locality]" : hw_pos.messages.billing_city,
            "billing[postal_code]" : hw_pos.messages.billing_postal_code,
            "billing[address1]" : hw_pos.messages.billing_address,
            "card[type]" : hw_pos.messages.card_type,
            "card[holder]" : hw_pos.messages.card_owner,
            "card[number]": {
                required: hw_pos.messages.card_number.required,
                minlength: hw_pos.messages.card_number.minlenght,
            },
            "card[month]": {
                required: hw_pos.messages.card_month.required,
                minlength: hw_pos.messages.card_month.minlength,
                maxlength: hw_pos.messages.card_month.maxlength,
                digits: hw_pos.messages.card_month.digits
            },
            "card[year]": {
                required: hw_pos.messages.card_year.required,
                minlength: hw_pos.messages.card_year.minlength,
                maxlength: hw_pos.messages.card_year.maxlength,
                digits: hw_pos.messages.card_year.digits
            },
            "card[ccv]": {
                required: hw_pos.messages.card_ccv.required,
                minlength: hw_pos.messages.card_ccv.minlength,
                maxlength: hw_pos.messages.card_ccv.maxlength,
                digits: hw_pos.messages.card_ccv.digits
            },
            "terms_and_conditions": hw_pos.messages.terms_and_conditions,
            "signature": {
                required: hw_pos.messages.signature,
            }
        }
    });
})