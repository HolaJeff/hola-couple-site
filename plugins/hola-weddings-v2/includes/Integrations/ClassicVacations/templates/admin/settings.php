<?php

use HW\Integrations\ClassicVacations\Admin;

$page_slug = Admin::$options_page_slug;

$page = 'connection';
if( isset( $_GET[ 'tab' ] ) ) {
  $page = $_GET[ 'tab' ];
}
?>
<h1>Classic Vacations</h1>

<?= hw_print_notices(); ?>

<h2 class="nav-tab-wrapper">
    <a href="?page=<?= $page_slug ?>&tab=connection" class="nav-tab <?php echo $page == 'connection' ? 'nav-tab-active' : ''; ?>">Connection</a>
    <a href="?page=<?= $page_slug ?>&tab=settings" class="nav-tab <?php echo $page == 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
</h2>

<?php $this->get_template('admin/settings/' . $page );