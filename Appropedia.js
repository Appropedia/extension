window.Appropedia = {

	/**
	 * Initialization script
	 */
	init: function () {
		var $context = $( '#mw-content-text' );

		// Update the search query when a search filter changes
		$context.find( '.mw-search-profile-form select' ).on( 'change', Appropedia.updateSearchQuery );

		// Fix the width of the search filters
		$context.find( '.mw-search-profile-form select' ).each( Appropedia.updateSearchFilterWidth );

		// Save quiz scores
		$context.find( '.quiz .score' ).each( Appropedia.saveQuizScore );

		// Manage reminders
		Appropedia.checkReminder();
		$context.find( '.template-set-reminder-set-button' ).click( Appropedia.setReminder );
		$context.find( '.template-set-reminder-unset-button' ).click( Appropedia.unsetReminder );

		// Print
		$( '#ca-print' ).on( 'click', Appropedia.print ),

		// Load MiniEdit
		Appropedia.loadMiniEdit();

		// Enable popups on more namespaces
		mw.config.set( 'wgContentNamespaces', [ 0, 2, 4, 12 ] );
	},

	/**
	 * Print or download the current page
	 */
	print: function () {
		window.print();
	},

	/**
	 * In Special:Search, update the search query when a filter changes
	 */
	updateSearchQuery: function () {
		var $select = $( this );
		var value = $select.val();
		var $options = $select.find( 'option' );
		var params = new URLSearchParams( window.location.search );
		var search = params.get( 'search' ) || '';
		$options.each( function () {
			var value = $( this ).val();
			search = search.replace( value, '' );
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
		var $select = $( this );
		var text = $select.find( 'option:selected' ).text();
		var $dummy = $( '<div></div>' ).text( text );
		$dummy.css( { 'position': 'absolute', 'visibility': 'hidden' } );
		$( 'body' ).append( $dummy );
		var width = $dummy.width();
		$dummy.remove();
		$select.width( width );
	},

	/**
	 * This interacts with {{Set reminder}}
	 */
	setReminder: function () {
		var $template = $( this ).closest( '.template-set-reminder' );
		var text = $template.data( 'text' );
		var image = $template.data( 'image' );
		var category = $template.data( 'category' );
		var categoryIgnore = $template.data( 'category-ignore' );
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
		var text = mw.cookie.get( 'ReminderText' );
		var $template = $( '.template-set-reminder[data-text="' + text + '"]' );
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
		var text = mw.cookie.get( 'ReminderText' );
		if ( !text ) {
			return;
		}
		var $template = $( '.template-set-reminder[data-text="' + text + '"]' );
		if ( $template.length ) {
			$template.children().toggle();
		}
		var category = mw.cookie.get( 'ReminderCategory' );
		var categories = mw.config.get( 'wgCategories' );
		if ( category && categories && !categories.includes( category ) ) {
			return;
		}
		var categoryIgnore = mw.cookie.get( 'ReminderCategoryIgnore' );
		if ( categoryIgnore && categories.includes( categoryIgnore ) ) {
			return;
		}
		var action = mw.config.get( 'wgAction' );
		if ( action !== 'view' ) {
			return;
		}
		var contentmodel = mw.config.get( 'wgPageContentModel' );
		if ( contentmodel !== 'wikitext' ) {
			return;
		}
		var mainpage = mw.config.get( 'wgIsMainPage' );
		if ( mainpage ) {
			return;
		}
		Appropedia.showReminder();
	},

	/**
	 * Show the reminder
	 */
	showReminder: function () {
		var text = mw.cookie.get( 'ReminderText' );
		var image = mw.cookie.get( 'ReminderImage', null, 'Antu appointment-reminder.svg' );
		var wikitext = '[[File:{{PAGENAME:' + image + '}}|right|38px|link=]]' + text;
		wikitext += '<div class="mw-ui-button">Unset reminder</div>';
		new mw.Api().get( {
			formatversion: 2,
			action: 'parse',
			text: wikitext,
			title: mw.config.get( 'wgPageName' )
		} ).done( function ( data ) {
			var html = data.parse.text;
			var $html = $( html );
			mw.notify( $html, { tag: 'reminder', autoHide: false } );
			$html.find( '.mw-ui-button' ).click( Appropedia.unsetReminder );
		} );
	},

	/**
	 * MiniEdit is a tool for quickly editing content without leaving the page
	 * Documentation at https://www.mediawiki.org/wiki/MiniEdit
	 */
	loadMiniEdit: function () {
		// Only load for logged-in users
		var user = mw.config.get( 'wgUserName' );
		if ( !user ) {
			return;
		}

		// Only load when viewing
		var action = mw.config.get( 'wgAction' );
		if ( action !== 'view' ) {
			return;
		}

		// Only load in useful namespaces
		var namespaces = [ 0, 2, 4, 12, 14 ]; // See https://www.mediawiki.org/wiki/Manual:Namespace_constants
		var namespace = mw.config.get( 'wgNamespaceNumber' );
		var talk = namespace % 2 === 1; // Talk pages always have odd namespaces
		if ( !namespaces.includes( namespace ) && !talk ) {
			return;
		}

		// Only load in wikitext pages
		var model = mw.config.get( 'wgPageContentModel' );
		if ( model !== 'wikitext' ) {
			return;
		}

		// Don't load in automatic translations
		var categories = mw.config.get( 'wgCategories' );
		if ( categories.includes( 'Automatic translations' ) ) {
			return;
		}

		// Documentation page to link from the edit summaries
		mw.config.set( 'miniedit-page', 'Appropedia:MiniEdit' );

		// Change tag to track edits made with the tool
		mw.config.set( 'miniedit-tag', 'miniedit' );

		// Load the latest code directly from the central version at MediaWiki.org
		mw.loader.load( '//www.mediawiki.org/wiki/MediaWiki:MiniEdit.js?action=raw&ctype=text/javascript' );
	},

	/**
	 * Save quiz scores
	 */
	saveQuizScore: function () {
		var $quiz = $( this ).closest( '.quiz' );

		// Get the score
		var score = $quiz.find( '.score' ).text();
		var total = $quiz.find( '.total' ).text();

		// Don't submit empty quizzes
		if ( !score || !total ) {
			return;
		}

		// Only submit in the main namespace
		if ( mw.config.get( 'wgCanonicalNamespace' ) ) {
			return;
		}

		// Figure out the talk page where to post
		var page = mw.config.get( 'wgPageName' );
		var title = new mw.Title( page );
		var talk = title.getTalkPage().getPrefixedText();

		// Figure out if the section already exists and its number
		var api = new mw.Api();
		api.get( {
			action: 'parse',
			page: talk,
			prop: 'text',
			formatversion: 2
		} ).fail( console.log ).always( function ( data ) {

			var section = 'new';
			if ( data !== 'missingtitle' ) {
				var html = $.parseHTML( data.parse.text );
				var $header = $( '#Quiz_scores', html );
				if ( $header.length ) {
					section = $header.prevAll( ':header' ).length + 1;
				}
			}
			var params = {
				action: 'edit',
				title: talk,
				section: section,
				summary: 'Save quiz score'
			};
			var wikitext = '* ' + score + '/' + total;
			if ( section === 'new' ) {
				params.sectiontitle = 'Quiz scores';
				params.text = wikitext;
			} else {
				params.appendtext = '\n' + wikitext;
			}
			api.postWithEditToken( params ).fail( console.log );
		} );
	}
};

mw.loader.using( [
	'mediawiki.api',
	'mediawiki.cookie'
], Appropedia.init );
