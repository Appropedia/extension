window.Appropedia = {

	/**
	 * Initialization script
	 */
	init: function () {
		// Update the search query when a search filter changes
		$( '.mw-search-profile-form select' ).on( 'change', Appropedia.updateSearchQuery );

		// Fix the width of the search filters
		$( '.mw-search-profile-form select' ).each( Appropedia.updateSearchFilterWidth );

		// Save quiz scores
		$( '.quiz .score' ).each( Appropedia.saveQuizScore );

		// Check for automatic translations every 5 seconds
		Appropedia.interval = setInterval( Appropedia.checkForTranslation, 5000 );

		// Add reminder
		Appropedia.addReminder();

		// Load WikiEdit
		Appropedia.loadWikiEdit();

		// Enable popups on more namespaces
		mw.config.set( 'wgContentNamespaces', [ 0, 2, 4, 12 ] );
	},

	/**
	 * In Special:Search, update the search query when a filter changes
	 */
	updateSearchQuery: function () {
		var $select = $( this );
		var value = $select.val();
		var $options = $select.find( 'option' );
		var params = new URLSearchParams( window.location.search );
		var search = params.get( 'search' );
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

	// Add reminders
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
	 * WikiEdit is a tool for quickly editing content without leaving the page
	 * Documentation at https://www.mediawiki.org/wiki/WikiEdit
	 */
	loadWikiEdit: function () {
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
		mw.config.set( 'wikiedit-page', 'Appropedia:WikiEdit' );

		// Change tag to track edits made with the tool
		mw.config.set( 'wikiedit-tag', 'wikiedit' );

		// Load the latest code directly from the central version at MediaWiki.org
		mw.loader.load( '//www.mediawiki.org/wiki/MediaWiki:WikiEdit.js?action=raw&ctype=text/javascript' );
	},

	checkForTranslation: function () {

		// Only check for translations when viewing content
		var action = mw.config.get( 'wgAction' );
		if ( action !== 'view' ) {
			return;
		}

		// Only check for translations to main, project and help namespaces
		var namespace = mw.config.get( 'wgNamespaceNumber' );
		if ( ! [ 0, 4, 12 ].includes( namespace ) ) {
			return;
		}

		// Check for <font> tags because Google Translate inserts MANY such tags
		var nodes = $( '#mw-content-text font' ).length;
		if ( nodes < 10 ) {
			return;
		}

		// Don't check for translations of translations (check for [[Template:Automatic translation notice]])
		if ( $( '.automatic-translation' ).length ) {
			return;
		}

		// Ignore rare and cryptic 'auto' language
		var translationLanguage = $( 'html' ).attr( 'lang' ).replace( /-.+/, '' ).trim();
		if ( translationLanguage === 'auto' ) {
			return;
		}

		// Don't check for translations to the same language (including language variants like en-GB)
		var contentLanguage = mw.config.get( 'wgPageContentLanguage' );
		if ( contentLanguage === translationLanguage ) {
			return;
		}

		// Check to see if there is a translation already
		var title = mw.config.get( 'wgPageName' );
		new mw.Api().get( {
			'action': 'query',
			'titles': title + '/' + translationLanguage,
			'prop': 'pageprops',
			'formatversion': 2
		} ).done( function ( data ) {

			// If there is a translation already, check if the current one is better
			var page = data.query.pages[0];
			if ( 'pageprops' in page && 'nodes' in page.pageprops && nodes < page.pageprops.nodes ) {
				return;
			}

			// If we reach this point, stop checking and just save the translation
			clearInterval( Appropedia.interval );
			Appropedia.saveTranslation();
		} );
	},

	saveTranslation: function () {

		// Fix old Hebrew language code
		var translationLanguage = $( 'html' ).attr( 'lang' ).replace( /-.+/, '' );
		if ( translationLanguage === 'iw' ) {
			translationLanguage = 'he';
		}

		// If the user reverts to the original language
		var contentLanguage = mw.config.get( 'wgPageContentLanguage' );
		if ( contentLanguage === translationLanguage ) {
			return;
		}

		// Get translation
		var title = $( '#firstHeading' ).text();
		var $content = $( '#mw-content-text > .mw-parser-output' ).clone();
		$content.find( '.mw-editsection' ).remove().end(); // Remove edit section links

		// Get translated HTML and minify it (because it's not meant to be edited)
		var html = $content.html();
		html = html.replace( /\n\s+|\n/g, '' );

		// Count nodes
		var nodes = $content.find( 'font' ).length;

		// Get categories
		var categories = mw.config.get( 'wgCategories' );

		// Build wikitext
		var wikitext = '{{Automatic translation notice';
		wikitext += '\n| title = ' + title;
		wikitext += '\n| revision = ' + mw.config.get( 'wgCurRevisionId' );
		wikitext += '\n| nodes = ' + nodes;
		wikitext += '\n}}';
		wikitext += '\n\n<html>' + html + '</html>\n';
		for ( var category of categories ) {
			wikitext += '\n[[Category:' + category + ']]';
		}

		// Create or update translation subpage
		// using a bot account
		var page = mw.config.get( 'wgPageName' );
		var lang = $( 'html' ).attr( 'lang' ).replace( /-.+/, '' );
		var params = {
			'action': 'edit',
			'title': page + '/' + lang,
			'text': wikitext,
			'summary': 'Automatic translation',
			'bot': true,
		};

		// The following login-logout routine is a carefully choreographed dance
		// because of things like that logging in twice without logging out first
		// causes an error, while logging out twice causes the real user to log out
		// and failing to log in as Bot causes edits on behalf of the real user
		var api = new mw.Api();
		api.login(
			'Bot@Translations',
			'up32smegqeb71s95j3ib51dpv7927fqc'
		).done( function () {
			api.postWithEditToken( params ).done( function () {
				api.postWithToken( 'csrf', { 'action': 'logout' } ).done( Appropedia.saveTranslation );
			} );
		} ).fail( function () {
			api.postWithToken( 'csrf', { 'action': 'logout' } ).done( Appropedia.saveTranslation );
		} );
	},

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
