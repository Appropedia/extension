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

		// Add reminder
		Appropedia.addReminder();

		// Load MiniEdit
		Appropedia.loadMiniEdit();

		// Stop any running voice
		window.speechSynthesis.cancel();

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
	 * Add reminders
	 */
	addReminder: function () {
		var text = mw.cookie.get( 'TemplateReminderText' );
		if ( !text ) {
			return;
		}
		var category = mw.cookie.get( 'TemplateReminderCategory' );
		var categories = mw.config.get( 'wgCategories' );
		if ( category && categories && !categories.includes( category ) ) {
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
		mw.loader.load( '/MediaWiki:TemplateReminder.js?action=raw&ctype=text/javascript' );
	},

	/**
	 * MiniEdit is a tool for quickly editing content without leaving the page
	 * Documentation at https://www.mediawiki.org/wiki/MiniEdit
	 */
	loadMiniEdit: function () {
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

$( Appropedia.init );
