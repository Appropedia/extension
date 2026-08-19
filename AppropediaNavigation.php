<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

/**
 * This class customizes the various navigation menus
 */
class AppropediaNavigation {

	public static function onSkinTemplateNavigationUniversal( SkinTemplate $skinTemplate, array &$links ) {

		// Add a link to the admin panel to the user menu of admins
		$user = $skinTemplate->getUser();
		$services = MediaWikiServices::getInstance();
		$groupManager = $services->getUserGroupManager();
		$groups = $groupManager->getUserGroups( $user );
		if ( in_array( 'sysop', $groups ) ) {
			$link = [
				'href' => '/Appropedia:Admin_panel',
				'text' => $skinTemplate->msg( 'appropedia-admin-panel' ),
				'icon' => 'unStar'
			];
			array_splice( $links['user-menu'], 2, 0, [ $link ] );
		}
	}

	/**
	 * Remove upload tool from the toolbox because tools should only refer to the current page
	 */
	public static function onSidebarBeforeOutput( Skin $skin, &$sidebar ) {
		unset( $sidebar['TOOLBOX']['upload'] );
	}

	/**
	 * Add links to the footer
	 */
	public static function onSkinAddFooterLinks( Skin $skin, string $key, array &$footerlinks ) {
		if ( $key === 'places' ) {
			$services = MediaWikiServices::getInstance();
			$linkRenderer = $services->getLinkRenderer();

			$policiesTitle = Title::newFromText( 'Policies', NS_PROJECT );
			$policiesText = $skin->msg( 'appropedia-policies' );
			$footerlinks['policies'] = $linkRenderer->makePreloadedLink( $policiesTitle, $policiesText );

			$contactTitle = Title::newFromText( 'Contact', NS_PROJECT );
			$contactText = $skin->msg( 'appropedia-contact' );
			$footerlinks['contact'] = $linkRenderer->makePreloadedLink( $contactTitle, $contactText );
		}
	}
}