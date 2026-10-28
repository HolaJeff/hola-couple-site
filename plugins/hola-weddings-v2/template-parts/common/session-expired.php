<div id="session-expired" class="text-center py-5">
    <div class="container">
        <img src="<?= HW_ASSETS_URL ?>/img/computer-sad.png" />
        <h1 class="mt-5"><?= __('Ops! Your session has expired.', 'hola-weddings' ); ?></h1>
        <?php if(isset($message)): ?>
            <p class="mt-3"><?= $message ?></p>
        <?php endif; ?>
    </div>
</div>