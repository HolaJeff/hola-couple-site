import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect, createInterpolateElement } from '@wordpress/element';
import { useBooking }    from '../../../hooks/useBooking';
import { useIsLoggedIn } from '../../../hooks/useIsLoggedIn';
import { useLoginModal } from '../../../hooks/useLoginModal';
import { StepCard }      from '../../StepCard';
import GoogleLoginButton from '../../GoogleLoginButton';
import { Check, CircleUserRound, LogIn } from 'lucide-react';

const HIDE_LOGIN_CTA      = Boolean( window.hwBooking?.hideLoginCta );
const HIDE_CREATE_ACCOUNT = Boolean( window.hwBooking?.hideCreateAccount );

export default function AccountCard( { confirmPassword, onConfirmChange, errors = {} } ) {
	const isLoggedIn = useIsLoggedIn();
	const { accountEmail, password, billing, dispatch } = useBooking();
	const loginModal = useLoginModal();

	const contactEmail = ( billing?.email ?? '' ).trim();

	// Seeded from the data rather than defaulting to false, so the choice survives
	// a remount — returning from a Google sign-in, for instance — without having
	// to be persisted separately.
	const [ useContactEmail, setUseContactEmail ] = useState(
		() => !! contactEmail && contactEmail === accountEmail
	);

	useEffect( () => {
		if ( ! useContactEmail ) return;

		// The contact email was cleared after being carried over. Release the
		// field, otherwise it stays read-only with no visible checkbox to undo it.
		if ( ! contactEmail ) {
			setUseContactEmail( false );
			return;
		}

		// Keep in step when the contact email is edited afterwards.
		if ( contactEmail !== accountEmail ) {
			dispatch( { type: 'SET_ACCOUNT_EMAIL', payload: contactEmail } );
		}
	}, [ useContactEmail, contactEmail, accountEmail ] );

	function toggleContactEmail( checked ) {
		setUseContactEmail( checked );
		dispatch( { type: 'SET_ACCOUNT_EMAIL', payload: checked ? contactEmail : '' } );
	}

	// hideLoginCta hides the "already have an account → Log In" CTA only;
	// hideCreateAccount is what removes the card altogether.
	if ( isLoggedIn || HIDE_CREATE_ACCOUNT ) return null;

	return (
		<>
			<StepCard className="mb-3">
				<StepCard.Header icon={ <CircleUserRound size={ 16 } /> } title={ __( 'Create an account (Optional)', 'hola-weddings' ) } />
				<p className="text-muted mb-3">{ __( "Creating an account is optional — you can complete your booking without one. It only takes a moment, and you'll be able to view and manage your reservation at any time.", 'hola-weddings' ) }</p>

				{ contactEmail && (
					<label
						htmlFor="useContactEmail"
						className={ `border pay-auth mb-3${ useContactEmail ? ' pay-auth--checked' : '' }` }
					>
						<input
							type="checkbox"
							id="useContactEmail"
							className="sr-only"
							checked={ useContactEmail }
							onChange={ ( e ) => toggleContactEmail( e.target.checked ) }
						/>
						<span className="pay-auth__box">
							{ useContactEmail && <Check size={ 14 } className="pay-auth__check" /> }
						</span>
						<span className="pay-auth__text">
							{ createInterpolateElement(
								sprintf(
									/* translators: %s: the main contact's email address */
									__( 'Use email from main contact: <strong>%s</strong>.', 'hola-weddings' ),
									contactEmail
								),
								{ strong: <strong /> }
							) }
						</span>
					</label>
				) }

				<div className="form-row">
					<div className="form-group col-12">
						<label>{ __( 'Email', 'hola-weddings' ) }</label>
						<input
							type="email"
							className={ `form-control${ errors.accountEmail ? ' is-invalid' : '' }` }
							value={ accountEmail }
							onChange={ ( e ) => dispatch( { type: 'SET_ACCOUNT_EMAIL', payload: e.target.value } ) }
							autoComplete="email"
							readOnly={ useContactEmail }
						/>
						{ errors.accountEmail && <div className="invalid-feedback">{ errors.accountEmail }</div> }
					</div>
					<div className="form-group col-md-6">
						<label>{ __( 'Password', 'hola-weddings' ) }</label>
						<input
							type="password"
							className={ `form-control${ errors.password ? ' is-invalid' : '' }` }
							value={ password }
							onChange={ ( e ) => dispatch( { type: 'SET_PASSWORD', payload: e.target.value } ) }
							autoComplete="new-password"
						/>
						{ errors.password && <div className="invalid-feedback">{ errors.password }</div> }
					</div>
					<div className="form-group col-md-6">
						<label>{ __( 'Confirm Password', 'hola-weddings' ) }</label>
						<input
							type="password"
							className={ `form-control${ errors.confirmPassword ? ' is-invalid' : '' }` }
							value={ confirmPassword }
							onChange={ ( e ) => onConfirmChange( e.target.value ) }
							autoComplete="new-password"
						/>
						{ errors.confirmPassword && <div className="invalid-feedback">{ errors.confirmPassword }</div> }
					</div>
				</div>

				{ ! HIDE_LOGIN_CTA && (
					<>
						<div className="ac-or">
							<span className="ac-or__line" />
							<span className="ac-or__label">{ __( 'OR', 'hola-weddings' ) }</span>
							<span className="ac-or__line" />
						</div>

						<div className="d-flex align-items-center flex-wrap">
							<div>
								<p className='mb-2 mb-md-0 mr-3 font-weight-bold h6'>{ __( 'Do you already have an account?', 'hola-weddings' ) }</p>
							</div>
							<button className="btn btn-primary" type="button" onClick={ () => loginModal.open() }>
								<LogIn />
								{ __( 'Log In', 'hola-weddings' ) }
							</button>
							<GoogleLoginButton className="hw-google-btn--inline rounded-circle p-3 ml-2" hideLabel={true} />
						</div>
					</>
				) }

			</StepCard>
		</>
	);
}
