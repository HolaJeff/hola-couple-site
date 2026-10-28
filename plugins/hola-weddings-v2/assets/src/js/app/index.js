import HwAuthModal, { AUTH_EVENTS } from './auth-modal';
import { registerVerifyEmail } from './verify-email';

// window.hw is the wp_localize_script payload for this handle, so extend it
// rather than replacing it. Assigned at module scope so anything loading after
// this bundle — the React booking form included — can call it right away;
// open() queues itself until the footer markup exists.
window.hw = window.hw || {};
window.hw.authModal = new HwAuthModal();
window.hw.AUTH_EVENTS = AUTH_EVENTS;

// Legacy aliases. The modal grew a sign-up pane and was renamed at the JS layer,
// but window.hw.loginModal is what the React booking form already calls and what
// any third party would have found — so both names point at the one instance.
// AUTH_EVENTS is a superset of the old LOGIN_EVENTS keys, and the five original
// event *names* are unchanged, so existing listeners keep firing.
window.hw.loginModal = window.hw.authModal;
window.hw.LOGIN_EVENTS = AUTH_EVENTS;

// Alpine components owned by this bundle. Registered on alpine:init, which has
// not fired yet — this file runs in <head> and Alpine is deferred.
registerVerifyEmail();

jQuery(function($){

    $('[data-toggle="tooltip"]').tooltip();

    updateGalleryItemsHeight();

    $(window).on('resize', updateGalleryItemsHeight);
    
    $('.hotel-slider').slick({
        arrows: true,
        dots: false
    });

    $('.ajax-login-btn').on( 'click', Ajax_Login );

    $('#ajax-reset-password .reset-password-btn').on('click', ResetPassword)

    // Delegated: the pane is only rendered when sign-up is switched on.
    $(document).on('click', '.hw-signup-btn', SignUp);

    $('#help-bubble .bubble .trigger').on('click', function(){
        $(this).parent().toggleClass('open');
    })
    
});

function updateGalleryItemsHeight(){
    jQuery('.gallery .gallery-item').each(function(){
        let width = jQuery(this).outerWidth();
        console.log(width)
        jQuery(this).outerHeight(width);
    });
}

/**
 * Format price
 * @param {int} price 
 * @returns 
 */
function format_price( price ){
    
    return hw.currency_symbol + price + ' ' + hw.currency;
}

/**
 * Calculate age
 * @param {Date} dateString 
 * @param {Date} dateString 
 * @returns 
 */
function calc_age(birthDate, comparisonDate) {

    if(!birthDate instanceof Date){
        throw new TypeError("birthDate should be of type Date");
    }

    if(!comparisonDate instanceof Date){
        throw new TypeError("birthDate should be of type Date");
    }
    
    var age = comparisonDate.getFullYear() - birthDate.getFullYear();
    var m = comparisonDate.getMonth() - birthDate.getMonth();

    if (m < 0 || (m === 0 && comparisonDate.getDate() < birthDate.getDate())) {
        age--;
    }
    return age;
}

var is_doing_ajax_login = false;

/**
 * Perfom ajax login
 */
