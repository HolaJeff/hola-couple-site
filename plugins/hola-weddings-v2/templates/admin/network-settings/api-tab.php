<!-- API Connection -->
<div class="row">
    <div class="col-4">
        <?php $form->text('data[hw_api_host]','API Host', hw_get_api_host() , null, ''); ?>
    </div>
    <div class="col-4">
        <?php $form->text('data[hw_api_base]','API Base', hw_get_api_base(), null, ''); ?>
    </div>
    <div class="col-4">
        <?php $form->text('data[hw_api_token]','API Token', hw_get_api_token(), null, ''); ?>
    </div>
</div>