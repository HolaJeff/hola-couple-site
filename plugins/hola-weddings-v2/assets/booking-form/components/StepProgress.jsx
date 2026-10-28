import { __ } from '@wordpress/i18n';
import { useBooking } from '../hooks/useBooking';
import { BedDouble, Check, ClipboardCheck, ContactRound, ShieldCheck, UsersRound } from 'lucide-react';

const STEPS = [
	{ label: __( 'Room', 'hola-weddings' ),      Icon: BedDouble      },
	{ label: __( 'Guests', 'hola-weddings' ),    Icon: UsersRound     },
	{ label: __( 'Contact', 'hola-weddings' ),   Icon: ContactRound   },
	{ label: __( 'Insurance', 'hola-weddings' ), Icon: ShieldCheck    },
	{ label: __( 'Review', 'hola-weddings' ),    Icon: ClipboardCheck },
];

export default function StepProgress() {
	const { step } = useBooking();

	return (
		<div className="sp-wrap">
			{ STEPS.map( ( { label, Icon }, i ) => {
				const isPast    = i < step;
				const isCurrent = i === step;

				return (
					<div
						key={ i }
						className={ `sp-bar${ isPast ? ' sp-bar--done' : '' }${ isCurrent ? ' sp-bar--active' : '' }` }
					>
						<span className="sp-bar__num">
							{ isPast
								? <Check size={ 12 } />
								: <Icon  size={ 14 } />
							}
						</span>
						<span className="sp-bar__label">{ label }</span>
					</div>
				);
			} ) }
		</div>
	);
}