function Ajax_Login() {

    var container_el = jQuery('#ajax-login'),
        username_el = container_el.find('input[name="username"]'),
        pwd_el = container_el.find('input[name="password"]'),
        quote_form = jQuery('#quoteForm');

    // This is our request object. Each of these keys (action,
    // username & password) can be picked up in the PHP $_POST object.
    var request = {
       'action': 'ajax_login',
       'username': username_el.val(),
       'password': pwd_el.val()
    };

    // request already sent
    if(is_doing_ajax_login == true){

        return;
    }

    container_el.find('.errorMessage').remove();

    // check if values are set
    if (!request.username || !request.password) {
       
        if(!request.username.trim()){
            username_el.next().html(hw_messages.ajax_login_email_required);
            username_el.addClass('is-invalid');
        }
        
        if(!request.password.trim()){
            pwd_el.next().html(hw_messages.ajax_login_pwd_required);
            pwd_el.addClass('is-invalid');
        }
        
        return;
    }
        
    username_el.removeClass('is-invalid');
    pwd_el.removeClass('is-invalid');
    
    // console.log('Attempting to log in now.');
    container_el.addClass('working');
    container_el.find('input, button').prop('disabled', true);

    // Send our login request object to /wp-admin/admin-ajax.php
    jQuery.ajax({
        url: ajax.ajax_url,
        data: request,
        method: 'POST',
        contentType: 'application/x-www-form-urlencoded',
        dataType: 'json',
        beforeSend: () => {
            is_doing_ajax_login = false;
            blockUI(container_el);
        }
    }).done(function(response) { // Success
        
        if (response.isLoggedIn) {

            // Let 3rd parties to prevent login i.e. on cart page
            const do_rediret = hw_applyFilters('ajax_login_do_redirect', true);

            // trigger the event
            jQuery(document).trigger('user_logged_in');

            // Native event for consumers that are not jQuery — the React booking
            // form persists its in-progress booking here. Dispatched
            // synchronously and before the reload, so listeners run first.
            window.hw.authModal.emit(AUTH_EVENTS.success);

            // Signed in, but the address has not been confirmed yet. Say so right
            // here rather than reloading into a page that will only bounce them to
            // /account/verify-email/. Closing the modal reloads, so the page still
            // catches up with the new session.
            const verification = response.verification || {};

            if (verification.required && window.hw.authModal.showVerification(verification)) {

                container_el.find('input, button').prop('disabled', false);
                container_el.removeClass('working');
                unblockUI(container_el);
                return;
            }

            // reload if allowed
            if(do_rediret){

                location.reload();
            }
            
        }else{

            jQuery(document).trigger('user_did_not_log_in');

            window.hw.authModal.emit(AUTH_EVENTS.error, {
                action: 'login',
                message: response.errorMessage,
            });

            container_el.prepend(jQuery('<p class="errorMessage alert alert-danger"></p>').html(response.errorMessage));
        }

    }).fail(function() { // Failure
            
        container_el.prepend(jQuery('<p class="errorMessage alert alert-danger"></p>').html(hw_messages.ajax_login_generic_error));

    }).always(function(response) { // Always
        
        if (!response.isLoggedIn) {

            container_el.find('input, button').prop('disabled', false);
            container_el.removeClass('working');
        }

        unblockUI(container_el);
    });
}

/**
 * Perfom ajax login
 */
function ResetPassword() {

    var container_el = jQuery(this).closest('#ajax-reset-password'),
        email_el = container_el.find('input[name="email"]');

    var request = {
       'action': 'hw_reset_password',
       'email': email_el.val(),
    };

    container_el.find('.notice').remove();

    // check if values are set
    if (!request.email) {
       
        if(!request.email.trim()){
            email_el.next().html(hw_messages.ajax_login_email_required);
            email_el.addClass('is-invalid');
        }
        
        return;
    }
        
    email_el.removeClass('is-invalid');
    
    container_el.find('input, button').prop('disabled', true);

    // Send our login request object to /wp-admin/admin-ajax.php
    jQuery.ajax({
        url: ajax.ajax_url,
        data: request,
        method: 'POST',
        contentType: 'application/x-www-form-urlencoded',
        dataType: 'json',
        beforeSend: () => {
            blockUI(container_el);
        }
    }).done(function(response) { // Success
        
        let alert = jQuery('<p class="notice alert alert-success"></p>');

        if (response.success) {

            alert.addClass('alert-success').html(response.message);
        }else{

            alert.addClass('alert-danger').html(response.message);
        }

        container_el.prepend(alert);

    }).fail(function() { // Failure
            
        container_el.prepend(jQuery('<p class="notice alert alert-danger"></p>').html(hw_messages.ajax_login_generic_error));

    }).always(function(response) { // Always

        container_el.find('input, button').prop('disabled', false);
        unblockUI(container_el);
    });
}

/**
 * Create an account from the sign-up pane.
 *
 * Field errors go into each input's .next() div, matching how Ajax_Login and
 * ResetPassword write theirs; anything the server reports without a field lands
 * in a banner prepended to the pane.
 */
