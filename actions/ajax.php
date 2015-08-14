<?php
	/**
	 *	site.com/?action=ajax
	 *
	 *	Обработчик вспомогательных Ajax-запросов, например, для проверки валидности полей формы
	 *	Данные передаются в POST, ответ последует в json
	 */ 
	
	$userAccount = Account::getCurrent();
	
	switch ( $_POST[ 'do' ] ) {
		case 'translate' : {
			// Перевод некста на другой язык
			$text = $_POST[ 'text' ];
			$targetLanguage = $_POST[ 'toLanguage' ];
			
			$response = array(
				'translation' => Translate::toLanguage( $text, $targetLanguage )
			);
			
			echo json_encode( $response, JSON_UNESCAPED_UNICODE );
			break;
		}
		case 'validate' : {
			// Проверка валидности значения
			switch ( $_POST[ 'filter' ] ) {
			
				case 'account-exists' : {
					// Проверка существования аккаунта по адресу почты или логину
					$value = $_POST[ 'value' ];
					$response = array();
					
					if ( Misc::validFormat( $value, 'email' ) ) {
						// Поиск аккаунта по email
						if ( Account::getInstanceByEmail( $value ) != null ) {
							// Аккаунт с таким email существует
							$response = array(
								'valid' => true,
								'status' => 'valid',
								'message' => Translate::toCurrent( 'Профиль найден' )
							);
						} else {
							// Аккаунта с таким email не существует
							$response = array(
								'valid' => false,
								'status' => 'invalid',
								'message' => Translate::toCurrent( 'Профиль не найден' )
							);
						}
					} else if ( Misc::validFormat( $value, 'login' ) ) {
						// Поиск аккаунта по логину
						if ( Account::getInstanceByLogin( $value ) != null ) {
							// Аккаунт с таким логином существует
							$response = array(
								'valid' => true,
								'status' => 'valid',
								'message' => Translate::toCurrent( 'Профиль найден' )
							);
						} else {
							// Аккаунта с таким логином не существует
							$response = array(
								'valid' => false,
								'status' => 'invalid',
								'message' => Translate::toCurrent( 'Профиль не найден' )
							);
						}
					} else {
						$response = array(
							'valid' => false,
							'status' => 'invalid',
							'message' => Translate::toCurrent( 'Неверный формат логина или адреса электронной почты' )
						);
					}
					
					echo json_encode( $response, JSON_UNESCAPED_UNICODE );
					break;
				}
				
				case 'email-not-exists' : {
					// Проверка занятости адреса email
					// Если адрес не занят, возвращается true
					$value = $_POST[ 'value' ];
					$response = array();
					
					if ( Misc::validFormat( $value, 'email' ) ) {
						// Поиск аккаунта по email
						if ( Account::getInstanceByEmail( $value ) != null ) {
							// Аккаунт с таким email существует
							$response = array(
								'valid' => false,
								'status' => 'invalid',
								'message' => Translate::toCurrent( 'Этот адрес уже занят' )
							);
						} else {
							// Аккаунта с таким email не существует
							$response = array(
								'valid' => true,
								'status' => 'valid',
								'message' => Translate::toCurrent( 'Этот адрес не занят' )
							);
						}
					} else {
						$response = array(
							'valid' => false,
							'status' => 'invalid',
							'message' => Translate::toCurrent( 'Неверный формат адреса электронной почты' )
						);
					}
					
					echo json_encode( $response, JSON_UNESCAPED_UNICODE );
					break;
				}
				
				case 'login-not-exists' : {
					// Проверка занятости логина
					// Если логин не занят, возвращается true
					$value = $_POST[ 'value' ];
					$response = array();
					
					if ( Misc::validFormat( $value, 'login' ) ) {
						// Поиск аккаунта по login
						if ( Account::getInstanceByLogin( $value ) != null ) {
							// Аккаунт с таким логином существует
							$response = array(
								'valid' => false,
								'status' => 'invalid',
								'message' => Translate::toCurrent( 'Этот логин уже занят' )
							);
						} else {
							// Аккаунта с таким логином не существует
							$response = array(
								'valid' => true,
								'status' => 'valid',
								'message' => Translate::toCurrent( 'Этот логин не занят' )
							);
						}
					} else {
						$response = array(
							'valid' => false,
							'status' => 'invalid',
							'message' => Translate::toCurrent( 'Неверный формат логина' )
						);
					}
					
					echo json_encode( $response, JSON_UNESCAPED_UNICODE );
					break;
				}
			}
		}
	}