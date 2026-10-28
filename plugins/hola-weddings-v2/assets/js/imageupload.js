jQuery.fn.extend({
    imageupload: function(){
        
        var setAttachment = function( attachment ){
            
            var imageUrl             = '',
				imageId              = '',
				imageAlt             = '',
				imageWidth           = '',
				imageHeight          = '';
                
                console.log(attachment);
                
			if ( 0 < attachment.url.length ) {
					imageUrl    = attachment.url;
					imageId     = attachment.id;
					imageAlt    = attachment.alt;
					imageWidth  = attachment.width;
					imageHeight = attachment.height;
			}

			previewImage.attr( {
						src: imageUrl,
						alt: imageAlt,
						//width: imageWidth,
						//height: imageHeight,
						srcset: '',
						sizes: '',
						//style: ''
			} ).show();
            
            uploadInput.val( imageId ).trigger( 'change' );

			deleteTrigger.show();
            openTrigger.hide();
        };
        
        var _ = this,
            openTrigger = _.find('.set-image-control'),
            deleteTrigger = _.find('.remove-image-control'),
            uploadInput = _.find('.upload_field'),
            previewImage = _.find('.mcweb-preview-image');
        
        openTrigger.on('click', function(){
            
            if ( _.mediaUploader ) {
				_.mediaUploader.open();
				return;
			}

			// Extend the wp.media object
			_.mediaUploader = wp.media( {
				title: 'Choose Image',
				button: {
					text: 'Choose Image'
				}, multiple: false
			} );
			wp.media.frames.file_frame = _.mediaUploader;

			_.mediaUploader.on( 'select', function() {
				var attachment = _.mediaUploader.state().get( 'selection' ).first().toJSON();
				setAttachment( attachment );
			} );

			_.mediaUploader.on( 'open', function() {
				var lib,
					selected,
					attachment,
					selection;

				// Get selected media.
				selected  = uploadInput.val();
				if ( selected ) {

					// Get library.
					lib = _.mediaUploader.state().get( 'library' );
					lib.comparator = function( a, b ) {
						var aInQuery = !! this.mirroring.get( a.cid ),
							bInQuery = !! this.mirroring.get( b.cid );

						if ( ! aInQuery && bInQuery ) {
							return -1;
						}
						if ( aInQuery && ! bInQuery ) {
							return 1;
						}
						return 0;
					};

					// Get attachment and add to library.
					attachment = wp.media.attachment( selected );
					attachment.fetch();
					lib.add( attachment ? [ attachment ] : [] );

					// Make it selected.
					selection = _.mediaUploader.state().get( 'selection' );
					selection.add( attachment ? [ attachment ] : [] );
				} else {
					selection = _.mediaUploader.state().get( 'selection' );
					selection.add( [] );
				}
			} );

			// Open the uploader dialog
			_.mediaUploader.open();

			return false;
        });
        
        deleteTrigger.on('click', function(){
            
            previewImage.attr( {
				src: '',
				alt: '',
			} ).hide();
            
            uploadInput.val( '' ).trigger( 'change' );

			deleteTrigger.hide();
            openTrigger.show();
        });
    }
});

jQuery(function($){
    
    $('.image_uploader').each(function(){
        $(this).imageupload();
    });
});