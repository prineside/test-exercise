<?php
	/**
	 *	site.com/?action=registration
	 *
	 *	Страница регистрации на сайте
	 */ 
	
	$userAccount = Account::getCurrent();
	
	if ( $userAccount->isGuest() ) {
		// Гостевой аккаунт
		$title = Translate::toCurrent( 'Регистрация' );
		
		if ( array_key_exists( 'do', $_POST ) && $_POST[ 'do' ] == 'registration' ) {
			// Пользователь отправил форму с данными для регистрации
			$inputEmail = $_POST[ 'email' ];
			$inputLogin = $_POST[ 'login' ];
			$inputName = $_POST[ 'name' ];
			$inputSurname = $_POST[ 'surname' ];
			$inputGender = $_POST[ 'gender' ];
			$inputPassword = $_POST[ 'password' ];
			$inputPasswordConfirm = $_POST[ 'password-confirm' ];
			
			$image = null;
			
			if ( array_key_exists( 'image', $_FILES ) ) {
				if ( $_FILES[ 'image' ][ 'error' ] == 0 ) {
					$image = $_FILES[ 'image' ];
				}
			}
				
			// Проверка введенных данных
			$errorMessages = array();
			if ( !Misc::validFormat( $inputEmail, 'email' ) ) {
				$errorMessages[] = Translate::toCurrent( 'Неверный формат адреса электронной почты' );
			} 
			if ( !Misc::validFormat( $inputLogin, 'login' ) ) {
				$errorMessages[] = Translate::toCurrent( 'Неверный формат логина' );
			} 
			if ( !Misc::validFormat( $inputName, 'name' ) ) {
				$errorMessages[] = Translate::toCurrent( 'Неверный формат имени' );
			} 
			if ( !Misc::validFormat( $inputSurname, 'surname' ) ) {
				$errorMessages[] = Translate::toCurrent( 'Неверный формат фамилии' );
			}
			if ( !in_array( $inputGender, array( 'male', 'female' ) ) ) {
				$errorMessages[] = Translate::toCurrent( 'Неверно указан пол' );
			}
			if ( !Misc::validFormat( $inputPassword, 'password' ) ) {
				$errorMessages[] = Translate::toCurrent( 'Неверный формат пароля' );
			}
			if ( $inputPasswordConfirm != $inputPassword ) {
				$errorMessages[] = Translate::toCurrent( 'Пароли не совпадают' );
			}
			if ( $image != null && !in_array( $image[ 'type' ], array( 'image/png', 'image/jpeg', 'image/gif' ) ) ) {
				$errorMessages[] = Translate::toCurrent( 'Неверный формат файла изображения' );
			}
			if ( $image != null && $image[ 'size' ] > Misc::getMaxUploadSizeBytes() ) {
				$errorMessages[] = Translate::toCurrent( 'Слишком большой файл изображения' );
			}
			
			if ( sizeof( $errorMessages ) == 0 ) {
				// Все проверки пройдены, создаем аккаунт и показываем страницу успешной регистрации
				
				// Создание аккаунта, установка cookies и установка его текущим
				$accountInstance = Account::create( array(
					'email' => $inputEmail,
					'login' => $inputLogin,
					'name' => $inputName,
					'surname' => $inputSurname,
					'gender' => $inputGender,
					'password' => $inputPassword
				) );
				$accountInstance->setCookies();
				Account::setCurrent( $accountInstance );
				
				// Сохранение изображения аккаунта, если загружено
				if ( $image != null ) {
					$imageInfo = getimagesize( $image[ 'tmp_name' ] );
					
					$gdHdl = null;
					
					if( $imageInfo[ 2 ] == IMAGETYPE_JPEG ) {
						$gdHdl = imagecreatefromjpeg( $image[ 'tmp_name' ] );
					} else if ( $imageInfo[ 2 ] == IMAGETYPE_GIF ) {
						$gdHdl = imagecreatefromgif( $image[ 'tmp_name' ] );
					} else if ( $imageInfo[ 2 ] == IMAGETYPE_PNG ) {
						$gdHdl = imagecreatefrompng( $image[ 'tmp_name' ] );
					}
					
					if ( $gdHdl ) {
						// Сохранение изображения без обработки
						// В этом месте можно изменить размер изображения или обрезать его
						imagejpeg( $gdHdl, 'media/images/' . $inputLogin . '.jpg', 80 );
					}
				}
				
				include( 'templates/registration-success.phtml' );
			} else {
				// Форма не принята, найдены ошибки. Возвращаем к форме
				$message = Translate::toCurrent( 'Произошли следующие ошибки при попытке регистрации:' );
				
				$message .= '<ul>';
				foreach ( $errorMessages as $errorMessage ) {
					$message .= '<li>' . $errorMessage . '</li>';
				}	
				$message .= '</ul>';
				
				$messageType = 'error';
				
				include( 'templates/registration.phtml' );
			}
			/*
			Array
			(
				[image] => Array
					(
						[name] => download.png
						[type] => image/png
						[tmp_name] => C:\Windows\Temp\php7F95.tmp
						[error] => 0
						[size] => 271944
					)

			)
			*/
		} else {
			// Пользователь не отправлял форму регистрации (GET)
			// Показываем форму регистрации
			include( 'templates/registration.phtml' );
		}
	} else {
		// Пользователь сайта - перенаправление на страницу профиля, нет потребности показывать форму регистрации
		header( 'Location: ' . Misc::url( 'index' ) );
	}