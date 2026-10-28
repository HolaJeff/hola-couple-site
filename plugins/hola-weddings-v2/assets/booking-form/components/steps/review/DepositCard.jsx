import { __, sprintf } from '@wordpress/i18n';
import { useBooking } from '../../../hooks/useBooking';
import { useCart }    from '../../../hooks/useCart';
import { StepCard }   from '../../StepCard';
import { Check, Coins, DollarSign } from 'lucide-react';
import { LOCALE }     from '../../../utils/locale';

const CURRENCY = window.hwBooking?.currency ?? 'USD';

function formatPrice( amount ) {
	return new Intl.NumberFormat( LOCALE, { style: 'currency', currency: CURRENCY } ).format( amount );
}

export default function DepositCard( { errors = {} } ) {
	const { payment, dispatch } = useBooking();
	const { grandTotal, minDepositAmount } = useCart();

	const depositType = payment.depositType ?? 'deposit';

	function set( field ) {
		return ( e ) => dispatch( { type: 'SET_PAYMENT', payload: { [ field ]: e.target.value } } );
	}
	function setDirect( field, value ) {
		dispatch( { type: 'SET_PAYMENT', payload: { [ field ]: value } } );
	}
	function clampDepositAmount() {
		const raw     = Number( payment.depositAmount ?? minDepositAmount );
		const clamped = Math.min( grandTotal, Math.max( minDepositAmount, raw ) );
		setDirect( 'depositAmount', clamped );
	}

	return (
		<StepCard className="mb-3">
			<StepCard.Header icon={ <DollarSign size={ 16 } /> } title={ __( 'Payment Amount', 'hola-weddings' ) } />
			<div className="dep-options">
				{ [
					{ value: 'deposit', label: __( 'Deposit', 'hola-weddings' ),       sub: formatPrice( minDepositAmount ) },
					{ value: 'full',    label: __( 'Full Amount', 'hola-weddings' ),   sub: formatPrice( grandTotal ) },
					{ value: 'custom',  label: __( 'Custom Amount', 'hola-weddings' ), sub: null },
				].map( ( opt ) => {
					const active = depositType === opt.value;
					return (
						<div
							key={ opt.value }
							className={ `dep-option${ active ? ' dep-option--active' : '' }` }
							onClick={ () => {
								const payload = { depositType: opt.value };
								if ( opt.value === 'custom' && payment.depositAmount == null ) {
									payload.depositAmount = minDepositAmount;
								}
								dispatch( { type: 'SET_PAYMENT', payload } );
							} }
							role="radio"
							aria-checked={ active }
							tabIndex={ 0 }
							onKeyDown={ ( e ) => {
								if ( e.key !== 'Enter' ) return;
								const payload = { depositType: opt.value };
								if ( opt.value === 'custom' && payment.depositAmount == null ) {
									payload.depositAmount = minDepositAmount;
								}
								dispatch( { type: 'SET_PAYMENT', payload } );
							} }
						>
							<span className={ `dep-option__radio${ active ? ' dep-option__radio--checked' : '' }` }>
								{ active && <Check size={ 12 } /> }
							</span>
							<span className="dep-option__body">
								<span className="dep-option__label">{ opt.label }</span>
								{ opt.sub && <span className="dep-option__sub">{ opt.sub }</span> }
								{ opt.value === 'custom' && active && (
									<div className="dep-option__custom mt-2" onClick={ ( e ) => e.stopPropagation() }>
										<div className="d-flex align-items-center gap-2">
											<input
												type="number"
												className={ `form-control${ errors.depositAmount ? ' is-invalid' : '' }` }
												style={ { maxWidth: '120px' } }
												min={ minDepositAmount }
												max={ grandTotal }
												value={ payment.depositAmount ?? minDepositAmount }
												onChange={ set( 'depositAmount' ) }
												onBlur={ clampDepositAmount }
												autoComplete='off'
												name='deposit_custom_amount'
											/>
											<span className="text-muted ml-2">{ CURRENCY }</span>
										</div>
										{ errors.depositAmount && (
											<div className="invalid-feedback d-block">{ errors.depositAmount }</div>
										) }
									</div>
								) }
							</span>
						</div>
					);
				} ) }
			</div>
			<small className="text-muted d-block mt-2">
				{ sprintf(
					/* translators: %s: formatted minimum deposit, e.g. $500.00 */
					__( 'Minimum deposit is %s (includes insurance if selected).', 'hola-weddings' ),
					formatPrice( minDepositAmount )
				) }
			</small>
		</StepCard>
	);
}
