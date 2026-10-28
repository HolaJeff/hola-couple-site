import { __ } from '@wordpress/i18n';
import { useBooking } from '../../hooks/useBooking';

export default function StepSearch() {
	const { search, dispatch, nextStep } = useBooking();

	function handleSubmit( e ) {
		e.preventDefault();
		nextStep();
	}

	function set( field ) {
		return ( e ) => dispatch( { type: 'SET_SEARCH', payload: { [ field ]: e.target.value } } );
	}

	return (
		<div className="card p-3 mt-3">
			<h4>{ window.hwBooking?.strings?.search ?? __( 'Search', 'hola-weddings' ) }</h4>
			<form onSubmit={ handleSubmit }>
				<div className="form-row">
					<div className="form-group col-md-6">
						<label>{ __( 'Check-in', 'hola-weddings' ) }</label>
						<input type="date" className="form-control" value={ search.checkin } onChange={ set( 'checkin' ) } required />
					</div>
					<div className="form-group col-md-6">
						<label>{ __( 'Check-out', 'hola-weddings' ) }</label>
						<input type="date" className="form-control" value={ search.checkout } onChange={ set( 'checkout' ) } required />
					</div>
				</div>
				<div className="form-row">
					<div className="form-group col-md-3">
						<label>{ __( 'Adults', 'hola-weddings' ) }</label>
						<input type="number" className="form-control" min="1" value={ search.adults } onChange={ set( 'adults' ) } />
					</div>
					<div className="form-group col-md-3">
						<label>{ __( 'Teens', 'hola-weddings' ) }</label>
						<input type="number" className="form-control" min="0" value={ search.teens } onChange={ set( 'teens' ) } />
					</div>
					<div className="form-group col-md-3">
						<label>{ __( 'Children', 'hola-weddings' ) }</label>
						<input type="number" className="form-control" min="0" value={ search.children } onChange={ set( 'children' ) } />
					</div>
					<div className="form-group col-md-3">
						<label>{ __( 'Infants', 'hola-weddings' ) }</label>
						<input type="number" className="form-control" min="0" value={ search.infants } onChange={ set( 'infants' ) } />
					</div>
				</div>
				<button type="submit" className="btn btn-info">{ __( 'Search', 'hola-weddings' ) }</button>
			</form>
		</div>
	);
}
