document.addEventListener(
	'DOMContentLoaded',
	function () {
		const config = window.mrpFrontendConfig || {};
		const localBaseUrl = config.localBaseUrl;
		const remoteBaseUrl = config.remoteBaseUrl;

		if ( ! localBaseUrl || ! remoteBaseUrl ) {
			return;
		}

		function rewriteValue( value ) {
			if ( ! value || ! value.includes( '/uploads/' ) || ! value.includes( localBaseUrl ) ) {
				return value;
			}

			return value.replaceAll( localBaseUrl, remoteBaseUrl );
		}

		document.querySelectorAll( 'img, source, a' ).forEach(
			function ( el ) {
				el.src = rewriteValue( el.src );
				el.srcset = rewriteValue( el.srcset );
				el.href = rewriteValue( el.href );
				el.poster = rewriteValue( el.poster );

				if ( el.dataset ) {
					el.dataset.src = rewriteValue( el.dataset.src );
					el.dataset.srcset = rewriteValue( el.dataset.srcset );
					el.dataset.bg = rewriteValue( el.dataset.bg );
					el.dataset.vcZoom = rewriteValue( el.dataset.vcZoom );
				}

				if ( el.style && el.style.backgroundImage ) {
					el.style.backgroundImage = rewriteValue( el.style.backgroundImage );
				}
			}
		);
	}
);
