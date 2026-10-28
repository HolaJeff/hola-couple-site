export default class TermsConditions{

    constructor(el){

        this.el = el;
        this.modal = jQuery('#termsConditionsModal');
        this.acceptBtn = this.modal.find('.accept-btn');
        this.scrollDownTriggers = this.modal.find('[data-scroll-bottom]');

        this.read = false;
        this.accepted = false;

        this.addEventListeners();
    }

    addEventListeners(){

        var _self = this;

        this.modal.find('.modal-body').on('scroll', function(){

            if ( ( Math.floor(this.scrollHeight -  jQuery(this).scrollTop() ) - 50  ) <=  Math.floor( jQuery(this).outerHeight() ) ) {
                
                _self.read = true;
                _self.acceptBtn.removeAttr('disabled');
            }
        });

        this.acceptBtn.on('click', function(){

            if(jQuery(this).is(':disabled') === false){

                jQuery(_self).trigger('hw.tc.accepted');

                _self.modal.modal('hide')
                _self.accepted = true;
            }
        });

        this.el.on('change', function(){

            if(!_self.isAccepted()){
                
                _self.el.prop('checked', false);
                _self.show();
            }
        });

        jQuery(this).on('hw.tc.accepted', function(){

            _self.el.prop('checked', true).removeClass('is-invalid');
        });

        this.scrollDownTriggers.on('click', function(){

            let scrollHeight = _self.modal.find('.modal-body')[0].scrollHeight;
            _self.modal.find('.modal-body').animate ({
                scrollTop: scrollHeight
            });
        });
    }

    isAccepted(){

        return this.accepted;
    }

    show(){

        this.modal.modal('show');
    }

    hide(){

        this.modal.modal('hide');
    }
}