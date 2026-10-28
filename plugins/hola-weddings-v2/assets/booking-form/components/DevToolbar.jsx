import { useState, useEffect } from '@wordpress/element';
import { useBooking }          from '../hooks/useBooking';
import { devStore }            from '../devStore';
import { submitRegistry }      from '../submitRegistry';
import { getRooms, getPackages } from '../api/booking';
import EdgeTrigger, { EDGE_ORDER } from '../../shared/EdgeTrigger';
import { CircleCheck, CircleX, Code, FlaskConical, LoaderCircle, Send, X, ZoomIn } from 'lucide-react';

// ── Date helpers ──────────────────────────────────────────────────────────────

function toYmd( date ) {
	return `${ date.getFullYear() }-${ String( date.getMonth() + 1 ).padStart( 2, '0' ) }-${ String( date.getDate() ).padStart( 2, '0' ) }`;
}

function getMockDates() {
	const startDate = window.hw?.start_date;

	let checkin;
	if ( startDate ) {
		const [ y, m, d ] = startDate.split( '-' ).map( Number );
		const start = new Date( y, m - 1, d );
		const today = new Date();
		today.setHours( 0, 0, 0, 0 );
		checkin = start >= today ? startDate : toYmd( today );
	} else {
		const d = new Date();
		d.setDate( d.getDate() + 7 );
		checkin = toYmd( d );
	}

	const [ cy, cm, cd ] = checkin.split( '-' ).map( Number );
	const checkout = toYmd( new Date( cy, cm - 1, cd + 7 ) );

	return { checkin, checkout };
}

// ── Mock state (no room — room is fetched separately) ─────────────────────────

