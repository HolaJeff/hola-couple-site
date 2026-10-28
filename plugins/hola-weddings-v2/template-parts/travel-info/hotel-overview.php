<div id="hotel" class="hotel-overview section">
    <div class="bg-overlay"></div>
    <div class="container">
        <h2 class="text-center mb-5 section-title"><?= sprintf(__('About %s', 'hola-weddings'), $hotel->name); ?></h2>

        <?php hw_get_template('template-parts/hotel/gallery', ['hotel' => $hotel]); ?>

        <div class="my-5">
            <?php hw_get_template('template-parts/hotel/overview', ['hotel' => $hotel]); ?>
        </div>
        
        <div class="frame-decoration">
            <?php hw_get_template('template-parts/hotel/description'); ?>
        </div>
        
        <?php hw_get_template('template-parts/hotel/amenities') ?>
        
    </div>
</div>