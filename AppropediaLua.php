<?php

use MediaWiki\MediaWikiServices;

/**
 * This class queries the database for the Appropedia Lua library
 */
class AppropediaLua extends Scribunto_LuaLibraryBase {

	public static function onScribuntoExternalLibraries( string $engine, array &$extraLibraries ) {
		$extraLibraries['appropedia'] = self::class;
	}

	public function register() {
		$this->getEngine()->registerInterface( __DIR__ . '/AppropediaLua.lua', [
			'emailDomain' => [ $this, 'emailDomain' ],
			'pageExists' => [ $this, 'pageExists' ],
			'pageCategories' => [ $this, 'pageCategories' ],
			'fileUses' => [ $this, 'fileUses' ],
		] );
	}

	/**
	 * Get the domain of the email of a user
	 *
	 * @param string $name User name
	 * @return string Email domain
	 */
	public function emailDomain( $name ) {
		$domain = '';
		$user = MediaWikiServices::getInstance()->getUserFactory()->newFromName( $name );
		if ( $user ) {
			$email = $user->getEmail();
			if ( $email ) {
				$domain = substr( $email, strpos( $email, '@' ) + 1 );
			}
		}
		return [ $domain ];
	}

	/**
	 * Check if the given page exists
	 *
	 * @param string $page Page name
	 * @return array Lua table
	 */
	public function pageExists( $page ) {
		$title = Title::newFromText( $page );
		if ( !$title ) {
			return [ false ];
		}
		if ( $title->isKnown() ) {
			return [ true ];
		}
		return [ false ];
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
		$services = MediaWikiServices::getInstance();
		$provider = $services->getConnectionProvider();
		$dbr = $provider->getReplicaDatabase();
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
	 * Get the number of uses of the given file
	 *
	 * @param string $file File name
	 * @return array Lua table
	 */
	public function fileUses( $file ) {
		$title = Title::newFromText( $file, NS_FILE );
		$services = MediaWikiServices::getInstance();
		$provider = $services->getConnectionProvider();
		$dbr = $provider->getReplicaDatabase();
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
