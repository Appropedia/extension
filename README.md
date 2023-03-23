# Appropedia extension

Appropedia extension for MediaWiki.

This extension contains all PHP, JavaScript and CSS specific to Appropedia. We used to have it scattered in our MediaWiki:Common.js, MediaWiki:Common.css and LocalSettings.php, but we now have it all here for several reasons:

* Simplifies testing and maintenance
* Simplifies documentation and discovery of features and changes
* Simplifies contributions by new developers
* Simplifies development by allowing us to make use of code editors and LESS
* Improves performance by allowing us to make use of [MediaWiki's Resource Loader](https://www.mediawiki.org/wiki/ResourceLoader)
* Allows us to translate strings via https://translatewiki.net

Many features that were developed specifically for Appropedia have been turned into standalone extensions (such as [CategoryLockdown](https://www.mediawiki.org/wiki/Extension:CategoryLockdown), [PageAuthors](https://www.mediawiki.org/wiki/Extension:PageAuthors), [StandardWikitext](https://www.mediawiki.org/wiki/Extension:StandardWikitext) and [SearchParserFunction](https://www.mediawiki.org/wiki/Extension:SearchParserFunction)) but others are so specific that can't be turned into extensions, so we clumped them all here.

Note that template-specific JavaScript and CSS are still hosted at Appropedia (see [Category:Template script pages](https://www.appropedia.org/Category:Template_script_pages) and [Category:Template style pages](https://www.appropedia.org/Category:Template_style_pages)) and should remain there. This is because such scripts and styles should only be loaded on pages where the templates are used, while code in the Appropedia extension gets loaded on all pages.