document.addEventListener( 'DOMContentLoaded', ( event ) => {
	var path_prefix = window.location.pathname;

	document.querySelectorAll( '.test-form' ).forEach( ( e ) => {
		e.addEventListener( 'submit', ( event2 ) => {
			event2.preventDefault();

			fetch( path_prefix + 'api/stock-prices?' + new URLSearchParams( new FormData( event2.target ) ).toString(), {
				'method': 'GET',
			})
			.then( ( response ) => {
				if ( response['ok'] ) {
					return response.json();
				} else {
					throw 'Error';
				}
			})
			.then( ( data ) => {
				document.querySelector( '#result-json' ).textContent = JSON.stringify( data );
			})
			.catch( ( error ) => {
				console.log( error );
			} );
		} );
	} );
} );