<?php 
$date = hw_get_date(true);
$events = hw_get_events();
$dresscode = hw_get_dress_code();

if($events['ceremony']['active']): ?>
    <div class="col-md-6 col-lg-3 mb-5 mb-lg-0">
        <div class="detail-wrapper ceremony bg-color decoration-left p-3 p-lg-5">
            <div class="inner text-center">
                <div class="icon">
                    <?= hw_get_svg_icon('ceremony'); ?>
                </div>
                <div class="content">
                    <div class="title"><?= __('Ceremony','hola-weddings'); ?></div>
                    <div class="value">
                        <div class="mb-2">
                            <i data-feather="map-pin" class="me-2 primary-color" height="16" width="16"></i>
                            <?= $events['ceremony']['location'] ?>
                        </div>
                        <div>
                            <i data-feather="clock" class="me-2 primary-color" height="16" width="16"></i>
                            <?= $events['ceremony']['time'] ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php if($events['cocktail']['active']): ?>
    <div class="col-md-6 col-lg-3 mb-5 mb-lg-0">
        <div class="detail-wrapper bg-color decoration-left p-3 p-lg-5">
            <div class="inner text-center">
                <div class="icon"><?= hw_get_svg_icon('cocktail'); ?></div>
                <div class="content">
                    <div class="title"><?= __('Cocktail Hour','hola-weddings'); ?></div>
                    <div class="value">
                        <div class="mb-2">
                            <i data-feather="map-pin" class="me-2 primary-color" height="16" width="16"></i>
                            <?= $events['cocktail']['location'] ?>
                        </div>
                        <div>
                            <i data-feather="clock" class="me-2 primary-color" height="16" width="16"></i>
                            <?= $events['cocktail']['time'] ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php if($events['reception']['active']): ?>
    <div class="col-md-6 col-lg-3 mb-5 mb-lg-0">
        <div class="detail-wrapper bg-color decoration-left p-3 p-lg-5">
            <div class="inner text-center">
                <div class="icon"><?= hw_get_svg_icon('reception'); ?></div>
                <div class="content">
                    <div class="title"><?= __('Reception','hola-weddings'); ?></div>
                    <div class="value">
                        <div class="mb-2">
                            <i data-feather="map-pin" class="me-2 primary-color" height="16" width="16"></i>
                            <?= $events['reception']['location'] ?>
                        </div>
                        <div>
                            <i data-feather="clock" class="me-2 primary-color"  height="16" width="16"></i>
                            <?= $events['reception']['time'] ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php if(!empty($dresscode)): ?>
    <div class="col-md-6 col-lg-3">
        <div class="detail-wrapper bg-color decoration-left dress-code p-3 p-lg-5">
            <div class="inner text-center">
                <div class="icon"><?= hw_get_svg_icon('dresscode'); ?></div>
                <div class="content">
                    <div class="title"><?= __('Dress Code') ?></div>
                    <div class="value"><?= $dresscode ?></div>    
                </div>
            </div>        
        </div>                
    </div>
<?php endif; ?>