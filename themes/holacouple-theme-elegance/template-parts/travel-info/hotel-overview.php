<div id="hotel" class="hotel-overview section pt-0 overflow-hidden">
    <div class="container">
        <h2 class="text-center mb-5 section-title"><?= sprintf(__('About %s', 'hola-weddings'), $hotel->name); ?></h2>

        <div class="row m-0 flex-column-reverse flex-lg-row">
            <div class="col-lg-6 p-lg-0">
                <div class="content mt-5 frame-decoration">
                    <?php hw_get_template('template-parts/hotel/overview', ['hotel' => $hotel]); ?>
                </div>
            </div>
            <div class="col-lg-6 p-lg-0">
                <?php hw_get_template('template-parts/hotel/gallery', ['hotel' => $hotel]); ?>
            </div>
        </div>

        <div class="mt-4 px-3">
            <?php hw_get_template('template-parts/hotel/description'); ?>
        </div>
        
        <?php hw_get_template('template-parts/hotel/amenities') ?>
        
    </div>
</div>