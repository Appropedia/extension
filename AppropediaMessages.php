<?php

/**
 * This class localizes various interface messages
 *
 * We use this class rather than editing the MediaWiki namespace
 * because editing the MediaWiki namespace only changes one language at a time,
 * whereas here it changes all languages at once
 * and also sends the messages for translation to translatewiki.net
 *
 * @note When calling wfMessage, we specify the language code to prevent errors like T302754
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

			// Unwanted stuff is generally hidden via CSS
			// but we hide these messages by making them empty
			// because there's no easy way to target them via CSS
			// or some other technical reason
			case 'newarticletext': // Useless and confusing message when creating a new page
			case 'createacct-username-help': // Special:CreateAccount
			case 'createacct-useuniquepass': // Special:CreateAccount
			case 'prefs-help-realname': // Special:CreateAccount and Special:Preferences
			case 'privacy': // Footer, see AppropediaNavigation::onSkinAddFooterLinks
			case 'disclaimers': // Footer, see AppropediaNavigation::onSkinAddFooterLinks
			case 'lastmodifiedat': // Footer, see AppropediaNavigation::onSkinAddFooterLinks
			case 'upload-form-label-not-own-work-local-generic-local': // Upload dialog in Extension:VisualEditor
				$message = '';
				break;

			// Override messages
			case 'pagetitle':
				$message = wfMessage( 'appropedia-page-title' )->inLanguage( $code )->text();
				break;

			case 'copyrightwarning':
				$message = wfMessage( 'appropedia-page-edit-warning' )->inLanguage( $code )->text();
				break;

			case 'anoneditwarning':
				$message = wfMessage( 'appropedia-anon-edit-warning' )->inLanguage( $code )->text();
				break;

			case 'editnotice-2':
				$context = RequestContext::getMain();
				$title = $context->getTitle();
				if ( $title->isSubpage() ) {
					break;
				}
				$root = $title->getRootTitle();
				$user = $context->getUser()->getUserPage();
				if ( $root->equals( $user ) ) {
					break;
				}
				$link = $title->getTalkPage()->getFullURL( [ 'action' => 'edit', 'section' => 'new' ] );
				$message = wfMessage( 'appropedia-user-edit-warning', $link )->inLanguage( $code )->text();
				break;

			case 'editnotice-8':
				$page = 'Appropedia:UI'; // @todo Should probably be defined elsewhere
				$message = wfMessage( 'appropedia-interface-edit-warning', $page )->inLanguage( $code )->text();
				break;

			case 'editnotice-10':
				$page = 'Appropedia:Templates'; // @todo Should probably be defined elsewhere
				$message = wfMessage( 'appropedia-template-edit-warning', $page )->inLanguage( $code )->text();
				break;

			case 'categorytree-member-num':
				$message = "($4)";
				break;

			case 'noarticletext':
				$context = RequestContext::getMain();
				$title = $context->getTitle();
				$namespace = $title->getNamespace();
				$action = in_array( $namespace, [ 0, 2, 4, 12 ] ) ? 'veaction' : 'action';
				$preload = $namespace === 2 && !$title->isSubpage() ? 'Preload:User' : null;
				$link = $title->getFullURL( [ $action => 'edit', 'preload' => $preload ] );
				$text = wfMessage( 'appropedia-create-page' )->inLanguage( $code )->text();
				$message = '[' . $link . '<span class="mw-ui-button mw-ui-progressive">' . $text . '</span>]';
				break;

			case 'welcomecreation-msg':
				$message = wfMessage( 'appropedia-account-created' )->inLanguage( $code )->text();
				$context = RequestContext::getMain();
				$link = $context->getUser()->getUserPage()->getFullURL( [ 'veaction' => 'edit', 'preload' => 'Preload:User' ] );
				$text = wfMessage( 'appropedia-create-user-page' )->inLanguage( $code )->text();
				$message .= "\n\n[" . $link . '<span class="mw-ui-button mw-ui-progressive">' . $text . '</span>]';
				break;

			// Special:UploadWizard
			case 'mwe-upwiz-add-file-0-free':
				$message = wfMessage( 'appropedia-select-files' )->inLanguage( $code )->text();
				break;

			// Upload dialog in Extension:VisualEditor
			case 'upload-form-label-not-own-work-message-generic-local':
				$page = 'Special:UploadWizard';
				$message = wfMessage( 'appropedia-not-own-work', $page )->inLanguage( $code )->text();
				break;
		}
	}
}