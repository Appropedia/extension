<?php

class AppropediaMessages {

	/**
	 * Customize interface messages
	 *
	 * When Appropedia updates to MediaWiki 1.41+
	 * some of this code should migrate to the MessageCacheFetchOverrides hook instead
	 * see https://www.mediawiki.org/wiki/Manual:Hooks/MessageCacheFetchOverrides
	 */
	public static function onMessagesPreLoad( $title, &$message, $code ) {
		if ( $code === 'qqx' ) {
			return;
		}

		$parts = explode( '/', $title );
		$key = $parts[0];
		$key = strtolower( $key );
		switch ( $key ) {

			// Unwanted stuff is generally hidden via CSS
			// but the following messages are hidden by making them empty
			// because there's no easy way to target them via CSS
			// or some other technical reason
			case 'privacy': // Part of the footer, see onSkinAddFooterLinks below
			case 'disclaimers': // Part of the footer, see onSkinAddFooterLinks below
			case 'histlegend': // @todo Hide via CSS
			case 'newarticletext':
			case 'createacct-username-help': // Special:CreateAccount
			case 'createacct-useuniquepass': // Special:CreateAccount
			case 'prefs-help-realname': // Special:CreateAccount and Special:Preferences
			case 'hcaptcha-createaccount': // Extension:ConfirmEdit
			case 'hcaptcha-edit': // Extension:ConfirmEdit
			case 'upload-form-label-not-own-work-local-generic-local': // Upload dialog of Extension:VisualEditor
				$message = '';
				break;

			/**
			 * Override messages
			 */

			case 'copyrightwarning':
				$message = wfMessage( "appropedia-page-edit-warning" )->text();
				break;

			case 'anoneditwarning':
				$message = wfMessage( "appropedia-anon-edit-warning" )->text();
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
				$message = wfMessage( 'appropedia-user-edit-warning', $link )->text();
				break;

			case 'editnotice-8':
				$page = 'Appropedia:UI'; // @todo Should probably be defined elsewhere
				$message = wfMessage( 'appropedia-interface-edit-warning', $page )->text();
				break;

			case 'editnotice-10':
				$page = 'Appropedia:Templates'; // @todo Should probably be defined elsewhere
				$message = wfMessage( 'appropedia-template-edit-warning', $page )->text();
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
				$text = wfMessage( 'appropedia-create-page' )->text();
				$message = '[' . $link . '<span class="mw-ui-button mw-ui-progressive">' . $text . '</span>]';
				break;

			case 'welcomecreation-msg':
				$message = wfMessage( 'appropedia-account-created' )->text();
				$context = RequestContext::getMain();
				$link = $context->getUser()->getUserPage()->getFullURL( [ 'veaction' => 'edit', 'preload' => 'Preload:User' ] );
				$text = wfMessage( 'appropedia-create-user-page' )->text();
				$message .= "\n\n[" . $link . '<span class="mw-ui-button mw-ui-progressive">' . $text . '</span>]';
				break;

			/**
			 * Override extension and skin messages
			 * These require setting the language explictly
			 */
			case 'mwe-upwiz-add-file-0-free':
				$message = wfMessage( 'appropedia-select-files' )->inLanguage( $code )->text();
				break;

			case 'upload-form-label-not-own-work-message-generic-local':
				$page = 'Special:UploadWizard';
				$message = wfMessage( 'appropedia-not-own-work', $page )->inLanguage( $code )->text();
				break;

			case 'poncho-print':
				$message = wfMessage( 'appropedia-download-pdf' )->inLanguage( $code )->text();
				break;
		}
	}
}