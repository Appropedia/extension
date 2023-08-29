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
		return false; // Prevent other extensions (like MobileFrontend) from adding more links
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
		self::addTranslateButton( $skinTemplate, $links );
		self::addPrintButton( $skinTemplate, $links );
		self::addReadAloudButton( $skinTemplate, $links );
		//self::addShareButton( $skinTemplate, $links );
		self::moveHistoryButton( $skinTemplate, $links );
		self::addAdminMenu( $skinTemplate, $links );
	}

	/**
	 * Add a button to print/download the current page
	 */
	private static function addTranslateButton( SkinTemplate $skinTemplate, array &$links ) {
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
		$namespace = $title->getNamespace();
		if ( ! in_array( $namespace, [ NS_MAIN, NS_PROJECT, NS_HELP ] ) ) {
			return;
		}
		$link = [
			'id' => 'ca-translate',
			'href' => '#',
			'text' => wfMessage( 'appropedia-translate' )->plain(),
			'icon' => 'language'
		];
		$links['views']['translate'] = $link;
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
	 * Add a button to read the current page aloud
	 */
	private static function addReadAloudButton( SkinTemplate $skinTemplate, array &$links ) {
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
		$namespace = $title->getNamespace();
		if ( ! in_array( $namespace, [ NS_MAIN, NS_USER, NS_PROJECT, NS_HELP ] ) ) {
			return;
		}
		$readAloud = [
			'id' => 'ca-read-aloud',
			'href' => '#',
			'text' => wfMessage( 'appropedia-read-aloud' )->plain(),
			'icon' => 'play'
		];
		$pauseReading = [
			'id' => 'ca-pause-reading',
			'href' => '#',
			'text' => wfMessage( 'appropedia-pause-reading' )->plain(),
			'icon' => 'pause'
		];
		$links['views']['read-aloud'] = $readAloud;
		$links['views']['pause-reading'] = $pauseReading;
	}

	/**
	 * Add a button to share the current page
	 * @todo Move to a separate extension?
	 */
	private static function addShareButton( SkinTemplate $skinTemplate, array &$links ) {
		$skin = $skinTemplate->getSkin();
		$title = $skin->getTitle();
		if ( ! $title->isContentPage() ) {
			return;
		}
		if ( ! $title->exists() ) {
			return;
		}
		$context = $skin->getContext();
		$action = Action::getActionName( $context );
		if ( $action !== 'view' ) {
			return;
		}
		$link = [
			'id' => 'ca-share',
			'href' => '#',
			'text' => wfMessage( 'appropedia-share' )->plain(),
			'icon' => 'heart'
		];
		$links['views']['share'] = $link;
	}

	/**
	 * Move the history button to the More menu
	 */
	private static function moveHistoryButton( SkinTemplate $skinTemplate, array &$links ) {
		if ( array_key_exists( 'history', $links['views'] ) ) {
			$history = $links['views']['history'];
			unset( $links['views']['history'] );
			$links['actions'] = array_merge( [ 'history' => $history ], $links['actions'] );
		}
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