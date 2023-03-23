# Appropedia extension

Appropedia extension for MediaWiki.

This extension contains all PHP, JS and CSS specific to Appropedia. We used to have it scattered in our MediaWiki:Common.js, MediaWiki:Common.css and LocalSettings.php, but we now have it all here for several reasons:

* Simplifies testing and maintenance
* Simplifies documentation and discovery of features and changes
* Simplifies contributions by new developers
* Improves performance by allowing us to make use of [MediaWiki's resource loader](https://www.mediawiki.org/wiki/ResourceLoader)
* Simplifies development by allowing us to make use of code editors and technologies like LESS

Many features that were developed specifically for Appropedia have been turned into standalone extensions, such as [CategoryLockdown](https://www.mediawiki.org/wiki/Extension:CategoryLockdown), [PageAuthors](https://www.mediawiki.org/wiki/Extension:PageAuthors), [StandardWikitext](https://www.mediawiki.org/wiki/Extension:StandardWikitext) and [SearchParserFunction](https://www.mediawiki.org/wiki/Extension:SearchParserFunction), but others are so specific that can't be turned into extensions, so we clumped them all here.