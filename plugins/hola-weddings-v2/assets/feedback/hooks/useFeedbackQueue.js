import { useReducer, useCallback, useRef } from '@wordpress/element';
import { submitFeedbackReport }            from '../api/feedback';
import { collectClientContext }            from '../components/collectClientContext';

let nextId = 1;

function reducer( toasts, action ) {
	switch ( action.type ) {
		case 'ADD':    return [ ...toasts, action.toast ];
		case 'UPDATE': return toasts.map( ( t ) => t.id === action.id ? { ...t, ...action.patch } : t );
		case 'REMOVE': return toasts.filter( ( t ) => t.id !== action.id );
		default:       return toasts;
	}
}

/**
 * Async submission queue with toast state.
 *
 * Each call to enqueue() fires immediately (concurrent) — submissions never
 * block each other. Toast state flows: processing → success | error.
 * Successful toasts auto-dismiss after 8 s; error toasts stay until dismissed.
 */
export function useFeedbackQueue( { nonce, appContext = null } = {} ) {
	const [ toasts, dispatch ] = useReducer( reducer, [] );
	const nonceRef             = useRef( nonce );
	nonceRef.current           = nonce;

	const dismiss = useCallback( ( id ) => dispatch( { type: 'REMOVE', id } ), [] );

	const scheduleAutoDismiss = useCallback( ( id, delay = 8000 ) => {
		setTimeout( () => dispatch( { type: 'REMOVE', id } ), delay );
	}, [] );

	const enqueue = useCallback( async ( { formValues, screenshotBlobs = [] } ) => {
		const id = nextId++;

		dispatch( { type: 'ADD', toast: {
			id,
			title:   formValues.title,
			status:  'processing',
			jiraKey: null,
			jiraUrl: null,
			error:   null,
		} } );

		const clientContext = collectClientContext();
		const payload = {
			...clientContext,
			title:       formValues.title,
			description: formValues.description ?? '',
			priority:    formValues.priority    ?? 'Medium',
			type:        formValues.type        ?? 'Bug',
			reporter: {
				name:      formValues.reporterName      ?? '',
				email:     formValues.reporterEmail     ?? '',
				accountId: formValues.reporterAccountId ?? '',
			},
			app_context: {
				environment: appContext?.environment ?? '',
				version:     appContext?.version     ?? '',
			},
		};

		try {
			const result = await submitFeedbackReport( {
				payload,
				screenshotBlobs,
				nonce: nonceRef.current,
			} );

			dispatch( { type: 'UPDATE', id, patch: {
				status:  'success',
				jiraKey: result.jira_key ?? null,
				jiraUrl: result.jira_url ?? null,
			} } );

			scheduleAutoDismiss( id );
		} catch ( err ) {
			dispatch( { type: 'UPDATE', id, patch: {
				status: 'error',
				error:  err.message ?? 'Submission failed.',
			} } );
		}
	}, [ appContext, scheduleAutoDismiss ] );

	/**
	 * Fire a mock toast that simulates the processing → success/error lifecycle.
	 * For development testing only.
	 *
	 * @param {string}  title
	 * @param {object}  opts
	 * @param {number}  [opts.delay=2000]  ms until resolution
	 * @param {boolean} [opts.fail=false]  resolve as error
	 */
	const enqueueMock = useCallback( ( title, { delay = 2000, fail = false } = {} ) => {
		const id = nextId++;

		dispatch( { type: 'ADD', toast: {
			id,
			title,
			status:  'processing',
			jiraKey: null,
			jiraUrl: null,
			error:   null,
		} } );

		setTimeout( () => {
			if ( fail ) {
				dispatch( { type: 'UPDATE', id, patch: {
					status: 'error',
					error:  'Mock error: Could not connect to Jira.',
				} } );
			} else {
				const key = 'WEB-' + Math.floor( Math.random() * 900 + 100 );
				dispatch( { type: 'UPDATE', id, patch: {
					status:  'success',
					jiraKey: key,
					jiraUrl: '#',
				} } );
				scheduleAutoDismiss( id );
			}
		}, delay );
	}, [ scheduleAutoDismiss ] );

	/**
	 * Generic toast-backed async operation.
	 * Shows a processing toast immediately, then updates it to success/error.
	 * Returns a Promise that resolves (never rejects) when the operation finishes.
	 *
	 * @param {string}   title
	 * @param {Function} asyncFn   Must return a Promise. Throw to signal failure.
	 * @param {object}  [opts]
	 * @param {object}  [opts.statusText]  Override { processing, success, error } labels.
	 */
	const push = useCallback( ( title, asyncFn, opts = {} ) => {
		const id = nextId++;

		dispatch( { type: 'ADD', toast: {
			id,
			title,
			status:     'processing',
			jiraKey:    null,
			jiraUrl:    null,
			error:      null,
			statusText: opts.statusText ?? null,
		} } );

		return asyncFn()
			.then( () => {
				dispatch( { type: 'UPDATE', id, patch: { status: 'success' } } );
				scheduleAutoDismiss( id );
			} )
			.catch( ( err ) => {
				dispatch( { type: 'UPDATE', id, patch: {
					status: 'error',
					error:  err.message ?? 'Operation failed.',
				} } );
			} );
	}, [ scheduleAutoDismiss ] );

	return { toasts, enqueue, enqueueMock, push, dismiss };
}
