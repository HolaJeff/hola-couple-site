import { useEffect }                   from '@wordpress/element';
import { BookingProvider, useBooking } from './store/bookingContext';
import SearchBox     from './components/SearchBox';
import SubmitManager from './components/SubmitManager';
import StepRooms     from './components/steps/StepRooms';
import StepGuests    from './components/steps/StepGuests';
import StepBilling   from './components/steps/StepBilling';
import StepInsurance from './components/steps/StepInsurance';
import StepReview    from './components/steps/StepReview';
import CartSidebar   from './components/cart/CartSidebar';
import MobileSummary from './components/cart/MobileSummary';
import StepProgress  from './components/StepProgress';
import LoginCard     from './components/LoginCard';
import DevToolbar    from './components/DevToolbar';
import SupportBubble  from './components/SupportBubble';
import TextSizeControl from './components/TextSizeControl';
import { useTextScale } from './hooks/useTextScale';

const STEPS = [
	StepRooms,
	StepGuests,
	StepBilling,
	StepInsurance,
	StepReview,
];

const STEP_REVIEW = STEPS.length - 1;

function BookingForm() {
	const { step, searched } = useBooking();
	const Step = STEPS[ step ];
	const textScale = useTextScale();

	useEffect( () => {
		const el = document.getElementById( 'booking-form' );
		if ( el ) {
			el.scrollIntoView( { behavior: 'smooth', block: 'start' } );
		} else {
			window.scrollTo( { top: 0, behavior: 'smooth' } );
		}
	}, [ step ] );

	// The wrapper carries the reader's text scale; styles/_variables.scss turns
	// it into --hw-bf-base, which every font size in the form is derived from.
	return (
		<div className="hw-bf-text" style={ { '--hw-bf-scale': textScale.scale } }>
			<TextSizeControl { ...textScale } />
			<div id="booking-form">
				<SearchBox />
				<SubmitManager />
				{ ( ! searched || step !== STEP_REVIEW ) && <LoginCard /> }
				{ searched && (
					<>
						<StepProgress />
						<div className="row mt-3">
							<div className="col-lg-8">
								<Step />
							</div>
							<div id="sidebar" className="col-lg-4 mt-4 mt-lg-0 d-none d-lg-block">
								<CartSidebar />
							</div>
						</div>
						<MobileSummary />
					</>
				) }
			</div>
		</div>
	);
}

export default function App() {
	return (
		<BookingProvider>
			<BookingForm />
			<DevToolbar />
			<SupportBubble />
		</BookingProvider>
	);
}
