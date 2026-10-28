<div id="contact" class="section mt-5">
    <div class="container">
        <h2 class="section-title"><?= __('Address & Map', 'hola-weddings'); ?></h2>
        
        <div id="map" style="height: 450px;"></div>

        <div class="contact-details d-flex flex-column flex-md-row flex-wrap flex-lg-nowrap mt-5">
            <?php hw_get_template('template-parts/hotel/contacts'); ?>
        </div>
    </div>
    
    <script type="text/javascript">
        jQuery(function($){
            
            let options = {
                lat: '<?php echo $hotel->lat ?>',
                long: '<?php echo $hotel->long ?>',
                zoom: 14,
            };
            
            new Gmap( $('#map')[0], options );
        });
    </script>
</div>