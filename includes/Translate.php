<?php
	/**
	 *	Перевод на другие языки
	 **/
	class Translate {
		const DEFAULT_LOCALE = 'ru';						// Стандартный язык
		const TRANSLATIONS_DIR = 'translations/';			// Каталог с файлами переводов
		
		private static $_locale = self::DEFAULT_LOCALE;		// Текущий язык (стандартный)
		private static $_availableLocales = array();		// Доступные языки (устанавливаются в index.php)
		private static $_dictionary = null;					// Словари для перевода
		private static $_dictionaryStatus = array();		// Состояние словарей
		
		/**
		 *	Инициализация Translate
		 *
		 *	Загружает библиотеки переводов из файлов
		 **/
		private static function init() {
			if ( self::$_dictionary == null ) {
				self::$_dictionary = array();
				
				foreach ( self::getAvailableLocales() as $language => $languageName ) {
					self::$_dictionary[ $language ] = array();
					self::$_dictionaryStatus[ $language ] = array( 'translated' => 0, 'total' => 0 );
					
					if ( is_file( self::TRANSLATIONS_DIR . $language . '.loc' ) ) {
						$wordsArray = array();
						
						$lines = file( self::TRANSLATIONS_DIR . $language . '.loc' );
						foreach ( $lines as $line ) {
							$line = trim( $line );
							if ( strlen( $line ) != 0 ) {
								$expl = explode( '<|>', $line );
								if ( sizeof( $expl ) >= 2 ) {
									$wordsArray[ $expl[ 0 ] ] = $expl[ 1 ];
									if ( strlen( $expl[ 1 ] ) != 0 ) {
										self::$_dictionaryStatus[ $language ][ 'translated' ]++;
									}
									self::$_dictionaryStatus[ $language ][ 'total' ]++;
								}
							}
						}
						self::$_dictionary[ $language ] = $wordsArray;
					}
				}
			}
		}
		
		/**
		 *	Перезагружает словари из файлов
		 **/
		public static function reload() {
			self::$_dictionary = null;
			self::init();
		}
		
		/**
		 *	Установка доступных языков
		 **/
		public static function setAvailableLocales( $locales ) {
			self::$_availableLocales = $locales;
		}
		
		/**
		 *	Получение доступных языков
		 **/
		public static function getAvailableLocales() {
			return self::$_availableLocales;
		}
		
		/**
		 *	Проверка существования языка
		 **/
		public static function localeExists( $locale ) {
			return array_key_exists( $locale, self::$_availableLocales );
		}
		
		/**
		 *	Получение названия языка по сокращению
		 **/
		public static function getLocaleName( $locale ) {
			return self::localeExists( $locale ) ? self::$_availableLocales[ $locale ] : null;
		}
		
		/**
		 *	Установка текущего языка перевода сайта
		 **/
		public static function setLocale( $locale ) {
			if ( !self::localeExists( $locale ) ) {
				throw new Exception( 'Locale ' . $locale . ' not exists' );
			}
			self::$_locale = $locale;
		}
		
		/**
		 *	Получение текущего установленного языка
		 **/
		public static function getLocale() {
			return self::$_locale;
		}
		
		/**
		 *	Перевод фразы на другой язык
		 **/
		public static function toLanguage( $text, $language ) {
			self::init();
			if ( $language != self::DEFAULT_LOCALE ) {
				if ( array_key_exists( $text, self::$_dictionary[ $language ] ) ) {
					if ( self::$_dictionary[ $language ][ $text ] != '' ) {
						return self::$_dictionary[ $language ][ $text ];
					} else {
						return $text;
					}
				} else {
					self::addToDictionary( $language , $text );
					return $text;
				}
			} else {
				return $text;
			}
		}
		
		/**
		 *	Перевод фразы на текущий язык
		 **/
		public static function toCurrent( $text ) {
			return self::toLanguage( $text, self::getLocale() );
		}
		
		/**
		 *	Добавление фразы в словарь
		 *	$language - язык, для которого добавляется перевод
		 *	$from - фраза на языке по умолчанию, которую переводят
		 *	$to - фраза, переведенная на язык $language или null, если необходимо только добавить запись о несуществуюзем переводе
		 **/
		public static function addToDictionary( $language, $from, $to = null ) {
			$language = preg_replace( '/[^a-z]/', '', $language );
			if ( !array_key_exists( $from, self::$_dictionary[ $language ] ) ) {
				if ( $to !== null ) {
					self::$_dictionary[ $language ][ $from ] = $to;
				} else {
					self::$_dictionary[ $language ][ $from ] = $from;
				}
				$to = ( $to != null ) ? $to : '';
				
				$from = str_replace( '<|>', '&lt;|&gt;', $from );
				$to = str_replace( '<|>', '&lt;|&gt;', $to );
				
				$src = fopen( self::TRANSLATIONS_DIR . $language . '.loc', 'a' );
				fwrite( $src, $from . '<|>' . $to . "\n" );
				fclose( $src );
			}
		}
	}