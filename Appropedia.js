const Appropedia = {

	/**
	 * Initialization script
	 */
	init: function () {
		const $content = $( '#mw-content-text' );

		// Special:Search
		$content.find( '.mw-search-profile-form select' ).each( Appropedia.updateSearchFilterWidth );
		$content.find( '.mw-search-profile-form select' ).on( 'change', Appropedia.updateSearchQuery );

		// Reminders
		// @todo Move to gadgets
		Appropedia.checkReminder();
		$content.find( '.template-set-reminder-set-button' ).click( Appropedia.setReminder );
		$content.find( '.template-set-reminder-unset-button' ).click( Appropedia.unsetReminder );

		// Enable popups on more namespaces
		mw.config.set( 'wgContentNamespaces', [ 0, 2, 4, 12 ] );
	},

	/**
	 * In Special:Search, update the filter search width
	 */
	updateSearchFilterWidth: function () {
		const $select = $( this );
		const text = $select.find( 'option:selected' ).text();
		const $dummy = $( '<div></div>' ).text( text );
		$dummy.css( { position: 'absolute', visibility: 'hidden' } );
		$( 'body' ).append( $dummy );
		const width = $dummy.width();
		$dummy.remove();
		$select.width( width );
	},

	/**
	 * In Special:Search, update the search query when a filter changes
	 */
	updateSearchQuery: function () {
		const $select = $( this );
		const value = $select.val();
		const $options = $select.find( 'option' );
		const params = new URLSearchParams( window.location.search );
		let search = params.get( 'search' ) || '';
		$options.each( function () {
			const value = $( this ).val();
			search = search.replace( value, '' );
		} );
		search = search + ' ' + value;
		search = search.replace( /  +/g, ' ' ).trim();
		params.set( 'search', search );
		window.location.search = params.toString();
	},

	/**
	 * This interacts with {{Set reminder}}
	 */
	setReminder: function () {
		const $template = $( this ).closest( '.template-set-reminder' );
		const text = $template.data( 'text' );
		const image = $template.data( 'image' );
		const category = $template.data( 'category' );
		const categoryIgnore = $template.data( 'category-ignore' );
		mw.cookie.set( 'ReminderText', text );
		mw.cookie.set( 'ReminderImage', image );
		mw.cookie.set( 'ReminderCategory', category );
		mw.cookie.set( 'ReminderCategoryIgnore', categoryIgnore );
		$template.children().toggle();
		Appropedia.showReminder();
	},

	/**
	 * This interacts with {{Set reminder}}
	 */
	unsetReminder: function () {
		const text = mw.cookie.get( 'ReminderText' );
		const $template = $( '.template-set-reminder[data-text="' + text + '"]' );
		if ( $template.length ) {
			$template.children().toggle();
		}
		mw.cookie.set( 'ReminderText', null );
		mw.cookie.set( 'ReminderImage', null );
		mw.cookie.set( 'ReminderCategory', null );
		mw.cookie.set( 'ReminderCategoryIgnore', null );
	},
	
	/**
	 * Check if a reminder needs to be shown
	 */
	checkReminder: function () {
		const text = mw.cookie.get( 'ReminderText' );
		if ( !text ) {
			return;
		}
		const $template = $( '.template-set-reminder[data-text="' + text + '"]' );
		if ( $template.length ) {
			$template.children().toggle();
		}
		const category = mw.cookie.get( 'ReminderCategory' );
		const categories = mw.config.get( 'wgCategories' );
		if ( category && categories && !categories.includes( category ) ) {
			return;
		}
		const categoryIgnore = mw.cookie.get( 'ReminderCategoryIgnore' );
		if ( categoryIgnore && categories.includes( categoryIgnore ) ) {
			return;
		}
		const action = mw.config.get( 'wgAction' );
		if ( action !== 'view' ) {
			return;
		}
		const contentmodel = mw.config.get( 'wgPageContentModel' );
		if ( contentmodel !== 'wikitext' ) {
			return;
		}
		const mainpage = mw.config.get( 'wgIsMainPage' );
		if ( mainpage ) {
			return;
		}
		Appropedia.showReminder();
	},

	/**
	 * Show the reminder
	 */
	showReminder: function () {
		const text = mw.cookie.get( 'ReminderText' );
		const image = mw.cookie.get( 'ReminderImage', null, 'Antu appointment-reminder.svg' );
		let wikitext = '[[File:{{PAGENAME:' + image + '}}|right|38px|link=]]' + text;
		wikitext += '<div class="mw-ui-button">Unset reminder</div>';
		new mw.Api().get( {
			formatversion: 2,
			action: 'parse',
			text: wikitext,
			title: mw.config.get( 'wgPageName' )
		} ).done( function ( data ) {
			const html = data.parse.text;
			const $html = $( html );
			mw.notify( $html, { tag: 'reminder', autoHide: false } );
			$html.find( '.mw-ui-button' ).click( Appropedia.unsetReminder );
		} );
	}
};

mw.loader.using( [
	'mediawiki.api',
	'mediawiki.cookie'
], Appropedia.init );
