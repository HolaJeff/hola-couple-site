<div id="contact" class="section hotel-contact-section mt-5">
    <div class="bg-overlay"></div>
    <div class="container">
        <h2 class="section-title"><?= __('Address & Map', 'hola-weddings'); ?></h2>
        <div class="contact-details row mb-3">
            <div class="col-lg-4 flex-column">                
                <?php hw_get_template('template-parts/hotel/contacts'); ?>
            </div>
            <div class="col-lg-8">
                <div id="map" style="height: 350px;"></div>
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