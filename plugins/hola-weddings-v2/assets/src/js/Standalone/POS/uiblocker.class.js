class UIBlocker{

    current_view = 'processing';

    constructor(){

        let _self = this;

        this.el = jQuery('#payment-processing-message');
        this.displayView(this.current_view);

        this.el.find('[data-close]').click(function(){
            _self.close();
        })
    }

    show(){
        jQuery.blockUI({
            message: this.el,
            centerY: true,
            centerX: true,
            bindEvents: false,
            css:{
                top: '15%',
                border: '0'
            },
            overlayCSS:  { 
                backgroundColor: 'rgba(255,255,255,0.9)',
            }
        }); 

        return this;
    }

    close(){

        var _self = this;

        jQuery.unblockUI({
            onUnblock: () => { _self.reset(); }
        });
        

        return this;
    }

    reset(){
        this.displayView('processing');
    }

    displayView(view_name){
        
        let viewEl = this.#getViewEl(view_name),
            currentViewEl = this.#getViewEl(this.current_view);

        if(viewEl.length > 0){
            currentViewEl.hide();
            viewEl.show();
            this.current_view = view_name;
        }
    }

    #getViewEl(view){

        return this.el.find('[data-view="'+view+'"]');
    }

    setErrorMessage(message){

        this.el.find('[data-error-message]').html(message);

        return this;
    }
}

export default UIBlocker;