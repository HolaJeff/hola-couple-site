class ErrorAlert{

    constructor( pos ){;

        this.el = pos.el.find('[data-error-message]');
    }

    /**
     * set alert content
     * @param {string} message 
     */
    setMessage(message){
        
        this.el.find('.message').html(message);

        return this;
    }

    /**
     * shows the alert
     * @param {boolean} focus 
     * @returns this
     */
    show( focus = true ){

        this.el.show();
        this.el[0].scrollIntoView({ behavior: "smooth", block: "end" });

        return this;
    }

    /**
     * hide the alert
     * @returns this
     */
    hide(){

        this.el.hide();

        return this;
    }
}

export default ErrorAlert;
