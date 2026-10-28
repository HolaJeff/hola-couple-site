import { useState, useCallback }    from '@wordpress/element';
import { useFeedbackReporter }      from '../hooks/useFeedbackReporter';
import { useFeedbackQueue }         from '../hooks/useFeedbackQueue';
import FeedbackSidebar              from './FeedbackSidebar';
import FeedbackIssuePanel           from './FeedbackIssuePanel';
import FeedbackModal                from './FeedbackModal';
import FeedbackSetupDialog          from './FeedbackSetupDialog';
import ElementPickerOverlay         from './ElementPickerOverlay';
import EdgeTrigger, { EDGE_ORDER }  from '../../shared/EdgeTrigger';
import FeedbackToastStack           from './FeedbackToastStack';

const DEFAULT_CATEGORIES = [ 'Bug', 'UI Issue', 'Content Issue', 'Feature Request' ];
const DEFAULT_PRIORITIES  = [ 'Highest', 'High', 'Medium', 'Low', 'Lowest' ];
const hwFeedback          = window.hwFeedback ?? {};
const isDebug             = window.hwFeedback?.debug === true;
const isConfigured        = hwFeedback.isConfigured !== false;

export default function FeedbackButton( {
	nonce            = hwFeedback.nonce,
	appContext        = null,
	categories        = hwFeedback.categories?.length ? hwFeedback.categories : DEFAULT_CATEGORIES,
	priorities        = hwFeedback.priorities?.length ? hwFeedback.priorities : DEFAULT_PRIORITIES,
	defaultPriority   = hwFeedback.defaultPriority,
	reporters         = hwFeedback.reporters ?? [],
	enableScreenshot  = true,
	requireReporter   = false,
} ) {
	const [ sidebarOpen,    setSidebarOpen    ] = useState( false );
	const [ modalOpen,      setModalOpen      ] = useState( false );
	const [ listOpen,       setListOpen       ] = useState( false );
	const [ isPicking,      setIsPicking      ] = useState( false );
	const [ isScreenshotting, setIsScreenshotting ] = useState( false );

	const reporter = useFeedbackReporter( { nonce, appContext } );
	const queue    = useFeedbackQueue( { nonce, appContext } );

	// ── Sidebar ────────────────────────────────────────────────────────────────

	const handleOpenSidebar = useCallback( () => setSidebarOpen( true ), [] );

	const handleFold = useCallback( () => {
		setSidebarOpen( false );
		setModalOpen( false );
		setListOpen( false );
		setIsPicking( false );
		reporter.clearScreenshot();
	}, [ reporter ] );

	// ── Add feedback ───────────────────────────────────────────────────────────

	const handleAddFeedback = useCallback( () => {
		setListOpen( false );
		setModalOpen( true );
	}, [] );

	const handleModalClose = useCallback( () => {
		setModalOpen( false );
		setIsPicking( false );
		reporter.clearScreenshot();
	}, [ reporter ] );

	const handleSubmit = useCallback( ( formValues ) => {
		queue.enqueue( { formValues, screenshotBlobs: reporter.screenshots.map( ( s ) => s.blob ) } );
		reporter.clearScreenshot();
		setModalOpen( false );
		setIsPicking( false );
	}, [ queue, reporter ] );

	// ── Issue list ─────────────────────────────────────────────────────────────

	const handleToggleList = useCallback( () => {
		setListOpen( ( prev ) => {
			if ( ! prev ) setModalOpen( false ); // close modal when opening list
			return ! prev;
		} );
	}, [] );

	// ── Element picker ─────────────────────────────────────────────────────────

	const handleStartPicking = useCallback( () => setIsPicking( true ), [] );
	const handlePickCancel   = useCallback( () => setIsPicking( false ), [] );

	const handlePickCapture  = useCallback( async ( element ) => {
		// Unmount the overlay; simultaneously flag that we're screenshotting so
		// the modal stays hidden until the capture finishes (React batches both).
		setIsPicking( false );
		setIsScreenshotting( true );
		if ( element ) {
			// Wait for the DOM to update (overlay removed, modal still hidden)
			// before html2canvas traverses the page.
			await new Promise( ( r ) => setTimeout( r, 60 ) );
			await reporter.captureElement( element );
		}
		setIsScreenshotting( false );
	}, [ reporter ] );

	// ── Dev mocks ──────────────────────────────────────────────────────────────

	const fireMocks = useCallback( () => {
		queue.enqueueMock( 'Navigation menu collapses on mobile',   { delay: 1500 } );
		queue.enqueueMock( 'Date picker overlaps the form fields',  { delay: 3000 } );
		queue.enqueueMock( 'Payment step returns 500 error',        { delay: 2200, fail: true } );
	}, [ queue ] );

	return (
		<>
			{ /* Trigger — hidden while sidebar is open */ }
			{ ! sidebarOpen && (
				<EdgeTrigger order={ EDGE_ORDER.feedback }>
					<button
						type="button"
						className="hw-edge-trigger fb-bubble"
						onClick={ handleOpenSidebar }
						aria-label="Open feedback tools"
						title="Feedback"
					>
						<i className="fas fa-bug" />
					</button>
				</EdgeTrigger>
			) }

			{ /* Dev-only mock trigger */ }
			{ isDebug && ! sidebarOpen && (
				<EdgeTrigger order={ EDGE_ORDER.feedbackMock }>
					<button
						type="button"
						className="hw-edge-trigger fb-bubble fb-bubble--mock"
						onClick={ fireMocks }
						title="Fire 3 mock feedback toasts"
					>
						<i className="fas fa-vial" />
					</button>
				</EdgeTrigger>
			) }

			{ /* Setup dialog when Jira is not configured */ }
			{ sidebarOpen && ! isConfigured && (
				<FeedbackSetupDialog onClose={ handleFold } />
			) }

			{ /* Sidebar strip */ }
			{ sidebarOpen && isConfigured && (
				<FeedbackSidebar
					listOpen={ listOpen }
					onAddFeedback={ handleAddFeedback }
					onToggleList={ handleToggleList }
					onFold={ handleFold }
				/>
			) }

			{ /* Issue list panel */ }
			{ sidebarOpen && isConfigured && listOpen && (
				<FeedbackIssuePanel nonce={ nonce } onPushToast={ queue.push } />
			) }

			{ /* Element picker overlay */ }
			{ isPicking && (
				<ElementPickerOverlay
					onCapture={ handlePickCapture }
					onCancel={ handlePickCancel }
				/>
			) }

			{ /* Feedback form modal */ }
			{ modalOpen && (
				<FeedbackModal
					reporter={ reporter }
					categories={ categories }
					priorities={ priorities }
					defaultPriority={ defaultPriority }
					reporters={ reporters }
					enableScreenshot={ enableScreenshot }
					requireReporter={ requireReporter }
					hidden={ isPicking || isScreenshotting }
					onStartPicking={ handleStartPicking }
					onClose={ handleModalClose }
					onSubmit={ handleSubmit }
				/>
			) }

			{ /* Toast notifications */ }
			<FeedbackToastStack toasts={ queue.toasts } onDismiss={ queue.dismiss } />
		</>
	);
}
