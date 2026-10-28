<div id="contact" class="section overflow-hidden">
    <div class="container">
        <h2 class="section-title"><?= __('Address & Map', 'hola-weddings'); ?></h2>
        <div class="contact-details row m-0 flex-column-reverse flex-lg-row">
            <div class="col-lg-4 p-0 flex-column pt-md-4">
                <div class="primary-bg frame-decoration text-white">
                    <?php hw_get_template('template-parts/hotel/contacts'); ?>
                </div>              
            </div>
            <div class="col-lg-8 p-0">
                <div class="map-wrapper">
                    <div id="map" style="height: 450px;"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script type="text/javascript">
        jQuery(function($){
            
            let options = {
                lat: '<?php echo $hotel->lat ?>',
                long: '<?php echo $hotel->long ?>',
                zoom: 15,
            };
            
            new Gmap( $('#map')[0], options );
        });
    </script>
</div>