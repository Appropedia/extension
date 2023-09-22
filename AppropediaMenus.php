<?php

/**
 * This class customizes the various menus
 */
class AppropediaMenus {

	/**
	 * Customize the footer
	 * @note Default links are removed via AppropediaMessages
	 */
	public static function onSkinAddFooterLinks( Skin $skin, string $key, array &$footerlinks ) {
		if ( $key === 'places' ) {
			$footerlinks['policies'] = $skin->footerLink( 'appropedia-policies', 'policiespage' );
			$footerlinks['contact'] = $skin->footerLink( 'appropedia-contact', 'contactpage' );
		};
	}

	/**
	 * Customize the sidebar
	 */
	public static function onSidebarBeforeOutput( Skin $skin, &$sidebar ) {
		unset( $sidebar['TOOLBOX']['print'] );

		// Remove global tools because this menu should refer only to the current page
		unset( $sidebar['TOOLBOX']['upload'] );
		unset( $sidebar['TOOLBOX']['specialpages'] );
	}

	/**
	 * Customize the navigation menus
	 */
	public static function onSkinTemplateNavigationUniversal( SkinTemplate $skinTemplate, array &$links ) {
		self::customizeButtons( $skinTemplate, $links );
		self::addPrintButton( $skinTemplate, $links );
		self::addAdminMenu( $skinTemplate, $links );
	}

	/**
	 * Customize the buttons
	 */
	private static function customizeButtons( SkinTemplate $skinTemplate, array &$links ) {
		// Move the History button out of the views
		if ( array_key_exists( 'history', $links['views'] ) ) {
			$history = $links['views']['history'];
			unset( $links['views']['history'] );
			$links['actions'] = array_merge( [ 'history' => $history ], $links['actions'] );
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
	}

	/**
	 * Add a button to print/download the current page
	 */
	private static function addPrintButton( SkinTemplate $skinTemplate, array &$links ) {
		$skin = $skinTemplate->getSkin();
		$title = $skin->getTitle();
		if ( ! $title->exists() ) {
			return;
		}
		$context = $skin->getContext();
		$action = Action::getActionName( $context );
		if ( $action !== 'view' ) {
			return;
		}
		if ( ! $title->isContentPage() ) {
			return;
		}
		$link = [
			'id' => 'ca-print',
			'href' => '#',
			'text' => wfMessage( 'appropedia-download-pdf' )->plain(),
			'icon' => 'printer'
		];
		$links['views']['print'] = $link;
	}

	/**
	 * Add link to [[Appropedia:Admin panel]]
	 */
	private static function addAdminMenu( SkinTemplate $skinTemplate, array &$links ) {
		$user = $skinTemplate->getUser();
		$groups = $user->getGroups();
		if ( in_array( 'sysop', $groups ) ) {
			$link = [
				'href' => '/Appropedia:Admin_panel',
				'text' => wfMessage( 'appropedia-admin-panel' )->text()
			];
			array_splice( $links['user-menu'], 2, 0, [ $link ] );
		}
	}
}