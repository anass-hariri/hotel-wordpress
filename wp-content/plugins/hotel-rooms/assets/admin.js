( function ( $ ) {
	'use strict';

	var $input = $( '#hr_gallery' );
	var $list = $( '.hr-gallery-list' );
	var frame;

	function sync() {
		var ids = $list.children( 'li' ).map( function () {
			return $( this ).data( 'id' );
		} ).get();
		$input.val( ids.join( ',' ) );
	}

	$list.sortable( { items: 'li', tolerance: 'pointer', update: sync } );

	$list.on( 'click', '.hr-remove', function () {
		$( this ).closest( 'li' ).remove();
		sync();
	} );

	$( '.hr-add-images' ).on( 'click', function ( e ) {
		e.preventDefault();
		if ( frame ) {
			frame.open();
			return;
		}
		frame = wp.media( {
			title: hrAdmin.title,
			button: { text: hrAdmin.button },
			library: { type: 'image' },
			multiple: 'add'
		} );
		frame.on( 'select', function () {
			frame.state().get( 'selection' ).each( function ( attachment ) {
				var data = attachment.toJSON();
				if ( $list.children( '[data-id="' + data.id + '"]' ).length ) {
					return;
				}
				var src = data.sizes && data.sizes.thumbnail ? data.sizes.thumbnail.url : data.url;
				var $li = $( '<li/>' ).attr( 'data-id', data.id ).data( 'id', data.id );
				$li.append( $( '<img/>' ).attr( { src: src, alt: '' } ) );
				$li.append( $( '<button type="button" class="hr-remove">×</button>' ) );
				$list.append( $li );
			} );
			sync();
		} );
		frame.open();
	} );
}( jQuery ) );
