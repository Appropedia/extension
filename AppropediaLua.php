<?php

use MediaWiki\MediaWikiServices;

class AppropediaLua extends Scribunto_LuaLibraryBase {

	public static function onScribuntoExternalLibraries( string $engine, array &$extraLibraries ) {
		$extraLibraries['appropedia'] = self::class;
	}

	public function register() {
		$this->getEngine()->registerInterface( __DIR__ . '/AppropediaLua.lua', [
			'pageCategories' => [ $this, 'pageCategories' ]
		] );
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
	 * Convert an array to a viable Lua table
	 *
	 * The resulting table has its numerical indices start with 1
	 * If $array is not an array, it is simply returned.
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
