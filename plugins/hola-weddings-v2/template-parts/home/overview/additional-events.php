<?php 
$additional_events = hw_get_additional_events();

if(!empty($additional_events)): ?>
    <div class="row justify-content-center">
        <?php foreach( $additional_events as $event ): ?>
            <div class="col-md-6 col-lg-4 mb-5 mb-lg-0">
                <div class="event-card inner text-center frame-decoration">
                    <div class="inner">
                        <?php if(!empty($event['name'])): ?>
                            <div class="title font-weight-bold h6 text-uppercase"><?= $event['name'] ?></div>
                        <?php endif; ?>
                        <?php if(!empty($event['date'])): ?>
                            <div class="meta date">
                                <i data-feather="calendar" class="me-2" height="16" width="16"></i>
                                <?= $event['date'] ?>
                            </div>
                        <?php endif; ?>
                        <?php if(!empty($event['time'])): ?>
                            <div class="meta time">
                                <i data-feather="clock" class="me-2" height="16" width="16"></i>
                                <?= $event['time'] ?>
                            </div>
                        <?php endif; ?>
                        <?php if(!empty($event['location'])): ?>
                            <div class="meta location">
                                <i data-feather="map-pin" class="me-2" height="16" width="16"></i>
                                <?= $event['location'] ?>
                            </div>
                        <?php endif; ?>
                        <?php if(!empty($event['dress_code'])): ?>
                            <div class="meta dress-code">
                                <?php hw_get_svg_icon('hanger')  ?> 
                                <?= $event['dress_code'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>        
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>