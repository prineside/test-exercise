<?php
	/**
	 *	site.com/?action=index или site.com/
	 *
	 *	Стандартный обработчик - страница профиля пользователя или сообщение с просьбой войти на сайт,
	 *	если пользователь еще этого не сделал
	 */ 
	
	$userAccount = Account::getCurrent();
	
	if ( $userAccount->isGuest() ) {
		// Гостевой аккаунт - показываем сообщение с просьбой войти на сайт
		$title = Translate::toCurrent( 'Добро пожаловать!' );
		include( 'templates/index.phtml' );
	} else {
		// Пользователь сайта - показываем данные профиля
		$title = $userAccount->getData( 'name' ) . ' ' . $userAccount->getData( 'surname' );
		include( 'templates/profile.phtml' );
	}