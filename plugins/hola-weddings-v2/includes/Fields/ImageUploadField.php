<?php

namespace HW\Fields;

class ImageUploadField extends AbstractField{
    
    public function render(){
        
        $image = ' button">Upload image';
        $image_size = 'full'; // it would be better to use thumbnail size here (150x150 or so)
        $display = 'none'; // display state ot the "Remove image" button
     
        if( $image_attributes = wp_get_attachment_image_src( $this->value, $image_size ) ) {
     
            // $image_attributes[0] - image URL
            // $image_attributes[1] - image width
            // $image_attributes[2] - image height
     
            $image = '"><img src="' . $image_attributes[0] . '" style="max-width:95%;display:block;" />';
            $display = 'inline-block';
     
        } 
     
        ?>
        <div id="<?php echo $this->id ?>" class="image_uploader">
            
            <div class="image_preview_wrapper">
                <?php if( $this->value ) : ?>
						<?php
                        echo wp_get_attachment_image( // phpcs:ignore WordPress.Security
							$this->value,
							'medium',
							false,
							[
								'class' => 'mcweb-preview-image',
                                'style' => 'max-width:100%; height: auto;'
							]
						);
						?>
				<?php else : ?>
					<img class="mcweb-preview-image" src="" style="display:none; max-width:200px; height: auto;">
				<?php endif; ?>
            </div>
            
            <p class="set-image-control" style="<?php echo ( ! $this->value ) ? '' : 'display:none;'; ?>">
				<a href="#"  >
					<?php _e( 'Set Image', 'hola' ); ?>
				</a>
				<input class="upload_field" name="<?php echo $this->name ?>" value="<?php echo esc_attr( $this->value ); ?>" type="hidden">
			</p>

			<p class="remove-image-control" style="<?php echo ( ! $this->value ) ? 'display:none;' : ''; ?>">
				<a href="#" >
					<?php _e( 'Remove Image', 'hola' ); ?>
				</a>
			</p>

			<?php wp_nonce_field( $this->id . '_nonce',  $this->id . '_nonce', true, true ); ?>
        </div>
        <?php
    }
}