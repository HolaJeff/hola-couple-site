<div id="offcanvas">
    <span class="toggle-close">
        <i class="fa fa-times"></i>
    </span>
    <div class="offcanvas-inner">
        <?php if ( is_active_sidebar( 'offcanvas' ) ) : ?>

            <?php dynamic_sidebar( 'offcanvas' ); ?>
										
        <?php endif; ?>
    </div>
</div>
<div id="offcanvas-overlay"></div>