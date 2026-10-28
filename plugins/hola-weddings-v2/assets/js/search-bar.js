/**
 * Booking search-bar shortcode controller (Alpine).
 *
 * Vanilla-JS port of the booking-form app's SearchBox component. Renders the
 * date-range picker + guest counters and, on submit, redirects to the
 * booking-form page with the search params in the query string. The React
 * booking app then bootstraps its search state from those params on load.
 *
 * Config is provided server-side via window.hwSearchBar:
 *   { bookingUrl, minNights, isTeenEnabled, isAdultsOnly }
 * Date bounds are read from the globally-localized window.hw (start_date/end_date).
 */

// ── Locale objects (mirror the booking-form SearchBox) ────────────────────────
var HW_SB_EN_LOCALE = {
	days:        [ 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ],
	daysShort:   [ 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ],
	daysMin:     [ 'Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa' ],
	months:      [ 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' ],
	monthsShort: [ 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ],
	today:       'Today',
	clear:       'Clear',
	dateFormat:  'MM/dd/yyyy',
	firstDay:    0,
};

var HW_SB_ES_LOCALE = {
	days:        [ 'Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado' ],
	daysShort:   [ 'Dom', 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab' ],
	daysMin:     [ 'Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa' ],
	months:      [ 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre' ],
	monthsShort: [ 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic' ],
	today:       'Hoy',
	clear:       'Limpiar',
	dateFormat:  'dd/MM/yyyy',
	firstDay:    1,
};

// ── Date helpers ──────────────────────────────────────────────────────────────
function hwSbYmdToLocalDate( ymd ) {
	var parts = ymd.split( '-' );
	return new Date( Number( parts[ 0 ] ), Number( parts[ 1 ] ) - 1, Number( parts[ 2 ] ) );
}

function hwSbAddDays( date, days ) {
	var d = new Date( date );
	d.setDate( d.getDate() + days );
	return d;
}

function hwSbToYmd( date ) {
	var y = date.getFullYear();
	var m = String( date.getMonth() + 1 ).padStart( 2, '0' );
	var d = String( date.getDate() ).padStart( 2, '0' );
	return y + '-' + m + '-' + d;
}

document.addEventListener( 'alpine:init', function () {
	Alpine.data( 'hwSearchBar', function () {

		var cfg           = window.hwSearchBar || {};
		var isAdultsOnly  = Boolean( cfg.isAdultsOnly );
		var isTeenEnabled = Boolean( cfg.isTeenEnabled );

		// Guest types — same rules as the SearchBox GUEST_TYPES array.
		var guestTypes = isAdultsOnly
			? [ { field: 'adults', label: 'Adults', desc: '18+ years', min: 1 } ]
			: [ { field: 'adults', label: 'Adults', desc: '18+ years', min: 1 } ]
				.concat( isTeenEnabled ? [ { field: 'teens', label: 'Teens', desc: '13–17 years', min: 0 } ] : [] )
				.concat( [
					{ field: 'children', label: 'Children', desc: '2–12 years', min: 0 },
					{ field: 'infants',  label: 'Infants',  desc: 'Under 2',    min: 0 },
				] );

		// Kept in the per-instance closure ( NOT in reactive state ) so Alpine does
		// not proxy the AirDatepicker instance — mirrors the React useRef() usage.
		var dp                = null;
		var onOutsidePointer  = null;

		return {
			// ── State ──
			draft: {
				checkin:  null,
				checkout: null,
				adults:   1,
				teens:    0,
				children: 0,
				infants:  0,
			},
			guestsOpen:         false,
			submitAttempted:    false,
			minNightsViolation: false,

			// Resolved per-instance in init() from the form's data-booking-url,
			// falling back to the globally-localized default.
			bookingUrl: '',
			minNights:  parseInt( cfg.minNights || 1, 10 ) || 1,
			guestTypes: guestTypes,

			// ── Derived validity (reactive helpers) ──
			checkinInvalid:  function () { return this.submitAttempted && ! this.draft.checkin; },
			checkoutInvalid: function () { return this.submitAttempted && ! this.draft.checkout; },
			guestsInvalid:   function () { return this.submitAttempted && ( this.draft.adults || 0 ) < 1; },

			minNightsMessage: function () {
				return 'A minimum of ' + this.minNights + ' nights is required.';
			},

			// ── Display helpers ──
			formatDate: function ( dateStr ) {
				if ( ! dateStr ) return null;
				var parts = dateStr.split( '-' );
				return new Date( parts[ 0 ], parts[ 1 ] - 1, parts[ 2 ] ).toLocaleDateString( 'en-US', {
					month: 'short', day: 'numeric', year: 'numeric',
				} );
			},

			guestSummary: function () {
				var s     = this.draft;
				var parts = [];
				if ( s.adults   > 0 ) parts.push( s.adults + ' Adult' + ( s.adults !== 1 ? 's' : '' ) );
				if ( s.teens    > 0 ) parts.push( s.teens + ' Teen' + ( s.teens !== 1 ? 's' : '' ) );
				if ( s.children > 0 ) parts.push( s.children + ' Child' + ( s.children !== 1 ? 'ren' : '' ) );
				if ( s.infants  > 0 ) parts.push( s.infants + ' Infant' + ( s.infants !== 1 ? 's' : '' ) );
				return parts.join( ', ' ) || 'Add guests';
			},

			adjust: function ( field, delta ) {
				var min    = field === 'adults' ? 1 : 0;
				var newVal = Math.max( min, ( this.draft[ field ] || 0 ) + delta );
				this.draft[ field ] = newVal;
			},

			// ── Datepicker ──
			openPicker: function () {
				if ( dp ) dp.show();
			},

			init: function () {
				var self = this;

				// Per-instance booking URL ( shortcode page_id attribute ) → global fallback.
				this.bookingUrl = ( this.$el && this.$el.dataset.bookingUrl ) || cfg.bookingUrl || '';

				if ( ! window.AirDatepicker || ! this.$refs.dateAnchor ) return;

				var hw       = window.hw || {};
				var minNight = this.minNights;
				var locale   = ( document.documentElement.lang || '' ).indexOf( 'es' ) === 0 ? HW_SB_ES_LOCALE : HW_SB_EN_LOCALE;

				var startDate = hw.start_date ? hwSbYmdToLocalDate( hw.start_date ) : new Date();
				var endDate   = hw.end_date   ? hwSbYmdToLocalDate( hw.end_date )   : null;
				// Latest valid check-in: endDate − minNight (so a minimum stay can fit before the end)
				var ciEndDate = endDate ? hwSbAddDays( endDate, -minNight ) : null;

				var options = {
					range:          true,
					selectedDates:  [],
					locale:         locale,
					minDate:        startDate,
					autoClose:      true,
					toggleSelected: false,
					offset:         4,

					onRenderCell: function ( data ) {
						if ( data.cellType !== 'day' ) return {};
						var selected = ( dp && dp.selectedDates ) || [];
						if ( selected.length === 1 ) {
							if ( data.date < selected[ 0 ] ) {
								return { disabled: true };
							}
							var minCheckout = hwSbAddDays( selected[ 0 ], minNight );
							if ( data.date > selected[ 0 ] && data.date < minCheckout ) {
								return { disabled: true, classes: '-dp-min-nights-' };
							}
						} else if ( ciEndDate && data.date > ciEndDate ) {
							return { disabled: true };
						}
						return {};
					},

					onSelect: function ( param ) {
						var dates = param.date;
						if ( ! dates || dates.length === 0 ) {
							self.draft.checkin  = null;
							self.draft.checkout = null;
						} else if ( dates.length === 1 ) {
							self.draft.checkin       = hwSbToYmd( dates[ 0 ] );
							self.draft.checkout      = null;
							self.minNightsViolation  = false;
							// Force a full cell re-render so the min-nights blocking takes effect.
							setTimeout( function () { if ( dp ) dp.update( {} ); }, 0 );
						} else {
							self.draft.checkin      = hwSbToYmd( dates[ 0 ] );
							self.draft.checkout     = hwSbToYmd( dates[ 1 ] );
							self.minNightsViolation = false;
						}
					},
				};

				if ( endDate ) {
					options.maxDate = endDate;
				}

				dp = new window.AirDatepicker( this.$refs.dateAnchor, options );

				// pointerdown covers desktop + iOS Safari
				onOutsidePointer = function ( e ) {
					if ( dp.$datepicker.classList.contains( '-active-' ) && ! dp.$datepicker.contains( e.target ) ) {
						dp.hide();
					}
				};
				document.addEventListener( 'pointerdown', onOutsidePointer );
			},

			destroy: function () {
				if ( dp ) dp.destroy();
				if ( onOutsidePointer ) document.removeEventListener( 'pointerdown', onOutsidePointer );
			},

			// ── Submit → redirect to the booking-form page ──
			submit: function () {
				this.guestsOpen = false;

				if ( ! this.draft.checkin || ! this.draft.checkout || ( this.draft.adults || 0 ) < 1 ) {
					this.submitAttempted = true;
					return;
				}

				if ( this.minNights > 1 ) {
					var nights = ( hwSbYmdToLocalDate( this.draft.checkout ) - hwSbYmdToLocalDate( this.draft.checkin ) ) / ( 1000 * 3600 * 24 );
					if ( nights < this.minNights ) {
						this.minNightsViolation = true;
						return;
					}
				}

				var p = new URLSearchParams();
				p.set( 'checkin',  this.draft.checkin );
				p.set( 'checkout', this.draft.checkout );
				p.set( 'adults',   this.draft.adults || 1 );
				[ 'teens', 'children', 'infants' ].forEach( function ( key ) {
					if ( ( this.draft[ key ] || 0 ) > 0 ) {
						p.set( key, this.draft[ key ] );
					}
				}.bind( this ) );

				var base = ( this.bookingUrl || cfg.bookingUrl || window.location.pathname );
				var sep  = base.indexOf( '?' ) === -1 ? '?' : '&';
				window.location.href = base + sep + p.toString();
			},
		};
	} );
} );
