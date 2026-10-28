<?php 
$additional_events = hw_get_additional_events();

if(!empty($additional_events)): ?>
    <div class="row justify-content-center">
        <?php foreach( $additional_events as $event ): ?>
            <div class="col-md-6 col-lg-4 mb-5 mb-lg-0">
                <div class="event-card inner bg-color decoration-left p-3 p-lg-5 text-center frame-decoration">
                    <div class="inner">
                        <?php if(!empty($event['name'])): ?>
                            <div class="h5 primary-color font-weight-normal"><?= $event['name'] ?></div>
                        <?php endif; ?>
                        <?php if(!empty($event['date'])): ?>
                            <div class="meta date">
                                <i data-feather="calendar" class="me-2 primary-color" height="16" width="16"></i>
                                <?= $event['date'] ?>
                            </div>
                        <?php endif; ?>
                        <?php if(!empty($event['time'])): ?>
                            <div class="meta time">
                                <i data-feather="clock" class="me-2 primary-color" height="16" width="16"></i>
                                <?= $event['time'] ?>
                            </div>
                        <?php endif; ?>
                        <?php if(!empty($event['location'])): ?>
                            <div class="meta location">
                                <i data-feather="map-pin" class="me-2 primary-color" height="16" width="16"></i>
                                <?= $event['location'] ?>
                            </div>
                        <?php endif; ?>
                        <?php if(!empty($event['dress_code'])): ?>
                            <div class="meta dress-code">
                                <span class="primary-color"><?php hw_get_svg_icon('hanger')  ?></span>
                                <?= $event['dress_code'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>        
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>