# Extension:Appropedia

Extension:Appropedia contains all PHP code specific to Appropedia.

Many features that were developed specifically for Appropedia have been turned into standalone extensions:

* [Extension:CategoryLockdown](https://www.mediawiki.org/wiki/Extension:CategoryLockdown)
* [Extension:PageAuthors](https://www.mediawiki.org/wiki/Extension:PageAuthors)
* [Extension:StandardWikitext](https://www.mediawiki.org/wiki/Extension:StandardWikitext)
* [Extension:SearchParserFunction](https://www.mediawiki.org/wiki/Extension:SearchParserFunction)
* [Extension:SearchThumbs](https://www.mediawiki.org/wiki/Extension:SearchThumbs)
* [Extension:SemanticRESTAPI](https://www.mediawiki.org/wiki/Extension:SemanticRESTAPI)
* [Extension:InterwikiExtracts](https://www.mediawiki.org/wiki/Extension:InterwikiExtracts)
* [Extension:Analytics](https://www.mediawiki.org/wiki/Extension:Analytics)
* [Extension:CloudflarePurge](https://www.mediawiki.org/wiki/Extension:CloudflarePurge)
* [Extension:WikiVideos](https://www.mediawiki.org/wiki/Extension:WikiVideos)
* [Extension:ReadAloud](https://www.mediawiki.org/wiki/Extension:ReadAloud)
* [Extension:GoogleTranslate](https://www.mediawiki.org/wiki/Extension:GoogleTranslate)
* [Extension:HTMLPurifier](https://www.mediawiki.org/wiki/Extension:HTMLPurifier)

However, a few features are so specific that can't be turned into extensions, so we clump them all together in Extension:Appropedia:

* AppropediaCategories - Handles automatic categorization of pages for maintenance purposes
* AppropediaMessages - Handles custom interface messages, most of which are localized via https://translatewiki.net
* AppropediaNavigation - Customizes Appropedia's sidebar and footer
* AppropediaSearch - Customizes Appropedia's search experience at Special:Search
* AppropediaWikitext - Forces Appropedia's wikitext conventions (like adding {{[Page data](https://www.appropedia.org/Template:Page_data)}} to all content pages) after every edit

This extension also contains Appropedia's custom Lua library, which exposes some data to [Appropedia's custom Lua module](https://www.appropedia.org/Module:Appropedia):

* emailDomain - Returns the email domain of a given user
* fileUses - Returns the number of uses of a given fileUses
* pageCategories - Returns the categories of a given page
* pageExists - Checks wether a given page exists

Finally, this extension contains a few custom maintenance scripts:

* deleteBrokenRedirects.php - Deletes all [broken redirects](https://www.appropedia.org/Special:BrokenRedirects)
* deleteDuplicateFiles.php - Deletes all [duplicate files](https://www.appropedia.org/Special:ListDuplicatedFiles)
* fixWikitext.php - Forces Appropedia's wikitext conventions on all pages
* generateKiwixList - Generates https://www.appropedia.org/kiwix.tsv for use by the [Kiwix scraper](https://farm.openzim.org/recipes/appropedia_en_all/config).
* generateOpenKnowHowManifests.php - Generates an Open Know How Manifest for each project in Appropedia as well as an index at https://www.appropedia.org/manifests/list.json for use by scrapers