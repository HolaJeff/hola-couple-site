import { __, sprintf } from '@wordpress/i18n';
import { useState, useRef, useEffect, createInterpolateElement } from '@wordpress/element';
import SignaturePad                     from 'signature_pad';
import { useBooking }                   from '../../../hooks/useBooking';
import { StepCard }                     from '../../StepCard';
import { ArrowDown, Check, FileCheck, Pen, PenTool, RotateCcw, Signature } from 'lucide-react';

const GLOBAL_TERMS = window.hwBooking?.globalTerms ?? '';

const FONTS = [
	{ value: 'Dancing Script', label: __( 'Cursive', 'hola-weddings' ) },
	{ value: 'Pinyon Script',  label: __( 'Formal', 'hola-weddings' ) },
];

export default function DisclaimerCard( { errors = {}, showAuth = false, payableAmount = '' } ) {
	const { payment, dispatch } = useBooking();

	// ── T&C modal ─────────────────────────────────────────────────────────────
	const [ tcOpen,     setTcOpen     ] = useState( false );
	const [ tcScrolled, setTcScrolled ] = useState( false );
	const tcBodyRef = useRef( null );

	// ── Signature modal ───────────────────────────────────────────────────────
	const [ sigOpen, setSigOpen ] = useState( false );
	const [ sigFont, setSigFont ] = useState( 'Dancing Script' );
	const [ sigText, setSigText ] = useState( '' );
	const canvasRef = useRef( null );
	const padRef    = useRef( null );

	const termsAccepted = Boolean( payment.termsAccepted );
	const signature     = payment.signature ?? '';

	function setDirect( field, value ) {
		dispatch( { type: 'SET_PAYMENT', payload: { [ field ]: value } } );
	}

	// ── T&C helpers ───────────────────────────────────────────────────────────

	function openTcModal() {
		setTcScrolled( false );
		setTcOpen( true );
	}

	// Enable accept immediately if content doesn't overflow
	useEffect( () => {
		if ( tcOpen && tcBodyRef.current ) {
			const el = tcBodyRef.current;
			if ( el.scrollHeight <= el.clientHeight ) setTcScrolled( true );
		}
	}, [ tcOpen ] );

	function handleTcScroll( e ) {
		if ( tcScrolled ) return;
		const el = e.currentTarget;
		if ( Math.floor( el.scrollHeight - el.scrollTop ) - 50 <= Math.floor( el.clientHeight ) ) {
			setTcScrolled( true );
		}
	}

	function scrollTcToBottom() {
		if ( tcBodyRef.current ) tcBodyRef.current.scrollTop = tcBodyRef.current.scrollHeight;
	}

	function acceptTerms() {
		setDirect( 'termsAccepted', true );
		setTcOpen( false );
	}

	function handleTcCheckbox( e ) {
		if ( e.target.checked && ! termsAccepted ) {
			openTcModal();
		} else if ( ! e.target.checked ) {
			setDirect( 'termsAccepted', false );
		}
	}

	// ── Signature helpers ─────────────────────────────────────────────────────

	// Init / destroy SignaturePad when modal opens/closes
	useEffect( () => {
		if ( ! sigOpen || ! canvasRef.current ) return;
		const canvas = canvasRef.current;
		const ratio  = Math.max( window.devicePixelRatio || 1, 1 );
		canvas.width  = canvas.offsetWidth  * ratio;
		canvas.height = canvas.offsetHeight * ratio;
		canvas.getContext( '2d' ).scale( ratio, ratio );
		padRef.current = new SignaturePad( canvas, { backgroundColor: 'rgb(255,255,255)' } );
		return () => { padRef.current?.off(); padRef.current = null; };
	}, [ sigOpen ] );

	// Redraw typed text when sigText or sigFont changes
	useEffect( () => {
		if ( ! sigOpen || ! padRef.current || ! sigText ) return;
		const canvas = canvasRef.current;
		const ctx    = canvas.getContext( '2d' );
		padRef.current.clear();
		ctx.font      = `48px "${ sigFont }", cursive`;
		ctx.fillStyle = 'black';
		ctx.textAlign = 'center';
		ctx.fillText( sigText, canvas.offsetWidth / 2, canvas.offsetHeight / 2 + 15 );
	}, [ sigText, sigFont, sigOpen ] );

	function clearSignature() {
		padRef.current?.clear();
		setSigText( '' );
	}

	function isCanvasBlank() {
		if ( ! canvasRef.current ) return true;
		const canvas = canvasRef.current;
		const blank  = document.createElement( 'canvas' );
		blank.width  = canvas.width;
		blank.height = canvas.height;
		return canvas.toDataURL() === blank.toDataURL();
	}

	function acceptSignature() {
		if ( isCanvasBlank() ) {
			// eslint-disable-next-line no-alert
			alert( __( 'Signature is empty.', 'hola-weddings' ) );
			return;
		}
		setDirect( 'signature', canvasRef.current.toDataURL( 'image/png' ) );
		setSigOpen( false );
		setSigText( '' );
	}

	// ── Render ────────────────────────────────────────────────────────────────
	return (
		<>
		<StepCard variant="brand-secondary" className="mb-3">
			<StepCard.Header
				icon={ <FileCheck size={ 16 } /> }
				title={ __( 'Final Authorization', 'hola-weddings' ) }
				subtitle={ __( 'Please review and sign to complete your booking', 'hola-weddings' ) }
			/>

			{ /* Authorization checkbox — only when card payment is available */ }
			{ showAuth && (
				<>
				<div className='mb-3'>
					<p className="step-card__disclaimer">
						{ __( 'By submitting, you certify that you are the cardholder and are authorizing the travel agency or its chosen Tour Operator/Supplier to charge the listed amount to the credit card. You certify that you have verified that all information contained in the confirmation you received is accurate. You also certify you have read the Terms & Conditions and the appropriate Travel Protection Plan details. Cancellation penalties may apply. Insurance is not refundable.', 'hola-weddings' ) }
						<br /><br />
						{ __( 'This charge will be manually applied by the agency to your reservation. If there are any issues, an agent will get back to you. Please note that you may not see a charge from the travel agency on your credit card statement; the charge will come from our supplier directly. Payment may take 3–5 business days to fully process and be reflected on your statement.', 'hola-weddings' ) }
					</p>
					<label
						htmlFor="pay-accept"
						className={ `border pay-auth${ payment.acceptCharge ? ' pay-auth--checked' : '' }${ errors.acceptCharge ? ' pay-auth--error' : '' }` }
					>
						<input
							type="checkbox"
							id="pay-accept"
							className="sr-only"
							checked={ Boolean( payment.acceptCharge ) }
							onChange={ ( e ) => setDirect( 'acceptCharge', e.target.checked ) }
						/>
						<span className={ `pay-auth__box${ ! payment.acceptCharge ? ' pay-auth__box--pulse' : '' }` }>
							{ payment.acceptCharge && <Check size={ 14 } className="pay-auth__check" /> }
						</span>
						<span className="pay-auth__text">
							{ createInterpolateElement(
								sprintf(
									/* translators: %s: formatted amount to be charged, e.g. $500.00 */
									__( 'I authorize Hola Weddings to charge my card for the amount of <strong>%s</strong>.', 'hola-weddings' ),
									payableAmount
								),
								{ strong: <strong /> }
							) }
						</span>
					</label>
					{ errors.acceptCharge && (
						<div className="pay-auth__error mb-3">{ errors.acceptCharge }</div>
					) }
				</div>
				</>
			) }

			{ /* Body: disclaimer + T&C */ }
			<p className="step-card__disclaimer">
				{ __( 'By submitting, you certify that you have verified that all information contained in the confirmation you received is accurate. You also certify you have read the Terms & Conditions and the appropriate Travel Protection Plan details. Cancellation penalties may apply. Insurance is not refundable.', 'hola-weddings' ) }
			</p>

			<label
				htmlFor="tcCheck"
				className={ `mb-3 border pay-auth${ termsAccepted ? ' pay-auth--checked' : '' }${ errors.termsAccepted ? ' pay-auth--error' : '' }` }
			>
				<input
					type="checkbox"
					id="tcCheck"
					className="sr-only"
					checked={ termsAccepted }
					onChange={ handleTcCheckbox }
				/>
				<span className={ `pay-auth__box${ ! termsAccepted ? ' pay-auth__box--pulse' : '' }` }>
					{ termsAccepted && <Check size={ 14 } className="pay-auth__check" /> }
				</span>
				<span className="pay-auth__text">
					{ createInterpolateElement(
						__( 'I accept the <cancellation>Cancellation Policy</cancellation>, <payment>Payment Policy</payment> and <terms>Terms & Conditions</terms>', 'hola-weddings' ),
						{
							cancellation: <a className='step-card__tc-link' href='#cancellation-policy' />,
							payment:      <a className='step-card__tc-link' href='#payment-policy' />,
							terms:        <button type="button" className="btn btn-link d-inline p-0 align-baseline step-card__tc-link" style={ { textDecoration: 'underline' } } onClick={ openTcModal } />,
						}
					) }
				</span>
			</label>
			{ errors.termsAccepted && (
				<div className="pay-auth__error">{ errors.termsAccepted }</div>
			) }

			{ /* Signature */ }
			<div className={ `step-card__signature${ errors.signature ? ' step-card__signature--error' : '' }` }>
				<div className="step-card__sig-header">
					<PenTool size={ 16 } />
					{ __( 'Signature', 'hola-weddings' ) }
				</div>
				<p className="step-card__sig-hint">
					{ __( 'Please add your electronic signature in order to complete your reservation.', 'hola-weddings' ) }
				</p>
				{ signature && (
					<img src={ signature } alt={ __( 'Your signature', 'hola-weddings' ) } className="step-card__sig-preview" />
				) }
				<button
					type="button"
					className="step-card__sig-btn btn"
					onClick={ () => setSigOpen( true ) }
				>
					{ signature
						? <><RotateCcw size={ 16 } className="mr-1" />{ __( 'Re-sign', 'hola-weddings' ) }</>
						: <><Pen size={ 16 } className="mr-1" />{ __( 'Sign now!', 'hola-weddings' ) }</> }
				</button>
				{ errors.signature && (
					<div className="step-card__sig-error">{ errors.signature }</div>
				) }
			</div>

		</StepCard>

		{ /* ── T&C Modal ─────────────────────────────────────────────────── */ }
		{ tcOpen && (
				<div className="hw-modal-backdrop" onClick={ () => setTcOpen( false ) }>
					<div className="hw-modal hw-modal--xl" onClick={ ( e ) => e.stopPropagation() }>
						<div className="hw-modal__header">
							<div className="d-flex align-items-center gap-2">
								<div className="step-card__icon mr-3"><Signature size={ 16 } /></div>
								<span className="step-card__title">{ __( 'Read and accept the general Terms & Conditions', 'hola-weddings' ) }</span>
							</div>
							<button type="button" className="hw-modal__close" onClick={ () => setTcOpen( false ) }>
								&times;
							</button>
						</div>
						<div
							className="hw-modal__body"
							ref={ tcBodyRef }
							onScroll={ handleTcScroll }
							dangerouslySetInnerHTML={ { __html: GLOBAL_TERMS } }
						/>
						<div className="hw-modal__footer">
							<button 
								type="button" 
								className="btn btn-primary" 
								onClick={ scrollTcToBottom }>
								<ArrowDown size={ 18 } className="mr-1" /> { __( 'Go to bottom', 'hola-weddings' ) }
							</button>
							<button
								type="button"
								className="btn btn-success"
								disabled={ ! tcScrolled }
								onClick={ acceptTerms }
							>
								{ __( 'Accept', 'hola-weddings' ) }
								<Check size={ 18 } />
							</button>
						</div>
					</div>
				</div>
			) }

			{ /* ── Signature Modal ────────────────────────────────────────────── */ }
			{ sigOpen && (
				<div className="hw-modal-backdrop" onClick={ () => setSigOpen( false ) }>
					<div className="hw-modal hw-modal--lg" onClick={ ( e ) => e.stopPropagation() }>
						<div className="hw-modal__header">
							<h5 className="mb-0">{ __( 'Create New Signature', 'hola-weddings' ) }</h5>
							<button type="button" className="hw-modal__close" onClick={ () => setSigOpen( false ) }>
								&times;
							</button>
						</div>
						<div className="hw-modal__body">
							<div className="row">
								{ /* Draw */ }
								<div className="col-md-6 mb-3 mb-md-0">
									<h6>{ __( 'Draw', 'hola-weddings' ) }</h6>
									<div className="sig-canvas-wrap">
										<canvas ref={ canvasRef } className="sig-canvas" />
										<div className="sig-canvas-hint">{ __( 'Sign here', 'hola-weddings' ) }</div>
									</div>
								</div>

								{ /* Type */ }
								<div className="col-md-6">
									<h6>{ __( 'Type', 'hola-weddings' ) }</h6>
									<input
										type="text"
										className="form-control mb-3"
										placeholder={ __( 'Type your signature', 'hola-weddings' ) }
										value={ sigText }
										onChange={ ( e ) => setSigText( e.target.value ) }
									/>
									<div className="sig-font-selector">
										{ FONTS.map( ( { value, label } ) => (
											<label
												key={ value }
												className={ `sig-font-option${ sigFont === value ? ' sig-font-option--active' : '' }` }
											>
												<input
													type="radio"
													name="sigFont"
													value={ value }
													checked={ sigFont === value }
													onChange={ () => setSigFont( value ) }
													className="sr-only"
												/>
												<span style={ { fontFamily: `"${ value }", cursive` } }>{ __( 'Signature', 'hola-weddings' ) }</span>
												<small>{ label }</small>
											</label>
										) ) }
									</div>
								</div>
							</div>
						</div>
						<div className="hw-modal__footer">
							<button type="button" className="btn btn-secondary" onClick={ clearSignature }>{ __( 'Clear', 'hola-weddings' ) }</button>
							<button type="button" className="btn btn-success" onClick={ acceptSignature }>{ __( 'Accept', 'hola-weddings' ) }</button>
						</div>
					</div>
				</div>
			) }
		</>
	);
}
