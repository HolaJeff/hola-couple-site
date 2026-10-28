<?php

use HW\Integrations\ClassicVacations\Admin;

$page_slug = Admin::$test_page_slug;

$page = 'group-overview';
if( isset( $_GET[ 'tab' ] ) ) {
  $page = $_GET[ 'tab' ];
}
?>
<h1>Classic Vacations API Tests</h1>

<?= hw_print_notices(); ?>

<h2 class="nav-tab-wrapper">
    <a href="?page=<?= $page_slug ?>&tab=group-overview" class="nav-tab <?php echo $page == 'group-overview' ? 'nav-tab-active' : ''; ?>">Group Overview</a>
    <a href="?page=<?= $page_slug ?>&tab=booking-details" class="nav-tab <?php echo $page == 'booking-details' ? 'nav-tab-active' : ''; ?>">Booking Details</a>
    <a href="?page=<?= $page_slug ?>&tab=make-payment" class="nav-tab <?php echo $page == 'make-payment' ? 'nav-tab-active' : ''; ?>">Make Payment</a>
    <a href="?page=<?= $page_slug ?>&tab=get-payment" class="nav-tab <?php echo $page == 'get-payment' ? 'nav-tab-active' : ''; ?>">Get Payment</a>
</h2>

<?php $this->get_template('admin/tests/' . $page );