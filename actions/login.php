<?php
	/**
	 *	site.com/?action=login
	 *
	 *	Страница входа на сайт
	 */ 
	
	$userAccount = Account::getCurrent();
	
	$title = Translate::toCurrent( 'Вход' );
	
	if ( $userAccount->isGuest() ) {
		// Гостевой аккаунт
		if ( array_key_exists( 'do', $_POST ) && $_POST[ 'do' ] == 'login' && array_key_exists( 'login', $_POST ) && array_key_exists( 'password', $_POST ) ) {
			// Пользователь отправил форму с необходимыми полями, выполняем попытку входа
			$loginAttemptStatus = Account::loginAttempt( $_POST[ 'login' ], $_POST[ 'password' ] );
			if ( $loginAttemptStatus == 'success' ) {
				// Успешный вход - перенаправление в профиль
				header( 'Location: ' . Misc::url( 'index' ) );
			} else {
				// Произошла ошибка - выводим сообщение и показываем форму входа
				if ( Misc::validFormat( $_POST[ 'login' ], 'login' ) || Misc::validFormat( $_POST[ 'login' ], 'email' ) ) {
					$inputLogin = $_POST[ 'login' ];
				}
				
				$message = '';
				switch ( $loginAttemptStatus ) {
					case 'noLogin' : $message = Translate::toCurrent( 'Логин / адрес электронной почты не указан' ); break;
					case 'noPassword' : $message = Translate::toCurrent( 'Пароль не указан' ); break;
					case 'wrongLoginFormat' : $message = Translate::toCurrent( 'Неверный формат логина / адреса электронной почты' ); break;
					case 'wrongPasswordFormat' : $message = Translate::toCurrent( 'Неверный формат пароля' ); break;
					case 'tooManyWrongAttempts' : $message = Translate::toCurrent( 'Превышен лимит неудачных попыток входа, подождите несколько минут' ); break;
					case 'accountNotFound' : $message = Translate::toCurrent( 'Профиль с таким логином или адресом электронной почты не найден' ); break;
					case 'wrongPassword' : $message = Translate::toCurrent( 'Пароль указан неверно' ); break;
				}
				
				$messageType = 'error';
				
				include( 'templates/login.phtml' );
			}
		} else {
			// Пользователь не отправлял форму - показываем форму входа на сайт
			include( 'templates/login.phtml' );
		}
	} else {
		// Пользователь сайта - перенаправление на страницу профиля, нет потребности показывать форму входа
		header( 'Location: ' . Misc::url( 'index' ) );
	}