function SignUp() {

    var container_el = jQuery(this).closest('#ajax-signup');

    var fields = {
        first_name:       container_el.find('input[name="signup_first_name"]'),
        last_name:        container_el.find('input[name="signup_last_name"]'),
        email:            container_el.find('input[name="signup_email"]'),
        password:         container_el.find('input[name="signup_password"]'),
        password_confirm: container_el.find('input[name="signup_password_confirm"]'),
    };

    // Clear whatever the previous attempt left behind.
    container_el.find('.errorMessage').remove();
    container_el.find('.hw-lm__field-error').html('');
    container_el.find('input').removeClass('is-invalid');

    var invalid = function(key, message){
        fields[key].addClass('is-invalid').next().html(message);
    };

    // Client-side first, so an obvious mistake costs no round trip. The server
    // repeats every one of these — this is convenience, not the gate.
    var errors = 0;

    if(!fields.first_name.val().trim()){
        invalid('first_name', hw_messages.ajax_signup_first_name_required); errors++;
    }

    if(!fields.email.val().trim()){
        invalid('email', hw_messages.ajax_signup_email_required); errors++;
    }

    if(!fields.password.val()){
        invalid('password', hw_messages.ajax_signup_password_required); errors++;
    }else if(fields.password.val().length < 8){
        invalid('password', hw_messages.ajax_signup_password_short); errors++;
    }else if(fields.password.val() !== fields.password_confirm.val()){
        invalid('password_confirm', hw_messages.ajax_signup_password_mismatch); errors++;
    }

    if(errors){
        return;
    }

    var request = {
        'action': 'hw_signup',
        'nonce': container_el.find('input[name="hw_signup_nonce"]').val(),
        'signup_first_name': fields.first_name.val(),
        'signup_last_name': fields.last_name.val(),
        'signup_email': fields.email.val(),
        'signup_password': fields.password.val(),
        'signup_password_confirm': fields.password_confirm.val(),
    };

    container_el.find('input, button').prop('disabled', true);

    jQuery.ajax({
        url: ajax.ajax_url,
        data: request,
        method: 'POST',
        contentType: 'application/x-www-form-urlencoded',
        dataType: 'json',
        beforeSend: () => {
            blockUI(container_el);
        }
    }).done(function(response) {

        if(!response || !response.success){

            jQuery.each((response && response.errors) || {}, function(key, message){
                if(fields[key]){
                    invalid(key, message);
                }
            });

            if(response && response.message){
                container_el.prepend(jQuery('<p class="errorMessage alert alert-danger"></p>').html(response.message));
            }

            window.hw.authModal.emit(AUTH_EVENTS.error, {
                action: 'signup',
                message: (response && response.message) || '',
            });

            return;
        }

        // Same contract the login flow uses — listeners persist state here,
        // synchronously, before we leave the page.
        window.hw.authModal.emit(AUTH_EVENTS.success);

        // Cancelable, and still fired so anything listening can react. The
        // redirect it guards is now only the fallback — see below.
        const go = window.hw.authModal.emit(AUTH_EVENTS.registered, {
            userId: response.userId,
            redirect: response.redirect,
        });

        // A new account is always unconfirmed, so this is the normal path: swap
        // the modal to its verification pane instead of throwing the visitor onto
        // another page. Closing the modal reloads so the page catches up.
        const verification = response.verification || {};

        if (verification.required && window.hw.authModal.showVerification(verification)) {

            container_el.find('input, button').prop('disabled', false);
            unblockUI(container_el);
            return;
        }

        if(go && response.redirect){
            location.assign(response.redirect);
        }else{
            location.reload();
        }

    }).fail(function() {

        container_el.prepend(jQuery('<p class="errorMessage alert alert-danger"></p>').html(hw_messages.ajax_signup_generic_error));

    }).always(function() {

        container_el.find('input, button').prop('disabled', false);
        unblockUI(container_el);
    });
}

/**
 * Block element
 *
 * @param {jQuery Element} el
 * @param {string} msg
 */
function blockUI(el, msg){
        
    el.block({
        centerY: false,
        centerX: true,
        message: msg,
        css:{
            width: '100%'
        },
        overlayCSS:  { 
            backgroundColor: 'rgba(255,255,255,0.9)',
        }
    }).addClass('loading');
}

/**
 * Unblock element
 * 
 * @param {jQuery element} el 
 */
function unblockUI(el){

    el.unblock().removeClass('loading');
}

/**
 *  Hook support
 */

/**
 * Contains the hooks that get registered with this EventManager. The array for storage utilizes a "flat"
 * object literal such that looking up the hook utilizes the native object literal hash.
 */
var HW_STORAGE = {
    actions: {},
    filters: {}
};
function hw_getStorage() {
    return HW_STORAGE;
}

/**
 * Adds an action to the event manager.
 *
 * @param action Must contain namespace.identifier
 * @param callback Must be a valid callback function before this action is added
 * @param [priority=10] Used to control when the function is executed in relation to other callbacks bound to the same hook
 * @param [context] Supply a value to be used for this
 */
