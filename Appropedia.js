window.Appropedia = {

	/**
	 * Initialization script
	 */
	init: function () {
		$( '#ca-print' ).on( 'click', Appropedia.print ),

		// Update the search query when a search filter changes
		$( '.mw-search-profile-form select' ).on( 'change', Appropedia.updateSearchQuery );

		// Fix the width of the search filters
		$( '.mw-search-profile-form select' ).each( Appropedia.updateSearchFilterWidth );

		// Save quiz scores
		$( '.quiz .score' ).each( Appropedia.saveQuizScore );

		// Manage reminders
		Appropedia.checkReminder();
		$( '.template-set-reminder-set-button' ).click( Appropedia.setReminder );
		$( '.template-set-reminder-unset-button' ).click( Appropedia.unsetReminder );

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
			'formatversion': 2,
			'action': 'parse',
			'text': wikitext,
			'title': mw.config.get( 'wgPageName' )
		} ).done( function ( data ) {
			var html = data.parse.text;
			var $html = $( html );
			mw.notify( $html, { tag: 'reminder' } );
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
		var quiz = $( this ).closest( '.quiz' );

		// Get the score
		var score = quiz.find( '.score' ).text();
		var total = quiz.find( '.total' ).text();
		if ( !score ) {
			return; // Don't submit empty quizzes
		}

		// Only submit in the main namespace
		if ( mw.config.get( 'wgCanonicalNamespace' ) ) {
			return;
		}

		// Set the page and section where to post
		var page = mw.config.get( 'wgPageName' );
		var title = new mw.Title( page );
		var talk = title.getTalkPage().getPrefixedText();
		var section = 'Quiz scores';

		// Build the wikitext
		var wikitext = '{{Quiz score';
		wikitext += '\n| score = ' + score;
		wikitext += '\n| total = ' + total;
		wikitext += '\n}}';

		// Append the wikitext to the talk page
		var api = new mw.Api();
		return api.get( {
			'action': 'parse',
			'page': talk,
			'prop': 'text',
			'formatversion': 2
		} ).always( function ( data ) {

			// Figure out if the section already exists and its number
			var sectionNumber, sectionTitle;
			if ( section ) {
				sectionNumber = 'new';
				sectionTitle = section;
				if ( data !== 'missingtitle' ) {
					var html = $.parseHTML( data.parse.text );
					var header = $( ':header:contains(' + sectionTitle + ')', html );
					if ( header.length ) {
						sectionNumber = 1 + header.prevAll( ':header' ).length;
						sectionTitle = null;
						wikitext = '\n\n' + wikitext;
					}
				}
			} else if ( data !== 'missingtitle' ) {
				wikitext = '\n\n' + wikitext;
			}
			var params = {
				'action': 'edit',
				'title': talk,
				'section': sectionNumber,
				'summary': 'Save quiz score',
				'bot': true,
			};
			if ( sectionNumber === 'new' ) {
				params.sectiontitle = sectionTitle;
				params.text = wikitext;
			} else {
				params.appendtext = wikitext;
			}
			if ( mw.config.get( 'wgUserName' ) ) {
				return api.postWithEditToken( params );
			} else {

				// If the user is not logged in, we post with a bot account
				return api.login(
					'Bot@Quizzes',
					'ogs8314dohsujap2pf249pgpv0av5q8a'
				).then( function () {
					return api.postWithEditToken( params );
				} );
			}
		} );
	}
};

mw.loader.using( [
	'mediawiki.api',
	'mediawiki.cookie'
], Appropedia.init );