function buildMockState( search ) {
	const isTeenEnabled = Boolean( window.hwBooking?.isTeenEnabled );

	return {
		searched: true,
		search,
		selectedRoom: null,
		guests: [
			{ personType: 'adult', title: 'mr',   name: 'John',  lastname: 'Smith', day: '15', month: '6',  year: '1985' },
			{ personType: 'adult', title: 'mrs',  name: 'Jane',  lastname: 'Smith', day: '22', month: '3',  year: '1988' },
			...( isTeenEnabled ? [
				{ personType: 'teen', title: 'miss', name: 'Emily', lastname: 'Smith', day: '5',  month: '9',  year: '2011' },
			] : [] ),
			{ personType: 'child', title: 'mstr', name: 'Liam',  lastname: 'Smith', day: '10', month: '1',  year: '2018' },
		],
		billing: {
			firstName: 'John',
			lastName:  'Smith',
			email:     'john.smith@example.com',
			phone:     '+1 555 000 1234',
			address:   '123 Main St',
			city:      'New York',
			state:     'NY',
			country:   'US',
			zip:       '10001',
		},
		insurance: null,
		payment: {
			depositType:  'full',
			cardType:     'visa',
			cardNumber:   '4111111111111111',
			cardMonth:    '12',
			cardYear:     '2028',
			cvv:          '123',
			cardOwner:    'John Smith',
			acceptCharge: true,
		},
		notes: '',
	};
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function sanitize( val, maxLen = 120 ) {
	if ( typeof val === 'string' ) {
		return val.length > maxLen ? `[string — ${ val.length } chars]` : val;
	}
	if ( Array.isArray( val ) ) return val.map( v => sanitize( v, maxLen ) );
	if ( val && typeof val === 'object' ) {
		return Object.fromEntries( Object.entries( val ).map( ( [ k, v ] ) => [ k, sanitize( v, maxLen ) ] ) );
	}
	return val;
}

const STEP_LABELS = [ 'Rooms', 'Guests', 'Contact', 'Insurance', 'Review' ];

// ── Inner component ───────────────────────────────────────────────────────────

function DevToolbarInner() {
	const { step, dispatch, selectedRoom } = useBooking();

	const [ open,          setOpen          ] = useState( () => localStorage.getItem( 'hw_dev_toolbar_open' ) !== 'false' );
	const [ activeTab,     setActiveTab     ] = useState( () => localStorage.getItem( 'hw_dev_toolbar_tab' ) ?? 'navigation' );
	const [ showInspector, setShowInspector ] = useState( false );
	const [ inspectorTab,  setInspectorTab  ] = useState( 'response' );
	const [ dev,           setDev           ] = useState( devStore.get );
	const [ filling,       setFilling       ] = useState( false );

	useEffect( () => devStore.subscribe( setDev ), [] );

	useEffect( () => {
		if ( dev.inspectOpen ) {
			setShowInspector( true );
			setInspectorTab( 'response' );
			devStore.set( { inspectOpen: false } );
		}
	}, [ dev.inspectOpen ] );

	async function fillWithRooms( targetStep ) {
		if ( filling ) return;

		const isTeenEnabled = Boolean( window.hwBooking?.isTeenEnabled );
		const { checkin, checkout } = getMockDates();

		const search = {
			checkin,
			checkout,
			adults:   2,
			teens:    isTeenEnabled ? 1 : 0,
			children: 1,
			infants:  0,
		};

		// If a room is already loaded and we're not going back to the rooms step, reuse it
		if ( targetStep > 0 && selectedRoom ) {
			dispatch( { type: 'FILL_MOCK', payload: { ...buildMockState( search ), step: targetStep, selectedRoom } } );
			return;
		}

		// Dispatch base state immediately so the UI responds
		dispatch( { type: 'FILL_MOCK', payload: { ...buildMockState( search ), step: targetStep } } );

		// Fetch a real room and select it
		setFilling( true );
		try {
			const fetchFn = window.hwBooking?.mode === 'package' ? getPackages : getRooms;
			const rooms   = await fetchFn( search );
			const room    = rooms.find( r => r.available ) ?? rooms[ 0 ] ?? null;
			if ( room ) {
				dispatch( { type: 'SET_ROOM', payload: room } );
			}
		} catch ( _ ) {
			// silently ignore — base state already applied
		} finally {
			setFilling( false );
		}
	}

	function jumpTo( n ) {
		dispatch( { type: 'SET_STEP', payload: n } );
	}

	function fillMock() {
		fillWithRooms( step );
	}

	const hasResult = dev.lastResponse !== null;

	function toggleOpen() {
		setOpen( v => {
			const next = ! v;
			localStorage.setItem( 'hw_dev_toolbar_open', next );
			return next;
		} );
	}

	return (
		<>
			{ /* ── Bookmark trigger ── */ }
			<EdgeTrigger order={ EDGE_ORDER.devToolbar }>
				<button
					className={ `hw-edge-trigger dev-toolbar__trigger${ open ? ' dev-toolbar__trigger--hidden' : '' }` }
					onClick={ toggleOpen }
					title="Dev Tools"
				>
					<Code size={ 20 } />
				</button>
			</EdgeTrigger>

			{ /* ── Panel — always mounted so closing can animate ── */ }
			<div className={ `dev-toolbar-wrap${ open ? ' dev-toolbar-wrap--open' : '' }` }>
				<div className="dev-toolbar">
					<button className="dev-toolbar__close" onClick={ toggleOpen } title="Close">
						<X size={ 16 } />
					</button>

					{ /* Tabs */ }
					<div className="dev-toolbar__tabs">
						{ [ [ 'navigation', 'Navigation' ], [ 'submission', 'Submission' ] ].map( ( [ id, label ] ) => (
							<button
								key={ id }
								className={ `dev-toolbar__tab${ activeTab === id ? ' dev-toolbar__tab--active' : '' }` }
								onClick={ () => { setActiveTab( id ); localStorage.setItem( 'hw_dev_toolbar_tab', id ); } }
							>
								{ label }
							</button>
						) ) }
					</div>

					<div className="dev-toolbar__body">

						{ /* ── Navigation tab ── */ }
						{ activeTab === 'navigation' && (
							<>
								<div className="dev-toolbar__label">Jump to step</div>
								<div className="dev-toolbar__steps">
									{ STEP_LABELS.map( ( label, i ) => (
										<button
											key={ i }
											className={ `dev-toolbar__step-btn${ step === i ? ' dev-toolbar__step-btn--active' : '' }` }
											onClick={ () => jumpTo( i ) }
										>
											{ i + 1 }. { label }
										</button>
									) ) }
								</div>
								<button
									className="dev-toolbar__mock-btn"
									onClick={ fillMock }
									disabled={ filling }
								>
									{ filling
										? <LoaderCircle size={ 14 } className="hw-spin" />
										: <FlaskConical size={ 14 } />
									}
									{ filling ? ' Loading…' : ' Fill mock data' }
								</button>
							</>
						) }

						{ /* ── Submission tab ── */ }
						{ activeTab === 'submission' && (
							<>
								<div className="dev-toolbar__setting">
									<span className="dev-toolbar__setting-label">Test Logged-out User</span>
									<label className="dev-toolbar__switch">
										<input
											type="checkbox"
											checked={ ! dev.simulateLoggedIn }
											onChange={ e => devStore.set( { simulateLoggedIn: ! e.target.checked } ) }
										/>
										<span className="dev-toolbar__switch-track" />
									</label>
								</div>

								<div className="dev-toolbar__setting">
									<span className="dev-toolbar__setting-label">Test mode</span>
									<label className="dev-toolbar__switch">
										<input
											type="checkbox"
											checked={ dev.testMode }
											onChange={ e => devStore.set( { testMode: e.target.checked } ) }
										/>
										<span className="dev-toolbar__switch-track" />
									</label>
								</div>

								{ dev.testMode && (
									<div className="dev-toolbar__setting">
										<span className="dev-toolbar__setting-label">Test response</span>
										<select
											className="dev-toolbar__select"
											value={ dev.mockOutcome }
											onChange={ e => devStore.set( { mockOutcome: e.target.value } ) }
										>
											<option value="success">✓ Success</option>
											<option value="error">✗ Error</option>
										</select>
									</div>
								) }

								<div className="dev-toolbar__setting">
									<span className="dev-toolbar__setting-label">Skip frontend validation</span>
									<label className="dev-toolbar__switch">
										<input
											type="checkbox"
											checked={ dev.skipValidation }
											onChange={ e => devStore.set( { skipValidation: e.target.checked } ) }
										/>
										<span className="dev-toolbar__switch-track" />
									</label>
								</div>

								<div className="dev-toolbar__setting">
									<span className="dev-toolbar__setting-label">Inspect submit request</span>
									<label className="dev-toolbar__switch">
										<input
											type="checkbox"
											checked={ dev.inspectMode }
											onChange={ e => devStore.set( { inspectMode: e.target.checked } ) }
										/>
										<span className="dev-toolbar__switch-track" />
									</label>
								</div>

								{ hasResult && (
									<div className="dev-toolbar__setting">
										<span className="dev-toolbar__setting-label">Last result</span>
										<button
											className="dev-toolbar__inspect-btn"
											onClick={ () => setShowInspector( true ) }
										>
											<ZoomIn size={ 14 } /> Inspect
										</button>
									</div>
								) }

								<button
									className="dev-toolbar__mock-btn"
									onClick={ () => submitRegistry.submit?.() }
								>
									<Send size={ 14 } /> Submit form
								</button>
							</>
						) }

					</div>
				</div>
			</div>

			{ /* ── JSON Inspector overlay ── */ }
			{ showInspector && hasResult && (
				<div className="dev-inspector-backdrop" onClick={ () => setShowInspector( false ) }>
					<div className="dev-inspector" onClick={ e => e.stopPropagation() }>
						<div className="dev-inspector__header">
							<span><Code size={ 14 } /> Request Inspector</span>
							<button className="dev-inspector__close" onClick={ () => setShowInspector( false ) }>
								&times;
							</button>
						</div>
						<div className="dev-inspector__status">
							{ dev.lastStatus !== null && dev.lastStatus < 400
								? <span className="dev-inspector__badge dev-inspector__badge--success"><CircleCheck size={ 14 } /> Status: { dev.lastStatus }</span>
								: <span className="dev-inspector__badge dev-inspector__badge--error"><CircleX size={ 14 } /> Status: { dev.lastStatus ?? '—' }</span>
							}
						</div>
						<div className="dev-inspector__tabs">
							{ [ [ 'response', 'Response' ], [ 'payload', 'Payload' ] ].map( ( [ id, label ] ) => (
								<button
									key={ id }
									className={ `dev-inspector__tab${ inspectorTab === id ? ' dev-inspector__tab--active' : '' }` }
									onClick={ () => setInspectorTab( id ) }
								>{ label }</button>
							) ) }
						</div>
						<pre className="dev-inspector__pre">{ JSON.stringify(
							inspectorTab === 'response' ? dev.lastResponse : sanitize( dev.lastPayload ),
							null, 2
						) }</pre>
					</div>
				</div>
			) }
		</>
	);
}

export default function DevToolbar() {
	return window.hwBooking?.debug ? <DevToolbarInner /> : null;
}
