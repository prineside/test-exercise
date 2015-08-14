<?php
	/**
	 *	Аккаунт пользователя
	 *
	 *	Для хранения информации о текущем аккаунте используются cookies (id и hash)
	 */
	
	class Account {
		const TABLE_NAME = 'account';
		const LOGIN_ATTEMPT_TABLE_NAME = 'login_attempt';
		
		const BRUTEFORCE_ATTEMPTS = 5;					// Количество неудачных попыток входа за BRUTEFORCE_BLOCK_TIME, при превышении которого все попытки будут отвергаться
		const BRUTEFORCE_BLOCK_TIME = 300;				// Количество секунд, за которое с одного IP может быть не больше BRUTEFORCE_ATTEMPTS попыток входа
		
		private static $_currentAccount = null;			// Текущий аккаунт (аккаунт пользователя, запрос которого сейчас обрабатывается)
		
		private static $_instances = array();			// Загруженные экземпляры аккаунтов
		
		private $_guest;								// Гостевой аккаунт
		private $_id = null;							// ID аккаунта (null для гостевого)
		private $_data = array();						// Данные аккаунта
		
		/**
 		 *	Получить текущий аккаунт пользователя (запрос которого обрабатывается)
		 **/
		public static function getCurrent() {
			if ( self::$_currentAccount === null ) {
				// Объект аккаунта еще не существует, обработка cookies
				$accountInstance = null;
				if ( array_key_exists( 'id', $_COOKIE ) && array_key_exists( 'hash', $_COOKIE ) ) {
					// Cookies установлены, проверка
					$idFromCookies = (int)$_COOKIE[ 'id' ];
					$hashFromCookies = $_COOKIE[ 'hash' ];
					
					if ( self::exists( $idFromCookies ) ) {
						// Аккаунт с указанным ID существует, далее проверяем хэш
						if ( strlen( $hashFromCookies ) != 0 && self::getInstance( $idFromCookies )->getData( 'hash' ) == $hashFromCookies ) {
							// Хэш совпал, устанавливаем аккант пользователю
							$accountInstance = self::getInstance( $idFromCookies );
						}
					}
					
					if ( $accountInstance === null ) {
						// Аккаунт не установлен - следовательно, cookies указаны неверно. Очистка cookies.
						self::clearCookies();
					}
				}
				
				if ( $accountInstance === null ) {
					// Аккаунт не был извлечен из cookies, создаем экземпляр гостевого аккаунта
					$accountInstance = new self();
				}
				
				self::$_currentAccount = $accountInstance;
			}
			
			return self::$_currentAccount;
		}
		
		/**
		 *	Установка аккаунта как текущего
		 **/
		public static function setCurrent( Account $accountInstance ) {
			self::$_currentAccount = $accountInstance;
		}
		
		/**
		 *	Удаление cookies (выход из сайта)
		 **/
		public static function clearCookies() {
			setcookie( 'id', 0, time() - 1, '/' );
			setcookie( 'hash', 0, time() - 1, '/' );
		}
		
		/**
		 *	Проверка данных для входа в аккаунт и аутентификация текущего пользователя в случае успеха
		 **/
		public static function loginAttempt( $loginOrEmail, $password ) {
			// Проверка формата введенных данных
			if ( empty( $loginOrEmail ) ) {
				return 'noLogin';
			}
			if ( empty( $password ) ) {
				return 'noPassword';
			}
			if ( !Misc::validFormat( $loginOrEmail, 'login' ) && !Misc::validFormat( $loginOrEmail, 'email' ) ) {
				return 'wrongLoginFormat';
			}
			if ( !Misc::validFormat( $password, 'password' ) ) {
				return 'wrongPasswordFormat';
			}
			// Проверка валидности формата данных завершена, попытка входа
			
			// Подсчет неудачных попыток входа за последнее время
			$attemptsCountResult = DB::getHandle()->query( "
				SELECT COUNT(*) AS cnt
				FROM " . self::LOGIN_ATTEMPT_TABLE_NAME . "
				WHERE ip = '" . DB::escape( Misc::getClientIP() ) . "'
				AND status = 0
				AND date > " . ( time() - self::BRUTEFORCE_BLOCK_TIME ) . "
			" )->fetch_array( MYSQLI_ASSOC );
			
			if ( $attemptsCountResult[ 'cnt' ] < self::BRUTEFORCE_ATTEMPTS ) {
				// Не превышен лимит попыток. Ищем аккаунт, в который пытаются войти
				$targetAccount = Misc::validFormat( $loginOrEmail, 'email' ) 
					? Account::getInstanceByEmail( $loginOrEmail ) 
					: Account::getInstanceByLogin( $loginOrEmail );
				
				if ( $targetAccount == null ) {
					// Аккаунт не найден
					return 'accountNotFound';
				} else {
					// Аккаунт найден, проверка пароля
					$attemptStatus = null;
					if ( Account::hashPassword( $password, $targetAccount->getData( 'salt' ) ) == $targetAccount->getData( 'hash' ) ) {
						// Хэш совпал - пароль введен верно
						
						// Установка cookies и текущего активного аккаунта
						Account::setCurrent( $targetAccount );
						$targetAccount->setCookies();
						
						$attemptStatus = 1;
						return 'success';
					} else {
						// Хэш не совпал - неверно указан пароль
						$attemptStatus = 0;
					}
					
					// Запись попытки входа
					DB::getHandle()->query( "
						INSERT INTO " . self::LOGIN_ATTEMPT_TABLE_NAME . "
						( ip, date, account, status, browser )
						VALUES (
							'" . DB::escape( Misc::getClientIP() ) . "',
							" . time() . ",
							" . $targetAccount->getID() . ",
							" . $attemptStatus . ",
							'" . DB::escape( array_key_exists( 'HTTP_USER_AGENT', $_SERVER ) ? $_SERVER[ 'HTTP_USER_AGENT' ] : '' ) . "'
						)
					" );
					
					return ( $attemptStatus == 1 ) ? 'success' : 'wrongPassword';
				}
			} else {
				// Превышен лимит попыток
				return 'tooManyWrongAttempts';
			}
		}
	
		/**
		 *	Проверка существования аккаунта
		 **/
		public static function exists( $id ) {
			// Поиск по загруженным аккаунтам
			if ( array_key_exists( $id, self::$_instances ) ) {
				return true;
			}
			
			// В загруженных аккаунтах нет, поиск в базе
			$instances = self::getAllInstances( null, null, 1, 1, "id = " . (int)$id );
			
			return sizeof( $instances ) != 0;
		}
	
		/**
		 *	Возвращает экземпляр аккаунта с указанным ID
		 **/
		public static function getInstance( $id ) {
			// Поиск по загруженным аккаунтам
			if ( array_key_exists( $id, self::$_instances ) ) {
				return self::$_instances[ $id ];
			}
			
			// В загруженных аккаунтах нет, поиск в базе
			$instances = self::getAllInstances( null, null, 1, 1, "id = " . (int)$id );
			if ( sizeof( $instances ) == 0 ) {
				return null;
			} else {
				return array_pop( $instances );
			}
		}
		
		/**
		 *	Возвращает массив аккаунтов, которые удовлетворяют условия
		 **/
		public static function getAllInstances( $sortKey = null, $sortOrder = null, $page = null, $perPage = null, $filter = null ) {
			$query = "
				SELECT *
				FROM " . self::TABLE_NAME . "
			";
			if ( $filter != null ) {
				$query .= " WHERE " . DB::filterConditions( $filter );
			}
			if ( $sortKey != null ) {
				$query .= " ORDER BY " . DB::filterTableRow( $sortKey ) . " " . DB::filterSortOrder( $sortOrder ? $sortOrder : "ASC" );
			}
			if ( $page != null && $perPage != null ) {
				$query .= " LIMIT " . ( ( $page - 1 ) * $perPage ) . ", " . (int)$perPage;
			}
			$result = DB::getHandle()->query( $query );
			
			$ret = array();
			while ( $row = $result->fetch_assoc() ) {
				if ( !array_key_exists( $row[ 'id' ], self::$_instances ) ) {
					self::$_instances[ $row[ 'id' ] ] = new self( $row[ 'id' ], $row );
				}
				$ret[ $row[ 'id' ] ] = self::$_instances[ $row[ 'id' ] ];
			}
			
			return $ret;
		}
	
		/**
		 *	Возвращает экземпляр аккаунта с указанным логином или null
		 **/
		public static function getInstanceByLogin( $login ) {
			// Поиск по загруженным аккаунтам
			foreach ( self::$_instances as $instance ) {
				if ( $instance->getData( 'login' ) == $login ) {
					return $instance;
				}
			}
			
			// В загруженных аккаунтах нет, поиск в базе
			$instances = self::getAllInstances( null, null, 1, 1, "login LIKE '" . DB::escape( $login ) . "'" );
			if ( sizeof( $instances ) == 0 ) {
				return null;
			} else {
				return array_pop( $instances );
			}
		}
	
		/**
		 *	Возвращает экземпляр аккаунта с указанным email или null
		 **/
		public static function getInstanceByEmail( $email ) {
			// Поиск по загруженным аккаунтам
			foreach ( self::$_instances as $instance ) {
				if ( $instance->getData( 'email' ) == $email ) {
					return $instance;
				}
			}
			
			// В загруженных аккаунтах нет, поиск в базе
			$instances = self::getAllInstances( null, null, 1, 1, "email LIKE '" . DB::escape( $email ) . "'" );
			if ( sizeof( $instances ) == 0 ) {
				return null;
			} else {
				return array_pop( $instances );
			}
		}
		
		/**
		 *	Генерирует новую случайную фразу для смешивания с паролем в хэш
		 **/
		public static function generateSalt() {
			$salt = '';
			
			for ( $i=0; $i<32; $i++ ) {
				$salt .= sprintf( "%x", rand( 0, 15 ) );
			}
			
			return $salt;
		}
		
		/**
		 *	Возвращает хэш пароля, который используется в аккаунтах
		 **/
		public static function hashPassword( $password, $salt ) {
			$result = '';
			$passwordMD5 = md5( $password );
			
			for ( $i=0; $i<32; $i++ ) {
				$result .= sprintf( '%x', ( hexdec( $passwordMD5{ $i } ) + hexdec( $salt{ $i } ) ) % 16 );
			}
			
			return md5( $result );
		}
		
		/**
		 *	Создает новый аккаунт и возвращает его объект
		 **/
		public static function create( $data ) {
			// Генерация хэша пароля
			$salt = self::generateSalt();
			$hash = self::hashPassword( $data[ 'password' ], $salt );
			
			$query = "
				INSERT INTO " . self::TABLE_NAME . "
				(email, login, hash, salt, registration_date, name, surname, gender)
				VALUES (
					'" . DB::escape( $data[ 'email' ] ) . "',
					'" . DB::escape( $data[ 'login' ] ) . "',
					'" . DB::escape( $hash ) . "',
					'" . DB::escape( $salt ) . "',
					" . time() . ",
					'" . DB::escape( $data[ 'name' ] ) . "',
					'" . DB::escape( $data[ 'surname' ] ) . "',
					'" . DB::escape( $data[ 'gender' ] ) . "'
				);
			";
			$result = DB::getHandle()->query( $query );
			if ( $result ) {
				return self::getInstance( DB::getHandle()->insert_id );
			} else {
				throw new Exception( 'Can\'t create account - database returned error: ' . DB::getHandle()->error );
			}
		}
		
		/**
 		 *	Создать экземпляр аккаунта
		 *	$id - primary key
		 *	Если $id не указан, будет возвращен гостевой аккаунт
		 **/
		public function __construct( $id = null, $data = null ) {
			if ( $id != null ) {
				$this->_id = $id;
				
				if ( $data == null ) {
					$data = DB::getHandle()->query( "
						SELECT *
						FROM " . self::TABLE_NAME . "
						WHERE id = " . (int)$id . "
					" )->fetch_array( MYSQLI_ASSOC );
				}
				
				if ( empty( $data ) ) {
					throw new Exception( 'Account ' . $id . ' not exists or wrong data passed' );
				}
				$this->_data = $data;
				$this->_guest = false;
			} else {
				$this->_guest = true;
			}
		}
		
		/**
		 *	Установка cookies аккаунта текущему пользователю для последующей автоматический аутентификации
		 *	при переходе на другие странице
		 **/
		public function setCookies() {
			if ( $this->isGuest() ) {
				throw new Exception( "Can't set guest account cookies" );
			} else {
				setcookie( 'id', $this->getData( 'id' ), time() + ( 60 * 60 * 24 * 365 ), '/' );
				setcookie( 'hash', $this->getData( 'hash' ), time() + ( 60 * 60 * 24 * 365 ), '/' );
			}
		}
		
		public function getID() {
			return $this->_id;
		}
		
		public function getData( $index ) {
			return array_key_exists( $index, $this->_data ) ? $this->_data[ $index ] : null;
		}
		
		public function isGuest() {
			return $this->_guest;
		}
	}