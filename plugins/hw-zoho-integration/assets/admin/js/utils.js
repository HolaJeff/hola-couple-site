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