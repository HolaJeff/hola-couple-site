<?php
global $hw_theme;

$display_separator = hw_evaluate_string_to_boolean($hw_theme['header_logo_display_separator'] ?? '1');
$separator = $hw_theme['header_logo_separator_char'] ?? '';
$wedding = hw()->getWedding();
?>
<div id="logo" class="logo">

    <div class="logo-wrapper">

        <?php do_action('hw_theme_before_logo'); ?>
        
        <a href="/">
            <?= $wedding->getHerName() ?>
            <?php if($display_separator && !empty($separator)): ?>
                <span class="separator"><?= $separator ?></span>
            <?php endif; ?>
            <?= $wedding->getHisName() ?>
        </a>

        <?php do_action('hw_theme_after_logo'); ?>
    </div>

</div>