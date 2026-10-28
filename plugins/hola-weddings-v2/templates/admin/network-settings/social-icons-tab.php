<?php
$social_links = hw_get_network_social_links();
if(is_string($social_links) && empty($social_links)){
    $social_links = [];
}
?>
<div class="mb-3">
    <?php $form->text('data[hw_social_links][facebook]','Facebook', $social_links['facebook'] ?: '', null, ''); ?>
</div>
<div class="mb-3">
    <?php $form->text('data[hw_social_links][instagram]','Instagram', $social_links['instagram'] ?: '' , null, ''); ?>
</div>
<div class="mb-3">
    <?php $form->text('data[hw_social_links][twitter]','Twitter / X', $social_links['twitter'] ?: '' , null, ''); ?>
</div>
<div class="">
    <?php $form->text('data[hw_social_links][pinterest]','Pinterest', $social_links['pinterest'] ?: '' , null, ''); ?>
</div>