function hw_addAction(action, callback, priority, context) {
    if (typeof action === 'string' && typeof callback === 'function') {
        priority = parseInt(priority || 10, 10);
        hw_addHook('actions', action, callback, priority, context);
    }
}

/**
 * Performs an action if it exists. You can pass as many arguments as you want to this function; the only rule is
 * that the first argument must always be the action.
 */
function hw_doAction( /* action, arg1, arg2, ... */) {
    var args = Array.prototype.slice.call(arguments);
    var action = args.shift();
    if (typeof action === 'string') {
        hw_runHook('actions', action, args);
    }
}

/**
 * Removes the specified action if it contains a namespace.identifier & exists.
 *
 * @param action The action to remove
 * @param [callback] Callback function to remove
 */
function hw_removeAction(action, callback) {
    if (typeof action === 'string') {
        hw_removeHook('actions', action, callback);
    }
}

/**
 * Adds a filter to the event manager.
 *
 * @param filter Must contain namespace.identifier
 * @param callback Must be a valid callback function before this action is added
 * @param [priority=10] Used to control when the function is executed in relation to other callbacks bound to the same hook
 * @param [context] Supply a value to be used for this
 */
function hw_addFilter(filter, callback, priority, context) {
    if (typeof filter === 'string' && typeof callback === 'function') {
    priority = parseInt(priority || 10, 10);
    hw_addHook('filters', filter, callback, priority, context);
    }
}

/**
 * Performs a filter if it exists. You should only ever pass 1 argument to be filtered. The only rule is that
 * the first argument must always be the filter.
 */
function hw_applyFilters( /* filter, filtered arg, arg2, ... */) {
    var args = Array.prototype.slice.call(arguments);
    var filter = args.shift();
    if (typeof filter === 'string') {
        return hw_runHook('filters', filter, args);
    }
}

/**
 * Removes the specified filter if it contains a namespace.identifier & exists.
 *
 * @param filter The action to remove
 * @param [callback] Callback function to remove
 */
function hw_removeFilter(filter, callback) {
    if (typeof filter === 'string') {
        hw_removeHook('filters', filter, callback);
    }
}

/**
 * Removes the specified hook by resetting the value of it.
 *
 * @param type Type of hook, either 'actions' or 'filters'
 * @param hook The hook (namespace.identifier) to remove
 * @private
 */
function hw_removeHook(type, hook, callback, context) {
    if (!HW_STORAGE[type][hook]) {
        return;
    }
    if (!callback) {
        HW_STORAGE[type][hook] = [];
    } else {
    var handlers = HW_STORAGE[type][hook];
    var i;
    if (!context) {
            for (i = handlers.length; i--;) {
                if (handlers[i].callback === callback) {
                    handlers.splice(i, 1);
                }
            }
        } else {
            for (i = handlers.length; i--;) {
                var handler = handlers[i];
                if (handler.callback === callback && handler.context === context) {
                    handlers.splice(i, 1);
                }
            }
        }
    }
}

/**
* Adds the hook to the appropriate storage container
*
* @param type 'actions' or 'filters'
* @param hook The hook (namespace.identifier) to add to our event manager
* @param callback The function that will be called when the hook is executed.
* @param priority The priority of this hook. Must be an integer.
* @param [context] A value to be used for this
* @private
*/
function hw_addHook(type, hook, callback, priority, context) {
    var hookObject = {
        callback: callback,
        priority: priority,
        context: context
    };

    // Utilize 'prop itself' : http://jsperf.com/hasownproperty-vs-in-vs-undefined/19
    var hooks = HW_STORAGE[type][hook];
    if (hooks) {
        hooks.push(hookObject);
        hooks = hw_hookInsertSort(hooks);
    } else {
        hooks = [hookObject];
    }
    
    HW_STORAGE[type][hook] = hooks;
}

/**
 * Use an insert sort for keeping our hooks organized based on priority. This function is ridiculously faster
 * than bubble sort, etc: http://jsperf.com/javascript-sort
 *
 * @param hooks The custom array containing all of the appropriate hooks to perform an insert sort on.
 * @private
 */
function hw_hookInsertSort(hooks) {
    var tmpHook, j, prevHook;
    for (var i = 1, len = hooks.length; i < len; i++) {
    tmpHook = hooks[i];
    j = i;
    while ((prevHook = hooks[j - 1]) && prevHook.priority > tmpHook.priority) {
        hooks[j] = hooks[j - 1];
        --j;
    }
    hooks[j] = tmpHook;
    }
    return hooks;
}

