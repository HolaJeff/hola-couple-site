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
 * formart the a card number
 * @param {string} val 
 * @returns string
 */
function format_card(val){

    return val.match(new RegExp('.{1,4}', 'g')).join("-");;
}

export {
    clearInputs,
    format_card
}