# Extension:Appropedia

Extension:Appropedia contains all PHP code specific to Appropedia.

Many features that were developed specifically for Appropedia have been turned into standalone extensions. However, some features are so specific that cannot be turned into extensions, so we clump them all together in this extension:

* [AppropediaCategories](AppropediaCategories.php) - Handles automatic categorization for [Appropedia's admin panel](https://www.appropedia.org/Appropedia:Admin_panel)
* [AppropediaMessages](AppropediaMessages.php) - Handles custom interface messages, most of which are localized from [Appropedia's project at TranslateWiki](https://translatewiki.net/wiki/Translating:Appropedia)
* [AppropediaNavigation](AppropediaNavigation.php) - Customizes Appropedia's sidebar and footer
* [AppropediaSearch](AppropediaSearch.php) - Customizes Appropedia's UX/UI at [Special:Search](https://www.appropedia.org/Special:Search)
* [AppropediaWikitext](AppropediaWikitext.php) - Forces Appropedia's wikitext conventions after every edit (like adding {{[Page data](https://www.appropedia.org/Template:Page_data)}} to content pages)

This extension also contains Appropedia's custom Lua library, which exposes some data to [Appropedia's custom Lua module](https://www.appropedia.org/Module:Appropedia):

* emailDomain - Returns the email domain of a given user
* fileUses - Returns the number of uses of a given file
* pageCategories - Returns the categories of a given page
* pageExists - Checks wether a given page exists

It also contains a few scripts that output stuff in formats not supported by MediaWiki's API. These scripts are meant to be public and run from the browser.

* [generatePDF](scripts/generatePDF.php) - Generates a PDF containing a specified set of pages. It uses the [wkhtmltopdf](https://wkhtmltopdf.org/usage/wkhtmltopdf.txt) library to generate the PDF.
* [generateOpenKnowHowManifest](scripts/generateOpenKnowHowManifest.php) - Generates a YAML file containing the Open Know How Manifest for a given project. The Open Know How schema specification can be found [here](https://github.com/iop-alliance/OpenKnowHow/blob/master/src/schema/okh.schema.json). It uses [EasyWiki](https://github.com/Sophivorus/EasyWiki) to interact with the Appropedia Action API.

Finally, it contains a few custom maintenance scripts:

* [addLicense](maintenance/addLicense.php) - Adds a license to {{Page data}} according to the year when the page was created
* [deleteBrokenRedirects](maintenance/deleteBrokenRedirects.php) - Deletes all [broken redirects](https://www.appropedia.org/Special:BrokenRedirects)
* [deleteDuplicateFiles](maintenance/deleteDuplicateFiles.php) - Deletes all [duplicate files](https://www.appropedia.org/Special:ListDuplicatedFiles)
* [fixWikitext](maintenance/fixWikitext.php) - Forces Appropedia's wikitext conventions on all pages
* [generateKiwixList](maintenance/generateKiwixList.php) - Generates [kiwix.tsv](https://www.appropedia.org/kiwix.tsv) for use by the [Kiwix scraper](https://farm.openzim.org/recipes/appropedia_en_all/config).
* [generateOpenKnowHowManifests](maintenance/generateOpenKnowHowManifests.php) - Generates an Open Know How Manifest for each project in Appropedia as well as [an index](https://www.appropedia.org/manifests/list.json) for use by scrapers
* [removeDisplayTitle](maintenance/removeDisplayTitle.php) - Remove the "title" parameter from {{Page data}} when it is identical to the real title of the page