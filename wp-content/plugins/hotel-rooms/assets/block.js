( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var be = wp.blockEditor;
	var c = wp.components;
	var ServerSideRender = wp.serverSideRender;

	wp.blocks.registerBlockType( 'hotel-rooms/listing', {
		edit: function ( props ) {
			var a = props.attributes;
			var set = function ( key ) {
				return function ( value ) {
					var patch = {};
					patch[ key ] = value;
					props.setAttributes( patch );
				};
			};

			return el(
				'div',
				be.useBlockProps(),
				el(
					be.InspectorControls,
					null,
					el(
						c.PanelBody,
						{ title: __( 'Contenu', 'hotel-rooms' ), initialOpen: true },
						el( c.TextControl, {
							label: __( 'Titre', 'hotel-rooms' ),
							help: __( 'Entourez un mot d’*astérisques* pour l’italique.', 'hotel-rooms' ),
							value: a.title,
							onChange: set( 'title' )
						} ),
						el( c.TextareaControl, { label: __( 'Introduction', 'hotel-rooms' ), value: a.intro, onChange: set( 'intro' ) } ),
						el( c.ToggleControl, { label: __( 'Afficher le fil d’Ariane', 'hotel-rooms' ), checked: a.breadcrumb, onChange: set( 'breadcrumb' ) } ),
						el( c.ToggleControl, { label: __( 'Afficher les filtres', 'hotel-rooms' ), checked: a.filters, onChange: set( 'filters' ) } ),
						el( c.TextControl, {
							label: __( 'Restreindre à la catégorie (slug)', 'hotel-rooms' ),
							value: a.category,
							onChange: set( 'category' )
						} ),
						el( c.RangeControl, {
							label: __( 'Nombre maximum (-1 = tous)', 'hotel-rooms' ),
							min: -1,
							max: 50,
							value: a.limit,
							onChange: set( 'limit' )
						} )
					)
				),
				el( c.Disabled, null, el( ServerSideRender, { block: 'hotel-rooms/listing', attributes: a } ) )
			);
		},
		save: function () {
			return null;
		}
	} );
}( window.wp ) );
