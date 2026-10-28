<div id="things" class="section mt-5 divider-top overflow-hidden">
    <div class="container">
        <div class="card-wrapper">
            <div class="card frame-decoration light">
                <h2 class="mb-5 text-center section-title"><?= __('Things to Know', 'hola-weddings'); ?></h2>
                <h4 class="text-uppercase"><?= __('ALL INCLUSIVE RATES INCLUDE', 'hola-weddings') ?></h4>
                <ul>
                    <li><?= __('Hotel Stay', 'hola-weddings') ?></li>
                    <li><?= __('All meals as well as unlimited alcoholic and non-alcoholic drinks', 'hola-weddings') ?></li>
                    <li><?= __('Shows and entertainment on the property', 'hola-weddings') ?></li>
                    <li><?= __('Non-motorized water sports', 'hola-weddings') ?></li>
                </ul>
                <h4 class="text-uppercase"><?= __('Useful Travel Information', 'hola-weddings') ?></h4>
                <ul>
                    <?php foreach($hotel->country['travel_info'] as $info): ?>
                        <li>
                            <strong><?= $info['title'] ?>:</strong>
                            <?= $info['value'] ?>
                        </li>
                    <?php endforeach; ?>
                    <?php foreach($hotel->state['travel_info'] as $info): ?>
                        <li>
                            <strong><?= $info['title'] ?>:</strong>
                            <?= $info['value'] ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>