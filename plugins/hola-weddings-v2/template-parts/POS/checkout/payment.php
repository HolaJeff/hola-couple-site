<div id="card-form" class="mt-3 bg-white p-3 p-md-5 rounded">
    <p class="mt-0 text-uppercase font-weight-bold"><?= __('Payment Method', 'hola-weddings') ?></p>  

    <div class="fields mb-3 mt-3">

        <?= hw_get_template('template-parts/partials/forms/card-form') ?>
        
        <div>
            <button id="clear-card-fields" type="button" class="btn btn-link p-0 text-small" style="font-size: 0.9em">
                <?= __('Clear Card Fields', 'hola-weddings') ?>
            </button>
        </div>
    </div>
</div>