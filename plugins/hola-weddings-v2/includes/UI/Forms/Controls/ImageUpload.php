<?php

namespace HW\UI\Forms\Controls;

class ImageUpload extends FormControl {

    protected function getType(): string {
        return 'image_upload';
    }

    protected function renderControl(): void {
        $value = $this->getValue();
        ?>
        <div id="<?= esc_attr($this->getId()) ?>" class="image_uploader">

            <div class="image_preview_wrapper position-relative">
                <?php if ($value): ?>
                    <?php echo wp_get_attachment_image( // phpcs:ignore WordPress.Security
                        $value,
                        'medium',
                        false,
                        [
                            'class' => 'mcweb-preview-image rounded',
                            'style' => 'width:100%; height: 300px; object-fit: cover;',
                        ]
                    ); ?>
                <?php else: ?>
                    <img class="mcweb-preview-image" src="" style="display:none; max-width:200px; height: auto;">
                <?php endif; ?>

                <button type="button" class="btn btn-danger remove-image-control position-absolute top-0 end-0 mt-3 me-3" style="<?= $value ? '' : 'display:none;' ?>">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>

            <p class="set-image-control" style="<?= $value ? 'display:none;' : '' ?>">
                <a href="#"><?php _e('Set Image', 'hola'); ?></a>
                <input class="upload_field" name="<?= esc_attr($this->getName()) ?>" value="<?= esc_attr($value) ?>" type="hidden">
            </p>

            <?php wp_nonce_field($this->getId() . '_nonce', $this->getId() . '_nonce', true, true); ?>
        </div>
        <?php
    }
}