/**
 * Runs the specified hook. If it is an action, the value is not modified but if it is a filter, it is.
 *
 * @param type 'actions' or 'filters'
 * @param hook The hook ( namespace.identifier ) to be ran.
 * @param args Arguments to pass to the action/filter. If it's a filter, args is actually a single parameter.
 * @private
 */
function _runHook(type, hook, args) {
    var handlers = STORAGE[type][hook];
    if (!handlers) {
    return type === 'filters' ? args[0] : false;
    }
    var i = 0,
    len = handlers.length;
    if (type === 'filters') {
    for (; i < len; i++) {
        args[0] = handlers[i].callback.apply(handlers[i].context, args);
    }
    } else {
    for (; i < len; i++) {
        handlers[i].callback.apply(handlers[i].context, args);
    }
    }
    return type === 'filters' ? args[0] : true;
}

/**
     * Runs the specified hook. If it is an action, the value is not modified but if it is a filter, it is.
     *
     * @param type 'actions' or 'filters'
     * @param hook The hook ( namespace.identifier ) to be ran.
     * @param args Arguments to pass to the action/filter. If it's a filter, args is actually a single parameter.
     * @private
     */
function hw_runHook(type, hook, args) {
    var handlers = HW_STORAGE[type][hook];
    if (!handlers) {
        return type === 'filters' ? args[0] : false;
    }
    var i = 0,
        len = handlers.length;
    if (type === 'filters') {
        for (; i < len; i++) {
            args[0] = handlers[i].callback.apply(handlers[i].context, args);
        }
    } else {
        for (; i < len; i++) {
            handlers[i].callback.apply(handlers[i].context, args);
        }
    }
    return type === 'filters' ? args[0] : true;
}

function getLang() {
    // Try WPML, Polylang, TranslatePress, fallback to <html lang="">
    return (
        (window.wpml_current_language && window.wpml_current_language()) ||
        (window.icl_lang) ||
        document.documentElement.getAttribute('lang')?.slice(0, 2) ||
        'en'
    );
}

/**
 * convets string date in format yyyy-mm-dd to date
 * @param {string} ymd 
 * @returns 
 */
function ymdToLocalDate(ymd) {

  const [y, m, d] = ymd.split('-').map(Number);

  // Use noon to dodge rare DST edge cases:
  return new Date(y, m - 1, d, 12, 0, 0, 0);
}

class GuestField{

    constructor(el, comparisonDate = null){

        this.el = jQuery(el);
        this.yearEl = this.el.find('.age-selector .year'),
        this.monthEl = this.el.find('.age-selector .month'),
        this.dayEl = this.el.find('.age-selector .day'),
        this.ageEl = this.el.find('.guest-age');

        this.messages = {
            'min' : this.el[0].hasAttribute('data-min-age-message') ? this.el.data('min-age-message') : messages.invalid_age,
            'max' : this.el[0].hasAttribute('data-max-age-message') ? this.el.data('max-age-message') : messages.invalid_age,
        }

        this.minAge = this.el[0].hasAttribute('data-min') ? parseInt(this.el.data('min')) : null;
        this.maxAge = this.el[0].hasAttribute('data-max') ? parseInt(this.el.data('max')) : null;

        // bind this instance to the dom element
        el.guestField = this;

        // if not comparison date is passed set it to today
        this.comparisonDate = comparisonDate ? comparisonDate : new Date();

        this.bindEvents();

        // run this the frist time, might be the fields are already filled
        this.updateAgeField();
    }
    
