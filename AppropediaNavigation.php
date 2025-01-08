<?php

use MediaWiki\MediaWikiServices;

/**
 * This class customizes the various navigation menus
 */
class AppropediaNavigation {

	public static function onSkinTemplateNavigationUniversal( SkinTemplate $skinTemplate, array &$links ) {

		// Add a link to the admin panel to the user menu of admins
		$user = $skinTemplate->getUser();
		$groups = MediaWikiServices::getInstance()->getUserGroupManager()->getUserGroups( $user );
		if ( in_array( 'sysop', $groups ) ) {
			$link = [
				'href' => '/Appropedia:Admin_panel',
				'text' => wfMessage( 'appropedia-admin-panel' )->text(),
				'icon' => 'unStar'
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