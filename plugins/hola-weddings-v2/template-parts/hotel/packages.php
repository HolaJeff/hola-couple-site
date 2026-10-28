<?php

$packages = get_packages();

?>
<div id="packages" class="mt-5">
    <h3 class="mt-3"><?= __('Packages', 'hola-weddings'); ?></h3>
    <?php if($packages->have_posts()): ?>
        <div class="package-list">
            <?php while($packages->have_posts()):
                $packages->the_post();
                $room = (object) hw_get_room(get_field('hw_room'));
                $from = get_field('from');
                $to = get_field('to');
                $id = get_the_ID();
                ?>
                <div class="package mb-3" data-toggle="collapse" data-target="#item_<?= $id ?>_body_collapse">
                    <div class="inner">
                        <div class="header d-flex">
                            <div class="image" style="background-image: url(<?= $room->main_image ?>)"></div>
                            <div class="content d-flex">
                                <div class="overview">
                                    <h3 class="title mb-2"><?= $room->name ?></h3>
                                    
                                    <div class="dates mb-2">
                                        <strong><?= __('From', 'hola-weddings'); ?>:</strong>
                                        <span><?= (new DateTime($from))->format('F d, Y') ?></span>
                                        -
                                        <strong><?= __('To', 'hola-weddings'); ?>:</strong>
                                        <span><?= (new DateTime($to))->format('F d, Y') ?></span>
                                    </div>
                                    
                                    <div class="features d-flex">
                                        <div class="feature mr-3">
                                            <i class="fas fa-plane mr-2"></i>
                                            <?= __('Flight Included', 'hola-weddings') ?>
                                        </div>
                                        <div class="feature mr-3">
                                            <i class="fas fa-shuttle-van"></i>
                                            <?= __('Airport Transportation Included', 'hola-weddings') ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="actions border-left px-5 d-flex flex-column justify-content-center">
                                    <div class="cta text-center">
                                        <i class="far fa-question-circle"></i>
                                        <p><?= __('View Details', 'hola-weddings') ?></p>
                                    </div>
                                </div>
                            </div>
                        </div><!-- Header -->
                        <div id="item_<?= $id ?>_body_collapse" class="body collapse p-5">
                            <div class="row">
                                <div class="col-sm-6 border-right">
                                    <h6 class="text-uppercase">
                                        <i class="fas fa-bed mr-3"></i>
                                        <?= __('About the room', 'hola-weddings') ?>
                                    </h6>
                                    <?php if($room->description): ?>
                                        <p class="room__description"><?= $room->description ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-sm-6 pl-5">
                                    <?= hw_get_template('template-parts/common/flights', ['id' => get_the_ID()]) ?>
                                </div>
                            </div>
                        </div><!-- body -->
                    </div><!-- inner -->
                </div><!-- item -->
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
    <?php else: ?>
        <p class="alert alert-warning"><?= __('Sorry, there are no packages available.', 'hola-weddings') ?></p>
    <?php endif; ?>
</div>