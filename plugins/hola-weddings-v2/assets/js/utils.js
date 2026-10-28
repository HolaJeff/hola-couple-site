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
 * clear all inputs passed
 * 
 * @param {array} list 
 */
function clearInputs(list){
    list.each(function(){
            
        switch(this.type){
            case 'checkbox':
            case 'radio':
                jQuery(this).prop('checked', false);
                console.log(jQuery(this).prop('checked'));
                break;
            case 'select':
                jQuery(this).prop('selectedIndex',0);
                break;
            default:
                jQuery(this).val('');
                break;
        }
    })
}

/**
 * Validate date string
 * @param {string} dateStr 
 * @returns bool
 */
function isDateValid(year, month, day) {

    let date = new Date(`${year}/${month}/${day}`);
    
    return date.getFullYear() == parseInt(year) && (date.getMonth()+1) == parseInt(month) && date.getDate() == parseInt(day);
}