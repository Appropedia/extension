window.Appropedia = {

	init: function () {

		// Enable popups on the user, project and help namespaces
		mw.config.set( 'wgContentNamespaces', [ 0, 2, 4, 12 ] );

		// Update the search query when a search filter changes
		$( '.mw-search-profile-form select' ).change( Appropedia.updateSearchQuery );

		// Fix the width of the search filters
		$( '.mw-search-profile-form select' ).each( Appropedia.updateSearchFilterWidth );

		// Save quiz scores
		$( '.quiz .score' ).each( Appropedia.saveQuizScore );

		// Check for automatic translations every 5 seconds
		Appropedia.interval = setInterval( Appropedia.checkForTranslation, 5000 );

		// Track events we're interested in
		Appropedia.trackEvents();

		// Add reminder
		Appropedia.addReminder();
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
	},

	// Add reminders
	addReminder: function () {
		var text = mw.cookie.get( 'TemplateReminderText' );
		if ( !text ) {
			return;
		}
		var category = mw.cookie.get( 'TemplateReminderCategory' );
		if ( category && !mw.config.get( 'wgCategories' ).includes( category ) ) {
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

	checkForTranslation: function () {

		// Only save translations when viewing content
		var action = mw.config.get( 'wgAction' );
		if ( action !== 'view' ) {
			return;
		}

		// Only save translations to main, project and help namespaces
		var namespace = mw.config.get( 'wgNamespaceNumber' );
		if ( ! [ 0, 4, 12 ].includes( namespace ) ) {
			return;
		}

		// Check for <font> tags because Google Translate inserts MANY such tags
		if ( $( '#mw-content-text font' ).length < 10 ) {
			return;
		}

		// Don't save translations of translations (check for [[Template:Automatic translation notice]])
		if ( $( '.automatic-translation' ).length ) {
			return;
		}

		// Don't save translations to the same language (including language variants like en-GB)
		var translationLanguage = $( 'html' ).attr( 'lang' ).replace( /-.+/, '' );
		var contentLanguage = mw.config.get( 'wgPageContentLanguage' );
		if ( contentLanguage === translationLanguage ) {
			return;
		}

		// Stop checking and save the translation
		clearInterval( Appropedia.interval );
		Appropedia.saveTranslation();
	},

	saveTranslation: function () {

		// If the user reverts to the original language
		var translationLanguage = $( 'html' ).attr( 'lang' ).replace( /-.+/, '' );
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

		// Build wikitext
		var wikitext = '{{Automatic translation notice';
		wikitext += '\n| title = ' + title;
		wikitext += '\n| revision = ' + mw.config.get( 'wgCurRevisionId' );
		wikitext += '\n| nodes = ' + nodes;
		wikitext += '\n}}';
		wikitext += '\n\n<html>' + html + '</html>';

		// Create or update translation subpage
		// using a bot account
		var page = mw.config.get( 'wgPageName' );
		var lang = $( 'html' ).attr( 'lang' ).replace( /-.+/, '' );
		params = {
			'action': 'edit',
			'title': page + '/' + lang,
			'text': wikitext,
			'summary': 'Automatic translation',
			'bot': true,
		};

		// The following login-logout routine is a carefully choreographed dance
		// because of things like that logging in twice without logging out first
		// causes an error, while logging out twice causes the real user to log out
		// and failing to log in as Bot causes edits on behalf of the real user, etc
		var api = new mw.Api();
		api.login(
			'Bot@Translations',
			'up32smegqeb71s95j3ib51dpv7927fqc'
		).done( function () {
			api.postWithEditToken( params ).done( function () {
				api.postWithToken( 'csrf', { 'action': 'logout' } ).done( saveTranslation );
			} );
		} ).fail( function () {
			api.postWithToken( 'csrf', { 'action': 'logout' } ).done( saveTranslation );
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
			action: 'parse',
			page: talk,
			prop: 'text',
			formatversion: 2
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
					'Bot@Widgets',
					'bejns8tt24m14r909kcfe4k4aahfn9vp'
				).then( function () {
					return api.postWithEditToken( params );
				} );
			}
		} );
	},

	trackEvents: function () {
		if ( typeof gtag === 'undefined' ) {
			return;
		}

		// Track clicks on "Create page" button
		$( '.template-create .mw-ui-button' ).click( function () {
			gtag( 'event', 'create_page' );
		} );

		// Track clicks on navigation menu
		$( '#poncho-navigation-menu li' ).click( function () {
			var link = $( this ).attr( 'id' );
			gtag( 'event', 'navigation', { 'navigation_link': link } );
		} );

		// Track clicks to search filters
		$( '.mw-search-profile-form select' ).change( function () {
			var filter = $( this ).val();
			gtag( 'event', 'search_filter', { 'search_filter': filter } );
		} );

		// Track clicks on content actions
		$( '#poncho-content-actions > span' ).click( function () {
			var action = $( this ).attr( 'id' );
			gtag( 'event', 'page_actions', { 
				'event_category': 'page_actions',
				'event_label': action,
				'page_action': action 
			} );
		} );

		// Content actions

		// Track clicks on "Visual editor" button
		$( '#ca-ve-edit, .mw-editsection a:nth-child(1)' ).click( function () {
			gtag( 'event', 'page_actions_edit_visual' );
		} );

		// Track clicks on "Source editor" button
		$( '#ca-edit, .mw-editsection a:nth-child(2)' ).click( function () {
			gtag( 'event', 'page_actions_edit_source' );
		} );

		// Track clicks on "Transalte" button
		$( '#poncho-translate-button' ).click( function () {
			gtag( 'event', 'page_actions_translate' );
		} );

		// Track clicks on "Print" button
		$( '#t-print' ).click( function () {
			gtag( 'event', 'page_actions_print' );
		} );

		// Track clicks on "Read aloud" button
		$( '#poncho-read-aloud-button' ).click( function () {
			gtag( 'event', 'page_actions_read_aloud' );
		} );

		// Track clicks on "Share" button
		$( '#poncho-share-button' ).click( function () {
			gtag( 'event', 'page_actions_share' );
		} );

		// Track clicks on "Talk" button
		$( '#ca-talk' ).click( function () {
			gtag( 'event', 'page_actions_talk' );
		} );

		// Track clicks on "Read" button
		$( '#ca-view' ).click( function () {
			gtag( 'event', 'page_actions_read' );
		} );

		// Trach clicks on "History" button
		$( '#ca-history' ).click( function () {
			gtag( 'event', 'page_actions_history' );
		} );

		// Trach clicks on "Delete" button
		$( '#ca-delete' ).click( function () {
			gtag( 'event', 'page_actions_delete' );
		} );

		// Trach clicks on "Move" button
		$( '#ca-move' ).click( function () {
			gtag( 'event', 'page_actions_move' );
		} );

		// Trach clicks on "Watch" button
		$( '#ca-watch' ).click( function () {
			gtag( 'event', 'page_actions_watch' );
		} );

		// Trach clicks on "Purge" button
		$( '#ca-purge' ).click( function () {
			gtag( 'event', 'page_actions_purge' );
		} );

		// Trach clicks on "What links here" button
		$( '#t-whatlinkshere' ).click( function () {
			gtag( 'event', 'page_actions_what_links_here' );
		} );

		// Trach clicks on "Related changes" button
		$( '#t-recentchangeslinked' ).click( function () {
			gtag( 'event', 'page_actions_recent_changes' );
		} );

		// Trach clicks on "Permanent link" button
		$( '#t-permalink' ).click( function () {
			gtag( 'event', 'page_actions_permalink' );
		} );

		// Trach clicks on "Page info" button
		$( '#t-info' ).click( function () {
			gtag( 'event', 'page_actions_page_info' );
		} );

		// Trach clicks on "Browse properties" button
		$( '#t-smwbrowselink' ).click( function () {
			gtag( 'event', 'page_actions_browse_properties' );
		} );

		// Sidebar

		// Track clicks on "Main page" button
		$( '#n-mainpage-description' ).click( function () {
			gtag( 'event', 'sidebar_main_page');
		} );

		// Track clicks on "Categories" button
		$( '#n-categories' ).click( function () {
			gtag( 'event', 'sidebar_categories');
		} );

		// Track clicks on "Map" button
		$( '#n-maps_map' ).click( function () {
			gtag( 'event', 'sidebar_map');
		} );

		// Track clicks on "Random page" button
		$( '#n-randompage' ).click( function () {
			gtag( 'event', 'sidebar_random_page');
		} );

		// Track clicks on "Popular pages" button
		$( '#n-popularpages' ).click( function () {
			gtag( 'event', 'sidebar_popular_page');
		} );

		// Track clicks on "Recent changes" button
		$( '#n-recentchanges' ).click( function () {
			gtag( 'event', 'sidebar_recent_changes');
		} );

		// Track clicks on "New page" button
		$( '#n-newpage' ).click( function () {
			gtag( 'event', 'sidebar_new_page');
		} );

		// Track clicks on "Upload" button
		$( '#n-upload' ).click( function () {
			gtag( 'event', 'sidebar_upload');
		} );

		// Track clicks on "Toolbox" button
		$( '#n-toolbox' ).click( function () {
			gtag( 'event', 'sidebar_toolbox');
		} );

		// Track clicks on "Help" button
		$( '#n-help' ).click( function () {
			gtag( 'event', 'sidebar_help');
		} );

		// Track clicks on "Community portal" button
		$( '#n-portal' ).click( function () {
			gtag( 'event', 'sidebar_portal');
		} );

		// Track clicks on "Special pages" button
		$( '#n-specialpages' ).click( function () {
			gtag( 'event', 'sidebar_special_pages');
		} );

		// Track clicks on "Admin panel" button
		$( '#n-adminpanel' ).click( function () {
			gtag( 'event', 'sidebar_admin_panel');
		} );

		// Main page

		// Track clicks on "New page" button
		$( '.main-page-gallery li:first-child a' ).click( function () {
			gtag( 'event', 'main_page_new_page' );
		} );

		// Track clicks on "Solar cooker" button
		$( '.main-page-gallery li:nth-child(2) a' ).click( function () {
			gtag( 'event', 'main_page_solar_cooker' );
		} );

		// Track clicks on "Photovoltaic system" button
		$( '.main-page-gallery li:nth-child(3) a' ).click( function () {
			gtag( 'event', 'main_page_photovoltaic' );
		} );

		// Track clicks on "Solar hot water" button
		$( '.main-page-gallery li:nth-child(4) a' ).click( function () {
			gtag( 'event', 'main_page_solar_hot_water' );
		} );

		// Track clicks on "Solar still" button
		$( '.main-page-gallery li:nth-child(5) a' ).click( function () {
			gtag( 'event', 'main_page_solar_still' );
		} );

		// Track clicks on "Rainwater system" button
		$( '.main-page-gallery li:nth-child(6) a' ).click( function () {
			gtag( 'event', 'main_page_rainwater_system' );
		} );

		// Track clicks on "Greywater syste" button
		$( '.main-page-gallery li:nth-child(7) a' ).click( function () {
			gtag( 'event', 'main_page_greywater_system' );
		} );

		// Track clicks on "Water quality testing" button
		$( '.main-page-gallery li:nth-child(8) a' ).click( function () {
			gtag( 'event', 'main_page_water_quality_testing' );
		} );

		// Track clicks on "Water filter" button
		$( '.main-page-gallery li:nth-child(9) a' ).click( function () {
			gtag( 'event', 'main_page_water_filter' );
		} );

		// Track clicks on "Water pump" button
		$( '.main-page-gallery li:nth-child(10) a' ).click( function () {
			gtag( 'event', 'main_page_water_pump' );
		} );

		// Track clicks on "Vertical garden" button
		$( '.main-page-gallery li:nth-child(11) a' ).click( function () {
			gtag( 'event', 'main_page_vertical_garden' );
		} );

		// Track clicks on "Living roof" button
		$( '.main-page-gallery li:nth-child(12) a' ).click( function () {
			gtag( 'event', 'main_page_living_roof' );
		} );

		// Track clicks on "Greenhouse" button
		$( '.main-page-gallery li:nth-child(13) a' ).click( function () {
			gtag( 'event', 'main_page_greenhouse' );
		} );

		// Track clicks on "Aquaponic" button
		$( '.main-page-gallery li:nth-child(14) a' ).click( function () {
			gtag( 'event', 'main_page_aquaponic' );
		} );

		// Track clicks on "Compost bin" button
		$( '.main-page-gallery li:nth-child(15) a' ).click( function () {
			gtag( 'event', 'main_page_compost_bin' );
		} );

		// Track clicks on "Natural paint" button
		$( '.main-page-gallery li:nth-child(16) a' ).click( function () {
			gtag( 'event', 'main_page_natural_paint' );
		} );

		// Track clicks on "Composting toilet" button
		$( '.main-page-gallery li:nth-child(17) a' ).click( function () {
			gtag( 'event', 'main_page_composting_toilet' );
		} );

		// Track clicks on "Rocket stove" button
		$( '.main-page-gallery li:nth-child(18) a' ).click( function () {
			gtag( 'event', 'main_page_rocket_stove' );
		} );

		// Track clicks on "Bike trailer" button
		$( '.main-page-gallery li:nth-child(19) a' ).click( function () {
			gtag( 'event', 'main_page_bike_trailer' );
		} );

		// Track clicks on "Pedal power generator" button
		$( '.main-page-gallery li:nth-child(20) a' ).click( function () {
			gtag( 'event', 'main_page_pedal_power_generator' );
		} );

		// Track clicks on "Adobe construction" button
		$( '.main-page-gallery li:nth-child(21) a' ).click( function () {
			gtag( 'event', 'main_page_adobe_construction' );
		} );

		// Track clicks on "Cobb construction" button
		$( '.main-page-gallery li:nth-child(22) a' ).click( function () {
			gtag( 'event', 'main_page_cobb_construction' );
		} );

		// Track clicks on "Bamboo construction" button
		$( '.main-page-gallery li:nth-child(23) a' ).click( function () {
			gtag( 'event', 'main_page_bamboo_construction' );
		} );

		// Track clicks on "Straw bale construction" button
		$( '.main-page-gallery li:nth-child(24) a' ).click( function () {
			gtag( 'event', 'main_page_straw_bale_construction' );
		} );

		// Track clicks on "Ecoladrillo" button
		$( '.main-page-gallery li:nth-child(25) a' ).click( function () {
			gtag( 'event', 'main_page_straw_ecoladrillo' );
		} );
	}
};

$( Appropedia.init );