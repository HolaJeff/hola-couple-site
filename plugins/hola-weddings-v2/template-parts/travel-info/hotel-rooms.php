<div id="rooms-section" class="section">
    <div class="container">
        <h2 class="mt-3 section-title"><?= __('Accommodations', 'hola-weddings'); ?></h2>
        <?php if(hw_get_mode() == 'room' ): ?>
            <?php hw_get_template('template-parts/hotel/rooms', ['hotel' => $hotel]); ?>
        <?php else: ?>
            <?php hw_get_template('template-parts/hotel/packages', ['hotel' => $hotel]); ?>
        <?php endif; ?>
    </div>
</div>