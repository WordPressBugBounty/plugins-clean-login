( function ( blocks, element, ServerSideRender, blockEditor ) {
	var el = element.createElement;

	[ 'login', 'register', 'restore', 'edit', 'change-password', 'logout' ].forEach( function ( name ) {
		blocks.registerBlockType( 'clean-login/' + name, {
			edit: function () {
				var props = blockEditor && blockEditor.useBlockProps ? blockEditor.useBlockProps() : {};
				return el( 'div', props, el( ServerSideRender, { block: 'clean-login/' + name } ) );
			},
			save: function () {
				return null;
			}
		} );
	} );
} )( window.wp.blocks, window.wp.element, window.wp.serverSideRender, window.wp.blockEditor );
