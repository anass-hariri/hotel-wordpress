( function () {
	'use strict';

	function init() {
		var burger = document.querySelector( '.hr-header__burger' );
		var drawer = document.getElementById( 'hr-drawer' );
		if ( ! burger || ! drawer ) {
			return;
		}
		var panel = drawer.querySelector( '.hr-drawer__panel' );

		function open() {
			drawer.hidden = false;
			burger.setAttribute( 'aria-expanded', 'true' );
			document.body.classList.add( 'hr-drawer-open' );
			var first = panel.querySelector( 'a, button' );
			if ( first ) {
				first.focus();
			}
		}

		var side = drawer.querySelector( '.hr-drawer__side' );

		// « main » ferme le sous-menu ; sinon affiche le sous-menu demandé dans le panneau latéral.
		function showView( name ) {
			drawer.querySelectorAll( '.hr-drawer__side [data-hr-view]' ).forEach( function ( v ) {
				v.hidden = v.getAttribute( 'data-hr-view' ) !== name;
			} );
			if ( side ) {
				side.hidden = 'main' === name;
			}
			drawer.querySelectorAll( '[data-hr-sub]' ).forEach( function ( b ) {
				b.setAttribute( 'aria-expanded', b.getAttribute( 'data-hr-sub' ) === name ? 'true' : 'false' );
			} );
			if ( 'main' !== name ) {
				var target = drawer.querySelector( '[data-hr-view="' + name + '"]' );
				var focusable = target && target.querySelector( 'a, .hr-drawer__back' );
				if ( focusable && window.matchMedia( '(max-width: 899px)' ).matches ) {
					target.querySelector( '.hr-drawer__back' ).focus();
				} else if ( focusable ) {
					focusable.focus();
				}
			}
		}

		drawer.querySelectorAll( '[data-hr-sub]' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var name = btn.getAttribute( 'data-hr-sub' );
				// Un second clic sur la même rubrique referme le sous-menu.
				showView( 'true' === btn.getAttribute( 'aria-expanded' ) ? 'main' : name );
			} );
		} );
		drawer.querySelectorAll( '[data-hr-back]' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var name = btn.closest( '[data-hr-view]' ).getAttribute( 'data-hr-view' );
				showView( 'main' );
				var parent = drawer.querySelector( '[data-hr-sub="' + name + '"]' );
				if ( parent ) {
					parent.focus();
				}
			} );
		} );

		function close() {
			showView( 'main' );
			drawer.hidden = true;
			burger.setAttribute( 'aria-expanded', 'false' );
			document.body.classList.remove( 'hr-drawer-open' );
			burger.focus();
		}

		burger.addEventListener( 'click', open );
		drawer.querySelectorAll( '[data-hr-drawer-close]' ).forEach( function ( el ) {
			el.addEventListener( 'click', close );
		} );
		document.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key && ! drawer.hidden ) {
				close();
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );