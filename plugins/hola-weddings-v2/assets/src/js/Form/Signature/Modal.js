import { printAtWordWrap } from "./Utils";
import trimCanvas from "trim-canvas";
import SignaturePad from "signature_pad";

export default class SignatureModal{

    instance = undefined;

    static getInstance() {

        if(this.instance == undefined){

            this.instance = new this();
        }

        return this.instance;
    }
    
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