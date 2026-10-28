<?php

if(is_null($hotel)){
    $hotel = (object) hw_get_hotel();
}

if($hotel->phone_number): ?>
	<div class="detail mb-3">
		<div class="wrapper justify-content-center">
			<div class="icon">
				<i data-feather="phone"></i>
			</div>
			<div class="value">
				<a href="tel: <?= $hotel->phone_number ?>"><?= $hotel->phone_number ?></a>
			</div>
		</div>
	</div>
<?php endif; ?>

<div class="detail mb-3">
	<div class="wrapper  justify-content-center">
		<div class="icon">
			<i data-feather="map-pin"></i>
		</div>
		<div class="value">
			<?= hw_get_hotel_full_address($hotel) ?>
		</div>
	</div>
</div>

<?php if($hotel->airport || !empty($hotel->features['ariport_distance'])): ?>
	<div class="detail mb-3">
		<div class="wrapper  justify-content-center">
			<div class="icon">
				<?= hw_get_svg_icon('airplane') ?>
			</div>
			<div class="value">
				<?php if($hotel->airport): ?>
					<div><?= $hotel->airport['name'] ?></div>
				<?php endif; ?>
				<?php if($hotel->features['ariport_distance']): ?>
					<?= $hotel->features['ariport_distance'] ?>
				<?php endif; ?>
			</div>
		</div>
	</div>
<?php endif; ?>

                
            