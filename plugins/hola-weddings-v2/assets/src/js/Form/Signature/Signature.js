import SignatureModal from "./Modal";

export default class eSignature{

    constructor(el){

        this.el = el
        this.$el = jQuery(this.el);
        this.modal = SignatureModal.getInstance();
        this.signatureHiddenInput = this.$el.find('[data-hidden-input]');
        this.signaturePlaceholder = this.$el.find('[data-placeholder]');
        this.triggerBtn = this.$el.find('[data-trigger]');
        
        this.bindEvents();
    }

    bindEvents(){
        const self = this;
        this.triggerBtn.on('click', function(){
            self.modal.open(function(canvas){
                self.handle(canvas)
            });
        });
    }

    handle(canvas){
        
        let img_code = canvas.toDataURL("image/png"),
            img = jQuery('<img class="border" />').attr('src', img_code);
        
        this.signaturePlaceholder.html(img).show();   
        this.signatureHiddenInput.val(img_code);
    }

    hasSignature(){
        let val = this.signatureHiddenInput.val();
        return val !== '' && val !== undefined && val !== null;
    }
}