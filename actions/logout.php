<?php
	/**
	 *	site.com/?action=дщпщге
	 *
	 *	Страница выхода из сайта
	 *	Очищает cookies и перенаправляет на главную страницу
	 */ 
	
	$userAccount = Account::getCurrent();
	
	if ( !$userAccount->isGuest() ) {
		// Пользователь сайта - очищаем cookies
		Account::clearCookies();
	}
	
	header( 'Location: ' . Misc::url( 'index' ) );