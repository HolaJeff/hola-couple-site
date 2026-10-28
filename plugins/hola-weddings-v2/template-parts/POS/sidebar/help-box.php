<!-- Help Box -->
<div class="help-box text-center mt-3 bg-white rounded p-5 d-none d-lg-block">
    <img class="mb-2" src="<?= HW_ASSETS_URL ?>/img/customer-support.png" style="height: 48px"/>
    <div class="text-uppercase font-weight-bold mb-3">Need Assistance?</div>
    <?php /* tenant-support */ $hs = function_exists('hw_tenant_support') ? hw_tenant_support() : array('email' => get_option('admin_email'), 'phone' => '', 'facebook' => '', 'instagram' => '', 'twitter' => '', 'pinterest' => ''); ?>
    <?= function_exists('hw_tenant_support_lines') ? hw_tenant_support_lines() : '<div><a href="mailto:' . esc_attr(get_option('admin_email')) . '">' . esc_html(get_option('admin_email')) . '</a></div>' ?>
</div>
<!-- end Help Box -->