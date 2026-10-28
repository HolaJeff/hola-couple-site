const LS_KEY = 'hw_dev_settings';

function _loadPersisted() {
	try {
		const raw = localStorage.getItem( LS_KEY );
		return raw ? JSON.parse( raw ) : {};
	} catch {
		return {};
	}
}

function _persist() {
	try {
		localStorage.setItem( LS_KEY, JSON.stringify( {
			simulateLoggedIn: _state.simulateLoggedIn,
			testMode:         _state.testMode,
			skipValidation:   _state.skipValidation,
			inspectMode:      _state.inspectMode,
		} ) );
	} catch {
		// ignore quota / security errors
	}
}

const _persisted = _loadPersisted();

const _state = {
	testMode:         _persisted.testMode         ?? false,
	skipValidation:   _persisted.skipValidation   ?? false,
	inspectMode:      _persisted.inspectMode      ?? false,
	mockOutcome:      'success',
	lastPayload:      null,
	lastResponse:     null,
	lastStatus:       null,
	inspectOpen:      false,
	simulateLoggedIn: _persisted.simulateLoggedIn ?? Boolean( window.hwBooking?.isLoggedIn ),
};

const _listeners = new Set();

function _notify() {
	const snap = { ..._state };
	_listeners.forEach( fn => fn( snap ) );
}

export const devStore = {
	get()           { return { ..._state }; },
	set( patch )    { Object.assign( _state, patch ); _persist(); _notify(); },
	subscribe( fn ) { _listeners.add( fn ); return () => _listeners.delete( fn ); },
};

/**
 * Returns the effective logged-in state.
 * In debug mode the dev toolbar toggle overrides the real value.
 */
export function isLoggedIn() {
	if ( window.hwBooking?.debug ) return _state.simulateLoggedIn;
	return Boolean( window.hwBooking?.isLoggedIn );
}
