<div class="love-story-timeline <?php if( $display_image): ?>image-position-<?= $image_position ?><?php endif; ?>  position-relative">
    <?php if(!empty($timeline)): ?>
        <span class="main-line d-none d-md-block"></span>
        <?php foreach($timeline as $item): ?>
            <div class="timeline-item">
                <span class="line"></span>
                <div class="inner d-flex flex-column flex-md-row">
                    <div class="left-col">
                        <?php if( $display_image && $item['image'] && $image_position == 'opposite'): ?>
                            <img class="image mb-3 mb-md-0" src="<?= wp_get_attachment_image_url( $item['image'], 'medium') ?>" />
                        <?php endif; ?>
                    </div>
                    <div class="right-col details">
                        <span class="circle"></span>

                        <?php if( $display_image && $item['image'] && $image_position == 'above'): ?>
                            <img class="image mb-2" src="<?= wp_get_attachment_image_url( $item['image'], 'medium') ?>" />
                        <?php endif; ?>

                        <?php if( !empty($item['title']) || !empty($item['year']) ): ?>
                            <h3 class="title mb-2">
                                <?php if( !empty($item['year']) && $display_image && $image_position == 'opposite'): ?>
                                    <span class="mr-2">
                                        <?= $item['year'] ?>
                                        <i class="fas fa-circle" style="font-size: 4px;"></i>
                                    </span>
                                <?php endif; ?>

                                <?php if( !empty($item['title']) ): ?>
                                    <?= $item['title'] ?>
                                <?php endif; ?>
                            </h3>
                        <?php endif; ?>

                        <?php if(!empty($item['description'])): ?>
                            <div class="description"><?= $item['description'] ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
    <?php else: ?>
        <p class="text-center"><?= __('No items in the timeline.', 'hola-weddings') ?></p>
    <?php endif; ?>
</div>