<div class="text-center text-danger" style="font-size: 64px">
    <i class="fas fa-times-circle"></i>
</div>
<p class="title text-warning mt-3 text-danger"><?= __('Transaction Canceled', 'hola-weddings') ?></p>
<p class="mt-3"><?php /* tenant-support */ echo sprintf(__('The transaction has been canceled. Please, contact our support team at %s', 'hola-weddings'), function_exists('hw_tenant_support_link') ? hw_tenant_support_link() : '<a href="mailto:' . esc_attr(get_option('admin_email')) . '">' . esc_html(get_option('admin_email')) . '</a>'); ?></p>
