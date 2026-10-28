class SignatureModalClass{
    
    constructor(){

        this.$el = jQuery('#signatureModal');
        this.canvas = document.getElementById('signature-canvas');
        this.signatureInput = this.$el.find('[data-type-input]');
        this.signaturePad = new SignaturePad(this.canvas);
        this.acceptBtn = this.$el.find('[data-accept]');
        this.clearBtn = this.$el.find('[data-clear]');
        this.undoBtn = this.$el.find('[data-undo]');
        this.callback = () => {};

        // init modal
        //this.$el.modal();

        // bind events
        this.bind();
    }

    bind(){
        var self = this;

        // clear event
        this.clearBtn.on('click', function(){
            self.clear();
        });

        // undo event
        this.undoBtn.on('click', function () {
            var data = self.signaturePad.toData();
            if (data) {
                data.pop(); // remove the last dot or line
                self.signaturePad.fromData(data);
            }
        });

        // accept event
        this.acceptBtn.on('click', function(){
    
            if(self.isSignatureEmpty()){
                alert('Signature is empty.')
                return;
            }

            let trimmed = self.getTrimmedCanvas();
            // pass the trimmed canvas to the callback function
            self.callback(trimmed);

            self.close(); 
            
        });

        // input events
        this.signatureInput.on('keyup', () => { self.updateSignature() });
        this.$el.find('input[name="signature-font-type"]').on('change', () => { return self.updateSignature() });
    }
    
    /**
     * Open the modal
     */
    open( callback ){
        this.callback = callback;
        this.$el.modal('show');
    }

    /**
     * close the modal
     */
    close(){
        this.$el.modal('hide');
        this.clear();
    }

    /**
     * Clear
     */
    clear(){
        this.signaturePad.clear();
        this.signatureInput.val('');
    }

    /**
     * Create a copy of the canvas and trim the white space
     *
     * @return html object
     */
    getTrimmedCanvas(){
        
        let copy = document.createElement('canvas');
        // copy the canvas
        copy.width = this.canvas.width
        copy.height = this.canvas.height
        copy.getContext('2d').drawImage(this.canvas, 0, 0)
        // then trim it
        return trimCanvas(copy);
    }

    /**
     * Update the signature from text input
     *
     * @return void
     */
    updateSignature(){

        var ctx = this.canvas.getContext("2d"),
            val = this.signatureInput.val(),
            font = this.$el.find('input[name="signature-font-type"]:checked').val(),
            max_width = this.canvas.width;
            
        this.signaturePad.clear();
        ctx.font = "42px " + font;
        ctx.fillStyle = "black";
        ctx.textAlign = "center";

        //ctx.fillText(val, canvas.width/2, canvas.height/2);
        printAtWordWrap(ctx, val, this.canvas.width/2, this.canvas.height * 0.66, 40, this.canvas.width);
    }

    /**
     * Check if the signature is empty
     *
     * @return boolean
     */
    isSignatureEmpty(){

        var blank = document.createElement('canvas');
        blank.width = this.canvas.width;
        blank.height = this.canvas.height;

        blank.getContext('2d').clearRect(0, 0, this.canvas.width, this.canvas.height);
        return this.canvas.toDataURL() == blank.toDataURL();
    }
}

var SignatureModal;

jQuery(function(){
    SignatureModal = new SignatureModalClass();
});

class Signature{

    constructor(el){

        this.el = document.getElementById(el);
        this.$el = jQuery(this.el);
        this.signatureHiddenInput = this.$el.find('[data-hidden-input]');
        this.signaturePlaceholder = this.$el.find('[data-placeholder]');
        this.triggerBtn = this.$el.find('[data-trigger]');
        
        this.bindEvents();
    }

    bindEvents(){
        const self = this;
        this.triggerBtn.on('click', function(){
            SignatureModal.open(function(canvas){
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
}

/**
 * Utility functions
 */
function printAtWordWrap( context , text, x, y, lineHeight, fitWidth)
{
    fitWidth = fitWidth || 0;
    
    if (fitWidth <= 0)
    {
        context.fillText( text, x, y );
        return;
    }
    var words = text.split(' ');
    var currentLine = 0;
    var idx = 1;

    while (words.length > 0 && idx <= words.length)
    {
        var str = words.slice(0,idx).join(' ');
        var w = context.measureText(str).width;
        if ( w > fitWidth )
        {
            if (idx==1)
            {
                idx=2;
            }
            context.fillText( words.slice(0,idx-1).join(' '), x, y - (lineHeight*currentLine) );
            currentLine++;
            words = words.splice(idx-1);
            idx = 1;
        }
        else{
            idx++;
        }
    }

    if(idx > 0){
        context.fillText( words.join(' '), x, y - (lineHeight*currentLine) );
    }
        
}