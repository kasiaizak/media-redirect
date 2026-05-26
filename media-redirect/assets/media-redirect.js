document.addEventListener(
	'DOMContentLoaded',
	function () {
		const prodDomain = window.mrpFrontendConfig && window.mrpFrontendConfig.prodDomain;
		const localHost  = window.location.origin;

		if ( ! prodDomain ) {
			return;
		}

		document.querySelectorAll( 'img, source' ).forEach(
			function ( el ) {
				if ( el.src && el.src.includes( '/uploads/' ) ) {
					el.src = el.src.replace( localHost, prodDomain );
				}

				if ( el.srcset && el.srcset.includes( '/uploads/' ) ) {
					el.srcset = el.srcset.replaceAll( localHost, prodDomain );
				}

				if ( el.dataset.src && el.dataset.src.includes( '/uploads/' ) ) {
					el.dataset.src = el.dataset.src.replace( localHost, prodDomain );
				}

				if ( el.dataset.bg && el.dataset.bg.includes( '/uploads/' ) ) {
					el.dataset.bg = el.dataset.bg.replace( localHost, prodDomain );
				}

				if (
					el.style &&
					el.style.backgroundImage &&
					el.style.backgroundImage.includes( '/uploads/' )
				) {
					el.style.backgroundImage = el.style.backgroundImage.replace( localHost, prodDomain );
				}
			}
		);
	}
);
