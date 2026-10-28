import { useState, useEffect } from '@wordpress/element';
import { devStore, isLoggedIn as getIsLoggedIn } from '../devStore';

export function useIsLoggedIn() {
	const [ loggedIn, setLoggedIn ] = useState( getIsLoggedIn );

	useEffect( () => devStore.subscribe( () => setLoggedIn( getIsLoggedIn() ) ), [] );

	return loggedIn;
}
