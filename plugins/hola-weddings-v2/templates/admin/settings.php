<?php

if(empty($tabs)){
    
    return;
}

$last_tab = isset($_POST['last_tab']) ? $_POST['last_tab'] : '';

?>
<div x-data="settingsController()" class="settingswrapper mr-4 mt-5">
    <form id="settings-form" method="post" action="<?= menu_page_url( 'holaweddings', false ) ?>">
        <div class="panel-wrapper bg-white border">
            <div class="header border-bottom bg-white py-2 px-3 text-end d-flex">
                <div class="brand">
                    <h5 class="mt-2"><?= $title ?: 'Settings' ?></h5>
                </div>
                <div class="options text-end flex-fill">
                    <button class="btn btn-primary">Save</button>
                </div>
            </div>
            <div class="body d-flex">
                <div class="nav nav-tabs flex-column border-bottom-0 border-end" id="nav-tab" role="tablist">
                    <?php $j = 1; foreach($tabs as $id => $tab): ?>
                        <!--<li class="nav-item" role="presentation">-->
                            <button class="nav-link text-start <?= $j == 1 ? 'active' : '' ?>" id="<?= $id ?>-tab" data-bs-toggle="tab" data-bs-target="#<?= $id ?>" type="button" role="tab">
                                <?= isset($tab['icon']) ? $tab['icon'] : '' ?><?= $tab['label'] ?>
                            </button>
                        <!--</li>-->
                    <?php $j++; endforeach; ?>
                </div>
                <div class="tab-content flex-fill" id="nav-TabContent">
                    <?php $j = 1; foreach($tabs as $id => $tab): ?>
                        <div class="tab-pane fade py-3 <?= $j == 1 ? 'show active' : '' ?>" id="<?= $id ?>" role="tabpanel" aria-labelledby="<?= $id ?>-tab">
                            <h2><?= $tab['label'] ?></h2>
                            <?php if(is_callable($tab['callback'])): ?>
                                <?php call_user_func_array( $tab['callback'], [ $id, $form ]); ?>
                            <?php endif; ?>
                        </div>
                    <?php $j++; endforeach; ?>
                </div>
            </div>
            <div class="footer border-top p-2 text-end">
                <button class="btn btn-primary">Save</button>
            </div>
        </div>
        <input type="hidden" name="action" value="<?= $action ?: 'hw_save_settings' ?>" />
        <input type="hidden" name="hw_cart_mode" value="room" />
        <?php wp_nonce_field( 'hw-save-settings' ); ?>
    </form>
</div>