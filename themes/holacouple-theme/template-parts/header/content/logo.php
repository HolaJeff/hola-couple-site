<?php
global $hw_theme;

$display_separator = hw_theme_header_display_logo_separator();
$separator = $hw_theme['header_logo_separator_char'] ?? '';
$wedding = hw()->getWedding();
?>
<div id="logo" class="logo">
    <div class="logo-wrapper">
        <a href="/">

            <?php do_action('hw_theme_before_logo'); ?>

            <div class="names-wrapper">
                <?= $wedding->getHerName() ?>
                <?php if($display_separator && !empty($separator)): ?>
                    <span class="separator"><?= $separator ?></span>
                <?php endif; ?>
                <?= $wedding->getHisName() ?>
            </div>

            <?php do_action('hw_theme_after_logo'); ?>
        </a>
    </div>

</div>