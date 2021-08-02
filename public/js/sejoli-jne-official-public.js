(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	$(document).ready(function($){
	    
	    $('body').on( 'change', 'select#shipping_method', function(e){
	        e.preventDefault();

	        $('input[value="cod:::CashOnDelivery"]').closest('.eight').removeClass('hide');
	        $('input[value="cod:::CashOnDelivery"]').prop('checked', true);

	        var s = $(this).val();
	        if (s.match(/COD.*/)) {
	            $('input[value="cod:::CashOnDelivery"]').closest('.eight').removeClass('hide');
	            $('input[value="cod:::CashOnDelivery"]').prop('checked', true);
	        } else {
	            $('input[value="cod:::CashOnDelivery"]').closest('.eight').addClass('hide');
	            $('input[value="cod:::CashOnDelivery"]').prop('checked', false);
	            $('.eight:nth-child(2) .ui input[name="payment_gateway"]').prop('checked', true);
	        }
	    });

    });
    
})( jQuery );
