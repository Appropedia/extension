<?php

use MediaWiki\MediaWikiServices;

/**
 * This class customizes the various navigation menus
 */
class AppropediaNavigation {

	public static function onSkinTemplateNavigationUniversal( SkinTemplate $skinTemplate, array &$links ) {
		$skin = $skinTemplate->getSkin();
		$title = $skin->getTitle();
		$sidebar = $skin->buildSidebar();
		$toolbox = $sidebar['TOOLBOX'];

		// Move the Print button to the views, give it an icon and change the text
		if ( $title->exists() && $title->isContentPage() && array_key_exists( 'print', $toolbox ) ) {
			$print = $toolbox['print'];
			$print['text'] = wfMessage( 'appropedia-download-pdf' )->plain();
			$print['icon'] = 'printer';
			$links['views']['print'] = $print;
		}

		// Move the Email button to the views and give it an icon
		if ( array_key_exists( 'emailuser', $toolbox ) ) {
			$email = $toolbox['emailuser'];
			$email['icon'] = 'message';
			$links['views']['email'] = $email;
		}

		// Move the History button out of the views
		if ( array_key_exists( 'history', $links['views'] ) ) {
			$history = $links['views']['history'];
			unset( $links['views']['history'] );
			$links['actions'] = [ 'history' => $history ] + $links['actions'];
		}

		// Give an icon to the button of the Extension:ReadAloud
		if ( array_key_exists( 'read-aloud', $links['views'] ) ) {
			$links['views']['read-aloud']['icon'] = 'play';
			$links['views']['pause-reading']['icon'] = 'pause';
		}

		// Give an icon to the button of the Extension:GoogleTranslate
		if ( array_key_exists( 'google-translate', $links['views'] ) ) {
			$links['views']['google-translate']['icon'] = 'language';
		}

		// Add a link to the admin panel to the user menu of admins
		$user = $skinTemplate->getUser();
		$groups = MediaWikiServices::getInstance()->getUserGroupManager()->getUserGroups( $user );
		if ( in_array( 'sysop', $groups ) ) {
			$link = [
				'href' => '/Appropedia:Admin_panel',
				'text' => wfMessage( 'appropedia-admin-panel' )->text()
			];
			array_splice( $links['user-menu'], 2, 0, [ $link ] );
		}
	}

	/**
	 * Remove global tools from the toolbox because it should only refer to the current page
	 */
	public static function onSidebarBeforeOutput( Skin $skin, &$sidebar ) {
		unset( $sidebar['TOOLBOX']['upload'] );
		unset( $sidebar['TOOLBOX']['specialpages'] );
	}

	/**
	 * Customize the footer
	 *
	 * This hook cannot remove existing links. We remove links by emptying
	 * the associated message keys via AppropediaMessages.php
	 */
	public static function onSkinAddFooterLinks( Skin $skin, string $key, array &$footerlinks ) {
		if ( $key === 'places' ) {
			$footerlinks['policies'] = Html::rawElement( 'a', [ 'href' => '/Appropedia:Policies' ], $skin->msg( 'appropedia-policies' )->text() );
			$footerlinks['contact'] = Html::rawElement( 'a', [ 'href' => '/Appropedia:Contact' ], $skin->msg( 'appropedia-contact' )->text() );
		};
	}
}