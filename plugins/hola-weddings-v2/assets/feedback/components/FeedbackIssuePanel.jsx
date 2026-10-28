import { useState, useEffect, useCallback } from '@wordpress/element';
import ConfirmDialog                         from './ConfirmDialog';

const PRIORITY_DOT = {
	Highest: '#dc3545',
	High:    '#fd7e14',
	Medium:  '#ffc107',
	Low:     '#0d6efd',
	Lowest:  '#adb5bd',
};

function statusMod( name ) {
	const n = ( name ?? '' ).toLowerCase().replace( /\s+/g, '' );
	if ( [ 'done', 'closed', 'resolved' ].includes( n ) ) return 'done';
	if ( n === 'inprogress' )                               return 'inprogress';
	return '';
}

function timeAgo( iso ) {
	if ( ! iso ) return '';
	const mins = Math.floor( ( Date.now() - new Date( iso ).getTime() ) / 60000 );
	if ( mins < 1  ) return 'just now';
	if ( mins < 60 ) return `${ mins }m ago`;
	const hrs = Math.floor( mins / 60 );
	if ( hrs  < 24 ) return `${ hrs }h ago`;
	return `${ Math.floor( hrs / 24 ) }d ago`;
}

const DELETE_STATUS_TEXT = { processing: 'Deleting…', success: 'Deleted', error: 'Failed' };

export default function FeedbackIssuePanel( { nonce, onPushToast } ) {
	const [ issues,        setIssues        ] = useState( [] );
	const [ loading,       setLoading        ] = useState( true );
	const [ error,         setError          ] = useState( null );
	const [ pendingDelete, setPendingDelete  ] = useState( null ); // issue awaiting confirm
	const [ deletingKey,   setDeletingKey    ] = useState( null ); // key of in-progress delete

	const issuesEndpoint  = window.hwFeedback?.issuesEndpoint  ?? '';
	const deleteIssueBase = window.hwFeedback?.deleteIssueBase ?? '';

	const load = useCallback( async () => {
		setLoading( true );
		setError( null );
		try {
			const sep = issuesEndpoint.includes( '?' ) ? '&' : '?';
			const res  = await fetch( `${ issuesEndpoint }${ sep }page=1`, {
				headers: { 'X-WP-Nonce': nonce ?? '' },
			} );
			const data = await res.json().catch( () => ( {} ) );
			if ( ! res.ok ) throw new Error( data?.message ?? `Error ${ res.status }` );
			setIssues( data.issues ?? [] );
		} catch ( err ) {
			setError( err.message );
		} finally {
			setLoading( false );
		}
	}, [ issuesEndpoint, nonce ] );

	useEffect( () => { load(); }, [ load ] );

	const handleDeleteConfirm = useCallback( async () => {
		const issue = pendingDelete;
		if ( ! issue ) return;

		// Close dialog immediately, mark row as in-progress.
		setPendingDelete( null );
		setDeletingKey( issue.key );

		await onPushToast(
			issue.key,
			async () => {
				const res  = await fetch( `${ deleteIssueBase }/${ issue.key }`, {
					method:  'DELETE',
					headers: { 'X-WP-Nonce': nonce ?? '' },
				} );
				const data = await res.json().catch( () => ( {} ) );
				if ( ! res.ok ) throw new Error( data?.message ?? `Error ${ res.status }` );
				setIssues( ( prev ) => prev.filter( ( i ) => i.key !== issue.key ) );
			},
			{ statusText: DELETE_STATUS_TEXT },
		);

		setDeletingKey( null );
	}, [ pendingDelete, onPushToast, deleteIssueBase, nonce ] );

	const renderBody = () => {
		if ( loading ) {
			return (
				<div className="fb-issue-panel__state">
					<i className="fas fa-spinner fa-spin" /> Loading…
				</div>
			);
		}

		if ( error ) {
			return (
				<div className="fb-issue-panel__state fb-issue-panel__state--error">
					<i className="fas fa-exclamation-triangle" /> { error }
				</div>
			);
		}

		if ( ! issues.length ) {
			return (
				<div className="fb-issue-panel__state">
					No issues found for this site.
				</div>
			);
		}

		return issues.map( ( issue ) => {
			const isDeleting = deletingKey === issue.key;
			return (
				<div
					key={ issue.key }
					className={ `fb-issue-item${ isDeleting ? ' fb-issue-item--deleting' : '' }` }
				>
					<a
						href={ issue.url }
						target="_blank"
						rel="noopener noreferrer"
						className="fb-issue-item__link"
						tabIndex={ isDeleting ? -1 : undefined }
					>
						<div className="fb-issue-item__top">
							<span
								className="fb-issue-item__priority"
								style={ { background: PRIORITY_DOT[ issue.priority ] ?? '#adb5bd' } }
								title={ issue.priority }
							/>
							<span className="fb-issue-item__key">{ issue.key }</span>
							<span className="fb-issue-item__summary">{ issue.summary }</span>
						</div>
						<div className="fb-issue-item__meta">
							{ isDeleting ? (
								<span className="fb-issue-item__deleting-label">
									<i className="fas fa-spinner fa-spin" /> Deleting…
								</span>
							) : (
								<>
									<span className={ `fb-issue-item__status fb-issue-item__status--${ statusMod( issue.status ) }` }>
										{ issue.status }
									</span>
									<span className="fb-issue-item__time">{ timeAgo( issue.created ) }</span>
								</>
							) }
						</div>
					</a>
					<button
						type="button"
						className="fb-issue-item__delete"
						onClick={ ( e ) => { e.stopPropagation(); setPendingDelete( issue ); } }
						disabled={ isDeleting }
						title="Delete issue"
						aria-label={ `Delete ${ issue.key }` }
					>
						<i className="fas fa-trash-alt" />
					</button>
				</div>
			);
		} );
	};

	return (
		<>
			<div className="fb-issue-panel">
				<div className="fb-issue-panel__header">
					<i className="fas fa-list" />
					<span>Feedback Issues</span>
					<button
						type="button"
						className="fb-issue-panel__refresh"
						onClick={ load }
						disabled={ loading }
						title="Refresh"
						aria-label="Refresh list"
					>
						<i className={ `fas fa-sync-alt${ loading ? ' fa-spin' : '' }` } />
					</button>
				</div>

				<div className="fb-issue-panel__body">
					{ renderBody() }
				</div>
			</div>

			{ pendingDelete && (
				<ConfirmDialog
					title="Delete Issue"
					message={ `Delete ${ pendingDelete.key } — "${ pendingDelete.summary }"? This cannot be undone.` }
					confirmLabel="Delete"
					confirmClass="btn-danger"
					onConfirm={ handleDeleteConfirm }
					onCancel={ () => setPendingDelete( null ) }
				/>
			) }
		</>
	);
}
