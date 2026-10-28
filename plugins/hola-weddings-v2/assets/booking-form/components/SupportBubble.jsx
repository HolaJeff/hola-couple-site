import { __ } from '@wordpress/i18n';
import { useState, useRef, useEffect } from '@wordpress/element';
import { Mail, Phone, X }             from 'lucide-react';

const AGENT = {
	name:     'Miriam Juárez',
	role:     __( 'Customer Service', 'hola-weddings' ),
	image:    'https://holaweddings.com/wp-content/uploads/2022/01/miriam-juarez-playa-del-carmen-150x150.jpg',
	phone:    '+1 888 472 7077',
	phoneTel: '+18884727077',
	email:    'hola@holaweddings.com',
};

export default function SupportBubble() {
	const [ open, setOpen ] = useState( false );
	const rootRef = useRef( null );

	useEffect( () => {
		if ( ! open ) return;
		const onMouse = ( e ) => {
			if ( rootRef.current && ! rootRef.current.contains( e.target ) ) setOpen( false );
		};
		const onKey = ( e ) => { if ( e.key === 'Escape' ) setOpen( false ); };
		document.addEventListener( 'mousedown', onMouse );
		document.addEventListener( 'keydown', onKey );
		return () => {
			document.removeEventListener( 'mousedown', onMouse );
			document.removeEventListener( 'keydown', onKey );
		};
	}, [ open ] );

	return (
		<div className="csb-wrap" ref={ rootRef }>

			{ open && (
				<div className="csb-popup" role="dialog" aria-label={ __( 'Customer support', 'hola-weddings' ) }>
					<div className="csb-popup__header">
						<button
							type="button"
							className="csb-popup__close"
							onClick={ () => setOpen( false ) }
							aria-label={ __( 'Close', 'hola-weddings' ) }
						>
							<X size={ 14 } />
						</button>
						<div className='d-flex align-items-center'>
							<img src={ AGENT.image } alt={ AGENT.name } className="csb-popup__agent-img" />
							<div>
								<div className="csb-popup__agent-name">{ AGENT.name }</div>
								<div className="csb-popup__agent-role">{ AGENT.role }</div>
							</div>
						</div>
					</div>

					<div className="csb-popup__body">
						<p className="csb-popup__text">
							{ __( "Hi there! I'm here to help you plan your dream wedding getaway. Don't hesitate to reach out — I'd love to assist you.", 'hola-weddings' ) }
						</p>
						<div className="csb-popup__actions">
							<a href={ `tel:${ AGENT.phoneTel }` } className="btn btn-primary" title={ __( 'Call Us', 'hola-weddings' ) }>
								<Phone size={ 18 } />
								<span className="d-none d-md-inline">{ AGENT.phone }</span>
								<span className="d-inline d-md-none">{ __( 'Call Us', 'hola-weddings' ) }</span>
							</a>
							<a href={ `mailto:${ AGENT.email }` } className="btn btn-outline-primary" title={ __( 'Message Us', 'hola-weddings' ) }>
								<Mail size={ 18 } />
								{ __( 'Message Us', 'hola-weddings' ) }
							</a>
						</div>
					</div>
				</div>
			) }

			<button
				type="button"
				className={ `csb-bubble${ open ? ' csb-bubble--active' : '' }` }
				onClick={ () => setOpen( ( v ) => ! v ) }
				aria-expanded={ open }
				aria-label={ __( 'Open customer support', 'hola-weddings' ) }
			>
				<img src={ AGENT.image } alt={ AGENT.name } className="csb-bubble__img" />
				{ !open && 
					(<span className="csb-bubble__label">{ __( 'Need help?', 'hola-weddings' ) }</span>)
				}
			</button>

		</div>
	);
}
