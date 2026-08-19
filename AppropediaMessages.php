<?php

/**
 * This class localizes various interface messages
 *
 * We use this class rather than editing the MediaWiki namespace
 * because editing the MediaWiki namespace only changes one language at a time,
 * whereas here it changes all languages at once
 * and also sends the messages for translation to translatewiki.net
 *
 * Note! When calling wfMessage, we specify the language code to prevent T302754
 */
class AppropediaMessages {

	public static function onMessagesPreLoad( $title, &$message, $code ) {
		if ( $code === 'qqx' ) {
			return;
		}

		$parts = explode( '/', $title );
		$key = $parts[0];
		$key = strtolower( $key );
		switch ( $key ) {

			case 'pagetitle':
				$message = wfMessage( 'appropedia-page-title' )->inLanguage( $code );
				break;

			// Remove unwanted elements from the footer
			// @see https://www.mediawiki.org/wiki/Project:Support_desk#Remove_footer_links
			case 'privacy':
			case 'disclaimers':
			case 'lastmodifiedat':
			case 'retrievedfrom': // Print mode
				$message = '';
				break;

			// Hide these messages by making them empty
			// because there's no easy way to target them via CSS
			case 'newarticletext': // Useless and confusing message when creating a new page
			case 'createacct-username-help': // Special:CreateAccount
			case 'createacct-useuniquepass': // Special:CreateAccount
			case 'prefs-help-realname': // Special:CreateAccount and Special:Preferences
			case 'upload-form-label-not-own-work-local-generic-local': // Upload dialog in Extension:VisualEditor
				$message = '';
				break;

			case 'copyrightwarning':
				$message = wfMessage( 'appropedia-page-edit-warning' )->inLanguage( $code );
				break;

			case 'anoneditwarning':
				$message = wfMessage( 'appropedia-anon-edit-warning' )->inLanguage( $code );
				break;

			case 'editnotice-2':
				$context = RequestContext::getMain();
				$title = $context->getTitle();
				$root = $title->getRootTitle();
				$user = $context->getUser()->getUserPage();
				if ( $root->equals( $user ) ) {
					break;
				}
				$link = $root->getTalkPage()->getFullURL( [ 'action' => 'edit', 'section' => 'new' ] );
				$message = wfMessage( 'appropedia-user-edit-warning', $link )->inLanguage( $code )->text();
				break;

			case 'noarticletext':
				$context = RequestContext::getMain();
				$title = $context->getTitle();
				$namespace = $title->getNamespace();
				$action = in_array( $namespace, [ 0, 2, 4, 12 ] ) ? 'veaction' : 'action';
				$preload = $namespace === 2 && !$title->isSubpage() ? 'Preload:User' : null;
				$link = $title->getFullURL( [ $action => 'edit', 'preload' => $preload ] );
				$text = wfMessage( 'appropedia-create-page' )->inLanguage( $code );
				$button = '<span class="cdx-button cdx-button--fake-button--enabled cdx-button--action-progressive cdx-button--weight-primary">' . $text . '</span>';
				$message = "[$link $button]";
				break;

			case 'welcomecreation-msg':
				$context = RequestContext::getMain();
				$link = $context->getUser()->getUserPage()->getFullURL( [ 'veaction' => 'edit', 'preload' => 'Preload:User' ] );
				$text = wfMessage( 'appropedia-create-user-page' )->inLanguage( $code );
				$button = '<span class="cdx-button cdx-button--fake-button--enabled cdx-button--action-progressive cdx-button--weight-primary">' . $text . '</span>';
				$message = "[$link $button]";
				break;

			// Special:UploadWizard
			case 'mwe-upwiz-add-file-0-free':
				$message = wfMessage( 'appropedia-select-files' )->inLanguage( $code );
				break;

			// Upload dialog in Extension:VisualEditor
			case 'upload-form-label-not-own-work-message-generic-local':
				$page = 'Special:UploadWizard';
				$message = wfMessage( 'appropedia-not-own-work', $page )->inLanguage( $code )->text();
				break;

			// Extension:CategoryTree
			case 'categorytree-member-num':
				$message = "($4)";
				break;
		}
	}
}