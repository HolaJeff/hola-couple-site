import { useState, useEffect, useRef, useCallback } from '@wordpress/element';
import ScreenshotPreview                             from './ScreenshotPreview';
import { MAX_SCREENSHOTS }                           from '../hooks/useFeedbackReporter';

const DEFAULT_CATEGORIES = [ 'Bug', 'UI Issue', 'Content Issue', 'Feature Request' ];
const DEFAULT_PRIORITIES  = [ 'Highest', 'High', 'Medium', 'Low', 'Lowest' ];
const REPORTER_KEY        = 'hwFeedback_reporter';

/**
 * Full-screen modal for collecting a feedback report.
 * Submission is handled externally — onSubmit(formValues) is called synchronously
 * and the modal closes immediately; the queue processes it in the background.
 *
 * @param {object}   props
 * @param {object}   props.reporter         useFeedbackReporter() return value (screenshot only).
 * @param {string[]} [props.categories]
 * @param {string[]} [props.priorities]
 * @param {boolean}  [props.enableScreenshot]
 * @param {boolean}  [props.requireReporter]
 * @param {Function} props.onSubmit         Called with formValues on valid submit.
 * @param {Function} props.onClose
 */
export default function FeedbackModal( {
	reporter,
	categories       = DEFAULT_CATEGORIES,
	priorities       = DEFAULT_PRIORITIES,
	defaultPriority,
	reporters        = [],
	enableScreenshot = true,
	requireReporter  = false,
	hidden           = false,
	onStartPicking,
	onClose,
	onSubmit,
} ) {
	const {
		screenshots,
		isCapturing,
		clearScreenshot,
	} = reporter;

	const firstFieldRef = useRef( null );

	const initialPriority = ( defaultPriority && priorities.includes( defaultPriority ) )
		? defaultPriority
		: ( priorities[ Math.floor( priorities.length / 2 ) ] ?? 'Medium' );

	const [ localReporters,    setLocalReporters    ] = useState( reporters );
	const [ refreshingReporters, setRefreshingReporters ] = useState( false );

	const handleRefreshReporters = useCallback( async () => {
		const endpoint = window.hwFeedback?.reportersEndpoint;
		const nonce    = window.hwFeedback?.nonce;
		if ( ! endpoint ) return;

		setRefreshingReporters( true );
		try {
			const res  = await fetch( endpoint, { headers: { 'X-WP-Nonce': nonce ?? '' } } );
			const data = await res.json().catch( () => [] );
			if ( res.ok ) setLocalReporters( Array.isArray( data ) ? data : [] );
		} finally {
			setRefreshingReporters( false );
		}
	}, [] );

	const [ form, setForm ] = useState( () => {
		let reporterAccountId = '';
		let reporterName      = '';
		if ( reporters.length > 0 ) {
			try {
				const saved = JSON.parse( localStorage.getItem( REPORTER_KEY ) ?? 'null' );
				if ( saved?.accountId && reporters.some( ( r ) => r.accountId === saved.accountId ) ) {
					reporterAccountId = saved.accountId;
					reporterName      = saved.displayName ?? '';
				}
			} catch {}
		}
		return {
			title:             '',
			description:       '',
			priority:          initialPriority,
			type:              categories[ 0 ] ?? 'Bug',
			reporterName,
			reporterEmail:     '',
			reporterAccountId,
		};
	} );

	const [ fieldErrors, setFieldErrors ] = useState( {} );

	// Block body scroll while modal is visible; restore it during element picking (hidden=true).
	useEffect( () => {
		document.body.style.overflow = hidden ? '' : 'hidden';
		return () => { document.body.style.overflow = ''; };
	}, [ hidden ] );

	// Trap focus + Escape key.
	useEffect( () => {
		firstFieldRef.current?.focus();

		const onKey = ( e ) => {
			if ( e.key === 'Escape' ) onClose();
		};
		document.addEventListener( 'keydown', onKey );
		return () => document.removeEventListener( 'keydown', onKey );
	}, [ onClose ] );

	// ── Handlers ──────────────────────────────────────────────────────────────

	const set = ( key ) => ( e ) =>
		setForm( ( prev ) => ( { ...prev, [ key ]: e.target.value } ) );

	const validate = () => {
		const errs = {};
		if ( ! form.title.trim() ) errs.title = 'Title is required.';
		if ( localReporters.length > 0 ) {
			if ( ! form.reporterAccountId ) errs.reporterAccountId = 'Please select a reporter.';
		} else if ( requireReporter ) {
			if ( ! form.reporterName.trim()  ) errs.reporterName  = 'Name is required.';
			if ( ! form.reporterEmail.trim() ) errs.reporterEmail = 'Email is required.';
		}
		setFieldErrors( errs );
		return Object.keys( errs ).length === 0;
	};

	const handleSubmit = ( e ) => {
		e.preventDefault();
		if ( ! validate() ) return;
		onSubmit( form );
	};

	// ── Render ────────────────────────────────────────────────────────────────

	return (
		<div
			className="fb-modal-backdrop"
			style={ hidden ? { display: 'none' } : undefined }
			onClick={ ( e ) => {
				if ( e.target === e.currentTarget ) onClose();
			} }
			role="dialog"
			aria-modal="true"
			aria-label="Report an issue"
		>
			<div className="fb-modal">

				{ /* Header */ }
				<div className="fb-modal__header">
					<div className="fb-modal__title">
						<i className="fas fa-bug" /> Report an Issue
					</div>
					<button
						type="button"
						className="fb-modal__close"
						onClick={ onClose }
						aria-label="Close"
					>
						<i className="fas fa-times" />
					</button>
				</div>

				{ /* Form */ }
				<form onSubmit={ handleSubmit } noValidate>
					<div className="fb-modal__body">

						{ /* Capturing overlay — covers both columns */ }
						{ isCapturing && (
							<div className="fb-modal__overlay">
								<i className="fas fa-spinner fa-spin" />
								<span>Capturing screenshot…</span>
							</div>
						) }

						{ /* Main column */ }
						<div className="fb-modal__main">

							{ /* Title */ }
							<div className={ `fb-form-group${ fieldErrors.title ? ' fb-form-group--error' : '' }` }>
								<label className="fb-label" htmlFor="fb-title">
									Title <span className="fb-required">*</span>
								</label>
								<input
									ref={ firstFieldRef }
									id="fb-title"
									type="text"
									className="form-control"
									value={ form.title }
									onChange={ set( 'title' ) }
									maxLength={ 255 }
									placeholder="Brief summary of the issue"
									required
								/>
								{ fieldErrors.title && (
									<span className="fb-field-error">{ fieldErrors.title }</span>
								) }
							</div>

							{ /* Description */ }
							<div className="fb-form-group">
								<label className="fb-label" htmlFor="fb-description">Description</label>
								<textarea
									id="fb-description"
									className="form-control"
									value={ form.description }
									onChange={ set( 'description' ) }
									rows={ 5 }
									placeholder="What happened? Steps to reproduce, expected vs actual behaviour…"
								/>
							</div>

							{ /* Screenshot */ }
							{ enableScreenshot && (
								<div className="fb-form-group">
									{ screenshots.length > 0 && (
										<div className="fb-screenshot-preview">
											<div className="fb-screenshot-preview__header">
												<span className="fb-screenshot-preview__label">
													<i className="fas fa-images" /> Screenshots
												</span>
												<span className="fb-screenshot-preview__count">
													{ screenshots.length } / { MAX_SCREENSHOTS }
												</span>
											</div>
											<div className="fb-screenshot-preview__body">
												{ screenshots.map( ( s, i ) => (
													<ScreenshotPreview
														key={ i }
														url={ s.url }
														onDiscard={ () => clearScreenshot( i ) }
													/>
												) ) }
											</div>
										</div>
									) }
									{ screenshots.length < MAX_SCREENSHOTS && (
										<button
											type="button"
											className={ `btn btn-sm btn-outline-secondary${ screenshots.length > 0 ? ' mt-2' : '' }` }
											onClick={ onStartPicking }
											disabled={ isCapturing }
										>
											{ isCapturing
												? <><i className="fas fa-spinner fa-spin" /> Capturing…</>
												: <><i className="fas fa-crosshairs" /> { screenshots.length > 0 ? 'Add another screenshot' : 'Pick element to capture' }</>
											}
										</button>
									) }
								</div>
							) }


						</div>

						{ /* Sidebar column */ }
						<div className="fb-modal__sidebar">

							{ /* Type */ }
							<div className="fb-form-group">
								<label className="fb-label" htmlFor="fb-type">Type</label>
								<select
									id="fb-type"
									className="form-control"
									value={ form.type }
									onChange={ set( 'type' ) }
								>
									{ categories.map( ( c ) => (
										<option key={ c } value={ c }>{ c }</option>
									) ) }
								</select>
							</div>

							{ /* Priority */ }
							<div className="fb-form-group">
								<label className="fb-label" htmlFor="fb-priority">Priority</label>
								<select
									id="fb-priority"
									className="form-control"
									value={ form.priority }
									onChange={ set( 'priority' ) }
								>
									{ priorities.map( ( p ) => (
										<option key={ p } value={ p }>{ p }</option>
									) ) }
								</select>
							</div>

							{ /* Reporter */ }
							{ localReporters.length > 0 ? (
								<div className={ `fb-form-group${ fieldErrors.reporterAccountId ? ' fb-form-group--error' : '' }` }>
									<div className="fb-label-row">
										<label className="fb-label" htmlFor="fb-reporter">
											Reporter <span className="fb-required">*</span>
										</label>
										<button
											type="button"
											className="fb-label-refresh"
											onClick={ handleRefreshReporters }
											disabled={ refreshingReporters }
											title="Refresh reporter list"
											aria-label="Refresh reporter list"
										>
											<i className={ `fas fa-sync-alt${ refreshingReporters ? ' fa-spin' : '' }` } />
										</button>
									</div>
									<select
										id="fb-reporter"
										className="form-control"
										value={ form.reporterAccountId }
										onChange={ ( e ) => {
											const id      = e.target.value;
											const matched = localReporters.find( ( r ) => r.accountId === id );
											try {
												if ( matched ) {
													localStorage.setItem( REPORTER_KEY, JSON.stringify( {
														accountId:   matched.accountId,
														displayName: matched.displayName,
													} ) );
												} else {
													localStorage.removeItem( REPORTER_KEY );
												}
											} catch {}
											setForm( ( prev ) => ( {
												...prev,
												reporterAccountId: id,
												reporterName:      matched?.displayName ?? '',
											} ) );
										} }
									>
										<option value="">— Select reporter —</option>
										{ localReporters.map( ( r ) => (
											<option key={ r.accountId } value={ r.accountId }>
												{ r.displayName }
											</option>
										) ) }
									</select>
									{ fieldErrors.reporterAccountId && (
										<span className="fb-field-error">{ fieldErrors.reporterAccountId }</span>
									) }
								</div>
							) : (
								<>
									<div className={ `fb-form-group${ fieldErrors.reporterName ? ' fb-form-group--error' : '' }` }>
										<label className="fb-label" htmlFor="fb-reporter-name">
											Your name{ requireReporter && <span className="fb-required"> *</span> }
										</label>
										<input
											id="fb-reporter-name"
											type="text"
											className="form-control"
											value={ form.reporterName }
											onChange={ set( 'reporterName' ) }
											placeholder="Optional"
										/>
										{ fieldErrors.reporterName && (
											<span className="fb-field-error">{ fieldErrors.reporterName }</span>
										) }
									</div>

									<div className={ `fb-form-group${ fieldErrors.reporterEmail ? ' fb-form-group--error' : '' }` }>
										<label className="fb-label" htmlFor="fb-reporter-email">
											Your email{ requireReporter && <span className="fb-required"> *</span> }
										</label>
										<input
											id="fb-reporter-email"
											type="email"
											className="form-control"
											value={ form.reporterEmail }
											onChange={ set( 'reporterEmail' ) }
											placeholder="Optional"
										/>
										{ fieldErrors.reporterEmail && (
											<span className="fb-field-error">{ fieldErrors.reporterEmail }</span>
										) }
									</div>
								</>
							) }

							{ /* Submit */ }
							<div className="fb-modal__sidebar-submit">
								<button
									type="submit"
									className="btn btn-primary w-100"
									disabled={ isCapturing }
								>
									<i className="fas fa-paper-plane" /> Submit report
								</button>
							</div>

						</div>

					</div>

				</form>

			</div>
		</div>
	);
}