    bindEvents(){

        var _self = this;

        // day
        this.dayEl.on('keyup', function(){

            let val = jQuery(this).val();

            if(val.length >= 1){
                jQuery(this).addClass('is-valid')
                if(val.length == 2){
                    jQuery(this).parent().next().children('select').focus();
                }
                
                _self.updateAgeField();
            }else if(val.lenght < 1 || val === ''){
                jQuery(this).removeClass('is-valid').addClass('is-error');
            }
        });

        this.dayEl.on("change", function() {
            let v = parseInt(this.value);
            if (v < 1) this.value = 1;
            if (v > 31) this.value = 31;
        });

        // month
        this.monthEl.on('change', function(){

            jQuery(this).addClass('is-valid').parent().next().children('input').focus();
            
            _self.updateAgeField();
        });

        this.monthEl.on("change", function() {
            let v = parseInt(this.value);
            if (v < 1) this.value = 1;
            if (v > 12) this.value = 12;
        });

        // year
        this.yearEl.on("change", function() {
            let v = parseInt(this.value),
                max = jQuery(this).attr('max'),
                min = jQuery(this).attr('min');

            if (v < min) this.value = min;
            if (v > max) this.value = max;
        });

        this.yearEl.on('focusout', function(){

            let val = jQuery(this).val();

            if(val.length == 4){
                jQuery(this).addClass('is-valid');
                
                _self.updateAgeField();
            }else{
                jQuery(this).removeClass('is-valid').addClass('is-error');
            }
        });

        // validte the field only when all birth date fields are completed
        this.ageEl.on('change', function(){

            _self.validate();
        });
    }

    /**
     * Update age for a given field
     */
    updateAgeField(){
            
        let year = this.yearEl.val(),
            month = this.monthEl.val(),
            day = this.dayEl.val();

        if(year !== '' && month !== '' && day !== ''){

            let guest_age = new Date(year, (month - 1), day);

            let age = calc_age(guest_age, this.comparisonDate);
            console.log(guest_age, this.comparisonDate, age)
            this.ageEl.val(age).trigger('change');
        }
    }

    /**
     * Check whether the date is valid
     * @returns boolean
     */
    isValidDate(){

        let year = this.yearEl.val(),
            month = this.monthEl.val(),
            day = this.dayEl.val();

        if(year !== '' && month !== '' && day !== ''){

            return isDateValid(year, month, day);
        }

        return false;
    }

    validate(){

        this.removeError();

        // check Age
        let validation = this.isValid()
        if(validation instanceof Error){

            this.displayError(validation);

            this.el.find('.age-selector :input')
                .removeClass('is-valid')
                .addClass('is-invalid');

            return false;
        }

        // remove invalid class
        this.el.find('.age-selector :input')
            .removeClass('is-invalid')
            .addClass('is-valid');

        return true;
    }

    isValid(){

        let age = this.ageEl.val();

        if(age === NaN || age === '' || age === undefined){

            return new Error(messages.invalid_age);
        }

        if(!this.isValidDate()){

            return new Error(messages.invalid_dob);
        }

        if(this.minAge || this.maxAge){

            if(this.minAge){

                if(parseInt(age) < this.minAge){
    
                    return new Error(this.messages.min);
                }
            }
    
            if(this.maxAge){
    
                if(parseInt(age) > this.maxAge){
    
                    return new Error(this.messages.max);
                }
            }        
        }

        return true;
    }

    displayError(error){

        let errorEl = jQuery(`<div class="invalid-feedback age"></div>`);

        let errorMessage = error instanceof Error ? error.message : message;

        this.el.append(errorEl);
        errorEl.text(errorMessage).show();
    }

    removeError(){

        this.el.find('.invalid-feedback.age').remove();
    }
}
/* ─────────────────────────────────────────────────────────────────────────────
 * Public surface
 *
 * This file is the app bundle's entry. The moment it gained an `import` it
 * became an ES module, and webpack wraps entry modules in their own IIFE to
 * isolate them — `output.iife: false` in gulpfile.babel.js only controls the
 * outer bundle wrapper, not per-module scope. Everything declared above is
 * therefore closure-scoped, where it used to leak to `window` by accident.
 *
 * These are the ones other scripts actually call, so they are published
 * deliberately instead. Do not remove without checking the caller listed
 * beside each — they load separately and would fail silently at runtime.
 * ────────────────────────────────────────────────────────────────────────── */
Object.assign(window, {
    blockUI,          // cart.js, utils.js, admin-settings.js, POS bundle
    unblockUI,        // cart.js, utils.js, admin-settings.js, POS bundle
    format_price,     // cart.js
    ymdToLocalDate,   // cart.js
    getLang,          // cart.js, template-parts/home/hero/content/countdown.php
    calc_age,         // assets/js/admin/quote.js
    GuestField,       // cart.js — `new GuestField(...)`, a class rather than a function

    // Hook API — a documented extension point, so the whole surface ships even
    // though only hw_addFilter has a caller inside this repo today.
    hw_addAction,
    hw_doAction,
    hw_removeAction,
    hw_addFilter,
    hw_applyFilters,
    hw_removeFilter,
});
