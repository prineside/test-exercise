<?php
	/**
	 *	Разного рода методы
	 *	Класс содержит методы, которые часто используются в определенных местах (грубо говоря, это контейнер для функций)
	 *	В идеале нужно создавать отдельные классы для некоторых групп методов (Filter, View...), но это уже работа по 
	 *	созданию движка / фреймворка, а для задания достаточно самих методов
	 **/
	class Misc {
		/**
		 *	View - Сформатировать ссылку
		 **/
		public static function url( $action, $language = null ) {
			if ( $language == null ) {
				$language = Translate::getLocale();
			}
			
			if ( $language == Translate::DEFAULT_LOCALE ) {
				return '/?action=' . $action;
			} else {
				return '/?action=' . $action . '&language=' . $language;
			}
		}
		
		/**
		 *	Filter - проверка валидности значения string в зависимости от типа filter
		 *	Например, проверка формата email
		 **/
		public static function validFormat( $string, $filter ) {
			switch ( $filter  ) {
				case 'login' : {
					if ( strlen( $string ) < 3 || strlen( $string ) > 32 ) {
						return false;
					} else {
						return preg_match( '/[a-z0-9_]+/i', $string );
					}
				}
				case 'password' : {
					if ( strlen( $string ) < 6 ) {
						return false;
					} else {
						return preg_match( '/[a-zа-яїёі]+/i', $string ) && preg_match( '/[0-9]+/', $string );
					}
				}
				case 'email' : {
					return filter_var( $string, FILTER_VALIDATE_EMAIL );
				}
				case 'name' : {
					if ( strlen( $string ) < 1 || strlen( $string ) > 32 ) {
						return false;
					} else {
						return preg_match( '/[a-zа-яїёі_\s]+/i', $string );
					}
				}
				case 'surname' : {
					if ( strlen( $string ) == 0 ) {
						return true;
					} else {
						return preg_match( '/[a-zа-яїёі_\s]+/i', $string );
					}
				}
			}
		}
		
		/**
		 *	View - Вывести переведенную на текущий язык фразу (обертка для Translate::toCurrent)
		 **/
		public static function _( $text ) {
			echo Translate::toCurrent( $text );
		}
		
		/**
		 *	Возвращает макс. размер загружаемых файлов в байтах
		 **/
		public static function getMaxUploadSizeBytes() {
			$unit = preg_replace( '/[^bkmg]/i', '', ini_get( 'upload_max_filesize' ) );	// Убираем все символы кроме единиц (б, кб, мб, гб)
			$size = preg_replace( '/[^0-9\.]/', '', ini_get( 'upload_max_filesize' ) ); // Убираем все, кроме цифр
			return $unit 
				? round( $size * pow( 1024, stripos( 'bkmg', $unit[0] ) ) )
				: round( $size );
		}
		
		/**
		 *	Превращение строки для вставки в Javascript
		 **/
		public static function toJavaScriptString( $string ) {
			return '"' . str_replace( 
				array( "\\", 	"\t", 	"\"", 	"\r\n", 	"\n", 	"script" ), 
				array( "\\\\", 	"\\t", 	"\\\"", "\\r\\n", 	"\\n", 	"scr\"+\"ipt" ), 
				$string ) . '"';
		}
		
		/**
		 *	Возвращает IP-адрес текущего пользователя
		 **/
		public static function getClientIP() {
			$ipAddress = $_SERVER[ 'REMOTE_ADDR' ];
			if ( array_key_exists( 'HTTP_X_REAL_IP', $_SERVER ) ) {
				$ipAddress = $_SERVER[ 'HTTP_X_REAL_IP' ];
			}
			if ( array_key_exists( 'HTTP_X_FORWARDED_FOR', $_SERVER ) ) {
				if ( !is_array( $_SERVER[ 'HTTP_X_FORWARDED_FOR'] ) ) {
					$ipAddress = $_SERVER[ 'HTTP_X_FORWARDED_FOR' ];
				}
			}
			return $ipAddress;
		}
	}