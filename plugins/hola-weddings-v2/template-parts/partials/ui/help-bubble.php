<div id="help-bubble" class="position-fixed bottom-0 right-0 d-lg-none w-100">
    <div class="bubble mb-3 mr-3">
        <div class="trigger">
            <img src="<?= HW_ASSETS_URL ?>/img/customer-support.png" />
        </div>
        <div class="popup">
            <div class="text-uppercase font-weight-bold mb-3">Need Assistance?</div>
            <?php /* tenant-support */ $hs = function_exists('hw_tenant_support') ? hw_tenant_support() : array('email' => get_option('admin_email'), 'phone' => '', 'facebook' => '', 'instagram' => '', 'twitter' => '', 'pinterest' => ''); ?>
            <?= function_exists('hw_tenant_support_lines') ? hw_tenant_support_lines() : '<div><a href="mailto:' . esc_attr(get_option('admin_email')) . '">' . esc_html(get_option('admin_email')) . '</a></div>' ?>
        </div>
    </div>
</div>