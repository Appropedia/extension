window.Appropedia = {

    init: function () {
        $( '.mw-search-profile-form select' ).change( Appropedia.updateSearchQuery );
        $( '.mw-search-profile-form select' ).each( Appropedia.updateSearchFilterWidth );
 
        // Enable popups on the user, project and help namespaces
        mw.config.set( 'wgContentNamespaces', [ 0, 2, 4, 12 ] );
    },

    /**
     * In Special:Search, update the search query when a filter changes
     */
    updateSearchQuery: function () {
    	var select = $( this );
    	var value = select.val();
    	var options = select.find( 'option' );
    	var params = new URLSearchParams( window.location.search );
    	var search = params.get( 'search' );
    	options.each( function () {
    		var option = $( this ).val();
    		search = search.replace( option, '' );
    	} );
    	search = search + ' ' + value;
    	search = search.replace( /  +/g, ' ' ).trim();
    	params.set( 'search', search );
    	window.location.search = params.toString();
    },

    /**
     * In Special:Search, update the filter search width
     */
    updateSearchFilterWidth: function () {
    	var text = $( 'option:selected', this ).text();
    	var $dummy = $( '<div></div>' ).text( text );
    	$dummy.css( 'position', 'absolute' );
    	$dummy.css( 'visibility', 'hidden' );
    	$( 'body' ).append( $dummy );
    	var width = $dummy.width();
    	$dummy.remove();
    	$( this ).width( width );
    }
};

$( Appropedia.init );