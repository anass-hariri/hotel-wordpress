( function () {
	'use strict';

	var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	/* Carrousels : flèches, points, clavier, glissement tactile natif. */
	function initSlider( slider ) {
		var track = slider.querySelector( '.hr-slider__track' );
		var slides = track ? track.children : [];
		var nav = slider.querySelector( '.hr-slider__nav' );
		var prev = slider.querySelector( '.hr-slider__btn--prev' );
		var next = slider.querySelector( '.hr-slider__btn--next' );
		var dots = slider.querySelectorAll( '.hr-slider__dot' );
		if ( ! track || slides.length < 2 || ! prev || ! next ) {
			return;
		}

		if ( nav ) {
			nav.hidden = false;
		}
		slider.tabIndex = 0;

		function index() {
			return Math.round( track.scrollLeft / track.clientWidth );
		}

		function update() {
			var i = index();
			prev.disabled = i <= 0;
			next.disabled = i >= slides.length - 1;
			dots.forEach( function ( dot, d ) {
				if ( d === i ) {
					dot.setAttribute( 'aria-current', 'true' );
				} else {
					dot.removeAttribute( 'aria-current' );
				}
			} );
		}

		function go( i ) {
			i = Math.max( 0, Math.min( slides.length - 1, i ) );
			track.scrollTo( { left: i * track.clientWidth, behavior: reduceMotion ? 'auto' : 'smooth' } );
		}

		prev.addEventListener( 'click', function () { go( index() - 1 ); } );
		next.addEventListener( 'click', function () { go( index() + 1 ); } );
		dots.forEach( function ( dot ) {
			dot.addEventListener( 'click', function () { go( parseInt( dot.getAttribute( 'data-index' ), 10 ) ); } );
		} );

		slider.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'ArrowLeft' ) { e.preventDefault(); go( index() - 1 ); }
			if ( e.key === 'ArrowRight' ) { e.preventDefault(); go( index() + 1 ); }
		} );

		var raf;
		track.addEventListener( 'scroll', function () {
			cancelAnimationFrame( raf );
			raf = requestAnimationFrame( update );
		}, { passive: true } );
		window.addEventListener( 'resize', update );

		// Précharge les photos suivantes quand on s'approche du carrousel.
		slider.addEventListener( 'pointerenter', function () {
			track.querySelectorAll( 'img[loading="lazy"]' ).forEach( function ( img ) { img.loading = 'eager'; } );
		}, { once: true } );

		update();
	}

	/* Filtres : filtrage instantané, URL partageable (?type=), repli sans JS via liens. */
	function initListing( listing ) {
		var nav = listing.querySelector( '.hr-filters' );
		if ( ! nav ) {
			return;
		}
		var links = nav.querySelectorAll( 'a[data-filter]' );
		var cards = listing.querySelectorAll( '.hr-card' );
		var status = listing.querySelector( '.hr-listing__status' );

		function apply( filter, push ) {
			var shown = 0;
			cards.forEach( function ( card ) {
				var types = ( card.getAttribute( 'data-types' ) || '' ).split( ' ' );
				var visible = ! filter || types.indexOf( filter ) !== -1;
				card.hidden = ! visible;
				if ( visible ) { shown++; }
			} );
			links.forEach( function ( a ) {
				if ( a.getAttribute( 'data-filter' ) === filter ) {
					a.setAttribute( 'aria-current', 'true' );
				} else {
					a.removeAttribute( 'aria-current' );
				}
			} );
			if ( status ) {
				status.textContent = shown + ( shown > 1 ? ' hébergements affichés' : ' hébergement affiché' );
			}
			if ( push && window.history && history.replaceState ) {
				var url = new URL( window.location.href );
				if ( filter ) { url.searchParams.set( 'type', filter ); } else { url.searchParams.delete( 'type' ); }
				history.replaceState( null, '', url );
			}
		}

		links.forEach( function ( a ) {
			a.addEventListener( 'click', function ( e ) {
				if ( e.metaKey || e.ctrlKey || e.shiftKey || e.button === 1 ) {
					return;
				}
				e.preventDefault();
				apply( a.getAttribute( 'data-filter' ), true );
			} );
		} );
	}

	/* Galerie plein écran (élément <dialog> natif). */
	function initLightbox() {
		document.querySelectorAll( '[data-hr-open]' ).forEach( function ( btn ) {
			var dialog = document.getElementById( btn.getAttribute( 'data-hr-open' ) );
			if ( ! dialog || typeof dialog.showModal !== 'function' ) {
				return;
			}
			btn.addEventListener( 'click', function () {
				dialog.showModal();
				var track = dialog.querySelector( '.hr-slider__track' );
				if ( track ) {
					track.scrollLeft = 0;
					track.dispatchEvent( new Event( 'scroll' ) );
				}
			} );
			dialog.addEventListener( 'click', function ( e ) {
				if ( e.target === dialog || e.target.hasAttribute( 'data-hr-close' ) ) {
					dialog.close();
				}
			} );
		} );
	}

	function init() {
		initLightbox();
		document.querySelectorAll( '.hr-slider' ).forEach( initSlider );
		document.querySelectorAll( '.hr-listing' ).forEach( initListing );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );