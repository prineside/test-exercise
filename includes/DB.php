<?php
	/**
	 *	Подключение к базе данных
	 */
	class DB {
		private static $_handle = null;
		
		/**
		 *	Инициализация подключений к базам данных (в этом случае только одно подключение)
		 *	$connectionData - массив с данными для подключения к базе
		 */
		public static function init( array $connectionData ) {
			self::$_handle = new mysqli( $connectionData[ 'host' ], $connectionData[ 'user' ], $connectionData[ 'password' ], $connectionData[ 'database' ] );
			if ( mysqli_connect_error() ) {
				// Ошибка подключения
				throw new Exception( 'Could not connect to database (errno: ' . mysqli_connect_errno() . ') ' . mysqli_connect_error() );
			}
		}
		
		/**
		 *	Получить указатель подключения к базе данных
		 */
		public static function getHandle( ) {
			if ( self::$_handle !== null ) {
				return self::$_handle;
			} else {
				throw new Exception( 'No database connection' );
			}
		}
		
		public static function escape( $str ) {
			return self::getHandle()->real_escape_string( $str );
		}
		
		public static function filterConditions( $str ) {
			$lStr = strtolower( $str );
			if ( strpos( $lStr, ';' ) !== false || strpos( $lStr, 'select ' ) !== false || strpos( $lStr, 'insert ' ) !== false || strpos( $lStr, 'delete ' ) !== false || strpos( $lStr, 'update ' ) !== false ) {
				return '';
			}
			return $str;
		}
		
		public static function filterTableRow( $str ) {
			return preg_replace( '/[^a-zA-Z0-9\-\_]/u', '', $str );
		}
		
		public static function filterSortOrder( $str ) {
			$str = strtoupper( $str );
			if ( array_key_exists( $str, array( 'DESC' => true, 'ASC' => true ) ) ) {
				return $str;
			} else {
				return 'ASC';
			}
		}
	}