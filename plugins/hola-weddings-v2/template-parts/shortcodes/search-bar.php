<?php
/**
 * Booking search bar shortcode markup.
 *
 * Alpine port of the booking-form app SearchBox component (dates + guests +
 * button). Behaviour lives in assets/js/search-bar.js (Alpine.data('hwSearchBar')).
 *
 * @var string $layout      "horizontal" | "vertical"
 * @var string $booking_url Resolved booking-form page URL to redirect to.
 */

$layout      = isset( $layout ) && in_array( $layout, [ 'horizontal', 'vertical' ], true ) ? $layout : 'horizontal';
$booking_url = isset( $booking_url ) ? $booking_url : '';
?>
<form
	class="hw-search-bar hw-search-bar--<?php echo esc_attr( $layout ); ?> sb-wrap sb-wrap--inline"
	data-booking-url="<?php echo esc_url( $booking_url ); ?>"
	x-data="hwSearchBar()"
	@submit.prevent="submit()"
>
	<div class="sb-bar">

		<?php /* ── Dates (unified range picker) ── */ ?>
		<div
			class="sb-field sb-field--dates"
			:class="{ 'sb-field--invalid': checkinInvalid() || checkoutInvalid() || minNightsViolation }"
			@click="openPicker()"
			role="button"
			tabindex="0"
			@keydown.enter="openPicker()"
		>
			<span class="sb-label">
				<i class="fas fa-calendar-alt mr-1"></i>
				<?php esc_html_e( 'Travel dates', 'hw' ); ?>
			</span>

			<?php /* Hidden anchor — AirDatepicker positions its popup relative to this */ ?>
			<input x-ref="dateAnchor" type="text" class="sb-anchor" readonly />

			<div class="sb-date-range">
				<div class="sb-date-range__part">
					<span class="sb-value" :class="{ 'sb-value--empty': ! draft.checkin }"
						x-text="draft.checkin ? formatDate(draft.checkin) : '<?php echo esc_js( __( 'Check-in', 'hw' ) ); ?>'"></span>
				</div>
				<span class="sb-date-range__sep">&rarr;</span>
				<div class="sb-date-range__part">
					<span class="sb-value" :class="{ 'sb-value--empty': ! draft.checkout }"
						x-text="draft.checkout ? formatDate(draft.checkout) : '<?php echo esc_js( __( 'Check-out', 'hw' ) ); ?>'"></span>
				</div>
			</div>

			<template x-if="minNights > 1">
				<span class="sb-date-range__disclaimer" :class="{ 'sb-date-range__disclaimer--error': minNightsViolation }">
					<i class="fas fa-info-circle mr-2"></i>
					<span x-text="minNightsMessage()"></span>
				</span>
			</template>
		</div>

		<?php /* ── Guests ── */ ?>
		<div
			class="sb-field sb-field--guests"
			:class="{ 'sb-field--invalid': guestsInvalid() }"
			@click.outside="guestsOpen = false"
		>
			<div
				class="sb-guests-trigger"
				@click="guestsOpen = ! guestsOpen"
				role="button"
				tabindex="0"
				@keydown.enter="guestsOpen = ! guestsOpen"
			>
				<span class="sb-label">
					<i class="fas fa-user-friends"></i>
					<?php esc_html_e( 'Guests', 'hw' ); ?>
				</span>
				<span class="sb-value" x-text="guestSummary()"></span>
			</div>

			<div class="sb-guests-dropdown" x-show="guestsOpen" x-cloak @click.stop>
				<template x-for="type in guestTypes" :key="type.field">
					<div class="sb-guest-row">
						<div>
							<div class="sb-guest-label" x-text="type.label"></div>
							<div class="sb-guest-desc" x-text="type.desc"></div>
						</div>
						<div class="sb-guest-counter">
							<button
								type="button"
								class="sb-counter-btn"
								@click="adjust(type.field, -1)"
								:disabled="(draft[type.field] || 0) <= type.min"
							>&minus;</button>
							<span class="sb-counter-val" x-text="draft[type.field] || 0"></span>
							<button
								type="button"
								class="sb-counter-btn"
								@click="adjust(type.field, 1)"
							>+</button>
						</div>
					</div>
				</template>
			</div>
		</div>

		<?php /* ── Search button ── */ ?>
		<div class="sb-action">
			<button type="submit" class="sb-search-btn" title="<?php esc_attr_e( 'Search', 'hw' ); ?>">
				<i class="fas fa-search"></i>
			</button>
		</div>

	</div>
</form>
