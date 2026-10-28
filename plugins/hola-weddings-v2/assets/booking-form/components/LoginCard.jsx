import { __ } from '@wordpress/i18n';
import { useIsLoggedIn }  from '../hooks/useIsLoggedIn';
import { useLoginModal }  from '../hooks/useLoginModal';
import { CircleUserRound, LogIn } from 'lucide-react';

export default function LoginCard() {
	const isLoggedIn = useIsLoggedIn();
	const loginModal = useLoginModal();

	if ( isLoggedIn || window.hwBooking?.hideLoginCta ) return null;

	return (
		<>
			<div className="login-card">
				<div className="login-card__icon-wrap">
					<CircleUserRound size={ 32 } />
				</div>
				<div className="login-card__body">
					<p className="login-card__heading">{ __( 'Do you already have an account?', 'hola-weddings' ) }</p>
					<p className="login-card__sub">{ __( 'Sign in to pre-fill your details and access your booking history.', 'hola-weddings' ) }</p>
				</div>
				<button className="login-card__btn btn btn-primary" onClick={ () => loginModal.open() }>
					<LogIn />
					{ __( 'Log In', 'hola-weddings' ) }
				</button>
			</div>
		</>
	);
}
