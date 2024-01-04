<?php

use MediaWiki\MediaWikiServices;

class AppropediaLua extends Scribunto_LuaLibraryBase {

	public static function onScribuntoExternalLibraries( string $engine, array &$extraLibraries ) {
		$extraLibraries['appropedia'] = self::class;
	}

	public function register() {
		$this->getEngine()->registerInterface( __DIR__ . '/AppropediaLua.lua', [
			'emailDomain' => [ $this, 'emailDomain' ],
			'pageCategories' => [ $this, 'pageCategories' ],
			'pageLinksCount' => [ $this, 'pageLinksCount' ],
			'fileLinksCount' => [ $this, 'fileLinksCount' ],
		] );
	}

	/**
	 * Get the domain of the email of a user
	 *
	 * @param string $name User name
	 * @return string Email domain
	 */
	public function emailDomain( $name ) {
		$user = MediaWikiServices::getInstance()->getUserFactory()->newFromName( $name );
		$email = $user->getEmail();
		$domain = '';
		if ( $email ) {
			$domain = substr( $email, strpos( $email, '@' ) + 1 );
		}
		return [ $domain ];
	}

	/**
	 * Get the categories of a page
	 *
	 * @param string $page Page name
	 * @return array Lua table
	 */
	public function pageCategories( $page ) {
		$title = Title::newFromText( $page );
		$id = $title->getArticleID();
		$db = wfGetDB( DB_REPLICA );
		$lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$dbr = $lb->getConnectionRef( DB_REPLICA );
		$results = $dbr->newSelectQueryBuilder()
			->select( 'cl_to' )
			->from( 'categorylinks' )
			->where( [ 'cl_from' => $id ] )
			->fetchResultSet();
		$categories = [];
		foreach ( $results as $result ) {
			$category = $result->cl_to;
			$category = str_replace( '_', ' ', $category );
			$categories[] = $category;
		}
		return [ self::toLuaTable( $categories ) ];
	}

	/**
	 * Get the number of links to the given page
	 *
	 * @param string $page Page name
	 * @return array Lua table
	 */
	public function pageLinksCount( $page ) {
		$title = Title::newFromText( $page );
		$db = wfGetDB( DB_REPLICA );
		$lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$dbr = $lb->getConnectionRef( DB_REPLICA );
		$queryBuilder = $dbr->newSelectQueryBuilder();
		$count = $queryBuilder->select( 'COUNT(*)' )->from( 'pagelinks' )->where( [ 'pl_title' => $page ] )->fetchField();
		$count = intval( $count );
		return [ $count ];
	}

	/**
	 * Get the number of uses of the given file
	 *
	 * @param string $file File name
	 * @return array Lua table
	 */
	public function fileLinksCount( $file ) {
		$title = Title::newFromText( $file, NS_FILE );
		$db = wfGetDB( DB_REPLICA );
		$lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$dbr = $lb->getConnectionRef( DB_REPLICA );
		$queryBuilder = $dbr->newSelectQueryBuilder();
		$count = $queryBuilder->select( 'COUNT(*)' )->from( 'imagelinks' )->where( [ 'il_to' => $file ] )->fetchField();
		$count = intval( $count );
		return [ $count ];
	}

	/**
	 * Helper method to convert an array to a viable Lua table
	 *
	 * The resulting table has its numerical indices start with 1
	 * If $array is not an array, it is simply returned
	 *
	 * @param mixed $array
	 * @return mixed Lua object
	 * @see https://github.com/SemanticMediaWiki/SemanticScribunto/blob/master/src/ScribuntoLuaLibrary.php
	 */
	private static function toLuaTable( $array ) {
		if ( is_array( $array ) ) {
			foreach ( $array as $key => $value ) {
				$array[ $key ] = self::toLuaTable( $value );
			}
			array_unshift( $array, '' );
			unset( $array[0] );
		}
		return $array;
	}
}
