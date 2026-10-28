import { createContext, useContext, useReducer } from '@wordpress/element';
import { sanitizeInsurance } from '../utils/insurance';

const BookingContext = createContext( null );

// ── Session persistence (survives page reload after login) ────────────────────

const BOOKING_SESSION_KEY = 'hw_booking_resume';

function readResumeState() {
	try {
		const raw = sessionStorage.getItem( BOOKING_SESSION_KEY );
		if ( ! raw ) return null;
		sessionStorage.removeItem( BOOKING_SESSION_KEY );
		return JSON.parse( raw );
	} catch {
		return null;
	}
}

export function saveBookingState( ctx ) {
	try {
		const { dispatch: _d, password: _p, confirmPassword: _cp, serverErrors: _se, ...state } = ctx;
		sessionStorage.setItem( BOOKING_SESSION_KEY, JSON.stringify( state ) );
	} catch {
		// ignore quota / security errors
	}
}

// ── Initial state ─────────────────────────────────────────────────────────────

// Read search params from URL on page load
const _p       = new URLSearchParams( window.location.search );
const _checkin  = _p.get( 'checkin' )  ?? '';
const _checkout = _p.get( 'checkout' ) ?? '';
const _adults   = Math.max( 1, parseInt( _p.get( 'adults'   ) ?? 1 ) );
const _teens    = Math.max( 0, parseInt( _p.get( 'teens'    ) ?? 0 ) );
const _children = Math.max( 0, parseInt( _p.get( 'children' ) ?? 0 ) );
const _infants  = Math.max( 0, parseInt( _p.get( 'infants'  ) ?? 0 ) );
const _hasSearch = Boolean( _checkin && _checkout );

const _resume = readResumeState();

const _prefillBilling = window.hwBooking?.customerBilling ?? {};

const _initialState = _resume ?? {
	step:          0,
	searchVersion: _hasSearch ? 1 : 0,
	searched:      _hasSearch,
	search: {
		checkin:  _checkin,
		checkout: _checkout,
		adults:   _adults,
		teens:    _teens,
		children: _children,
		infants:  _infants,
	},
	selectedRoom:    null,
	selectedPackage: null,
	guests:          [],
	billing:         { ..._prefillBilling },
	updateCustomer:  false,
	insurance:       null,
	payment:         { depositType: 'deposit', cardType: 'visa' },
	notes:           '',
	accountEmail:    '',
	password:        '',
	confirmPassword: '',
	showErrors:      false,
	serverErrors:    {},
};

// a resumed session may carry a plan the saved billing state does not allow
const initialState = {
	..._initialState,
	insurance: sanitizeInsurance( _initialState.insurance, _initialState.billing ),
};

function reducer( state, action ) {
	switch ( action.type ) {
		case 'SET_SEARCHED':
			return { ...state, searched: true };
		case 'RESET_BOOKING':
			return {
				...state,
				searched:        true,
				searchVersion:   state.searchVersion + 1,
				step:            0,
				selectedRoom:    null,
				selectedPackage: null,
				guests:          [],
				billing:         {},
				insurance:       null,
				payment:         { depositType: 'deposit', cardType: 'visa' },
				notes:           '',
				accountEmail:    '',
				password:        '',
				confirmPassword: '',
				showErrors:      false,
				serverErrors:    {},
			};
		case 'SET_STEP':
			return { ...state, step: action.payload, showErrors: false };
		case 'SET_ACCOUNT_EMAIL':
			return { ...state, accountEmail: action.payload };
		case 'SET_PASSWORD':
			return { ...state, password: action.payload };
		case 'SET_CONFIRM_PASSWORD':
			return { ...state, confirmPassword: action.payload };
		case 'SET_SHOW_ERRORS':
			return { ...state, showErrors: action.payload };
		case 'SET_SERVER_ERRORS':
			return { ...state, serverErrors: action.payload };
		case 'NEXT_STEP':
			return { ...state, step: state.step + 1 };
		case 'PREV_STEP':
			return { ...state, step: Math.max( 0, state.step - 1 ) };
		case 'SET_SEARCH':
			return { ...state, search: { ...state.search, ...action.payload } };
		case 'SET_ROOM':
			return { ...state, selectedRoom: action.payload };
		case 'SET_PACKAGE':
			return { ...state, selectedPackage: action.payload };
		case 'SET_GUESTS':
			return { ...state, guests: action.payload };
		case 'SET_BILLING': {
			const billing = { ...state.billing, ...action.payload };
			return { ...state, billing, insurance: sanitizeInsurance( state.insurance, billing ) };
		}
		case 'SET_UPDATE_CUSTOMER':
			return { ...state, updateCustomer: action.payload };
		case 'SET_INSURANCE':
			return { ...state, insurance: sanitizeInsurance( action.payload, state.billing ) };
		case 'SET_PAYMENT':
			return { ...state, payment: { ...state.payment, ...action.payload } };
		case 'SET_NOTES':
			return { ...state, notes: action.payload };
		case 'FILL_MOCK':
			return { ...state, ...action.payload };
		default:
			return state;
	}
}

export function BookingProvider( { children } ) {
	const [ state, dispatch ] = useReducer( reducer, initialState );

	return (
		<BookingContext.Provider value={ { ...state, dispatch } }>
			{ children }
		</BookingContext.Provider>
	);
}

export function useBooking() {
	const ctx = useContext( BookingContext );
	if ( ! ctx ) throw new Error( 'useBooking must be used inside BookingProvider' );
	return ctx;
}
