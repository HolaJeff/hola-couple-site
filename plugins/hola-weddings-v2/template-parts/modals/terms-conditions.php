<?php 
$terms = get_network_option(null, 'hw_global_terms_conditions');
?>
<!-- Terms Condition modal -->
<div class="modal fade" id="termsConditionsModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel"><?= __('Terms & Conditions', 'hola-weddings') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-5">
                <?= wpautop( $terms) ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link" data-scroll-bottom>
                    <i class="fas fa-arrow-down"></i>    
                    <?= __('Go to bottom', 'hola-weddings') ?>
                </button>
                <button disabled type="button" class="btn btn-primary accept-btn"><?= __('Accept', 'hola-weddings') ?></button>
            </div>
        </div>
    </div>
</div>