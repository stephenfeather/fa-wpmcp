/**
 * FA WPMCP Admin Scripts
 *
 * @package FAWpmcp
 */

(function ($) {
	'use strict';

	/**
	 * Initialize admin functionality.
	 */
	function init() {
		// Add webhook endpoint button functionality (future enhancement).
		bindEvents();
	}

	/**
	 * Bind event handlers.
	 */
	function bindEvents() {
		// Dismiss notices.
		$( document ).on(
			'click',
			'.notice-dismiss',
			function () {
				$( this ).closest( '.notice' ).fadeOut();
			}
		);
	}

	// Initialize when DOM is ready.
	$( document ).ready( init );

})( jQuery );
