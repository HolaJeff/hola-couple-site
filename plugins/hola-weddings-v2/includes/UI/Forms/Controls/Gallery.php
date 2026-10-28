<?php

namespace HW\UI\Forms\Controls;

class Gallery extends FormControl {

    protected string $addLabel = 'Select Images';

    protected function getType(): string {
        return 'gallery';
    }

    public function addLabel(string $label): static {

        $this->addLabel = $label;

        return $this;
    }

    protected function renderControl(): void {

        $ids = array_filter(
            explode(',', (string) $this->getValue())
        );
        ?>
        <div class="hw-gallery">

            <ul class="hw-gallery-list d-flex flex-wrap gap-2 p-0 mb-2">
                <?php foreach ($ids as $id): ?>
                    <?php $thumb = wp_get_attachment_image_url($id, 'medium'); ?>
                    <?php if ($thumb): ?>
                        <li class="hw-gallery-image position-relative" data-attachment_id="<?= esc_attr($id) ?>">
                            <img class="rounded" src="<?= esc_url($thumb) ?>" style="width:150px;height:150px;object-fit:cover;" />
                            <button type="button" title="Remove" class="hw-gallery-remove position-absolute btn btn-danger top-0 end-0 me-2 mt-2">&times;</button>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>

            <button
                type="button"
                class="btn btn-primary hw-gallery-add"
                data-choose="<?= esc_attr($this->addLabel) ?>"
                data-update="<?= esc_attr__('Add to gallery', 'hola') ?>"
            >
                <?= esc_html($this->addLabel) ?>
            </button>

            <input
                type="hidden"
                class="hw-gallery-field"
                name="<?= esc_attr($this->getName()) ?>"
                value="<?= esc_attr($this->getValue()) ?>"
            >
        </div>
        <?php
    }
}
