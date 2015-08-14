<?php
	/**
	 *	index.php - основной и единственный обработчик запросов
	 *
	 *	Для простоты текущая страница передается в GET[action], если же страница не существует или не указана,
	 *	будет использована страница "index" (профиль пользователя)
	 *
	 *	Всего есть несколько страниц:
	 *	- index - страница профиля пользователя (если пользователь вошел на сайт) или предложение войти / зарегистрироваться
	 *	- ajax - обработчик вспомогательных Ajax-запросов, в первую очередь для валидации полей формы (проверка занятости логина, ...)
	 *	- login - страница входа на сайт
	 *	- logout - страница выхода из сайта
	 *	- registration - страница регистрации на сайте
	 *	- notfound - страница 404 (если неверно указан обработчик)
	 **/
	
	require_once 'includes/DB.php';
	require_once 'includes/Account.php';
	require_once 'includes/Translate.php';
	require_once 'includes/Misc.php';
	
	define( 'DEFAULT_ACTION', 'index' );			// Стандартный обработчик (если ни один не указан)
	
	// Отображение всех ошибок
	error_reporting( E_ALL );
	ini_set( 'display_errors', 1 );
	
	// Загрузка конфигурации подключения к базе данных и инициализация подключений
	DB::init( include( 'db.config.php' ) );
	
	// Установка дополнительных языков перевода, имена должны быть на языке ru
	Translate::setAvailableLocales( array(
		'ru' => 'Русский',
		'ua' => 'Украинский',
		'en' => 'Английский'
	) );
	
	// Изменение языка сайта, если явно указан в $_GET[language]
	if ( array_key_exists( 'language', $_GET ) && Translate::localeExists( $_GET[ 'language' ] ) ) {
		Translate::setLocale( $_GET[ 'language' ] );
	}
	
	// Получение названия обработчика ($_GET[action]). Если не указан, установка стандартного
	$requestedAction = array_key_exists( 'action', $_GET )
		? preg_replace( '/[^a-z0-9]/', '', $_GET[ 'action' ] )
		: DEFAULT_ACTION;
		
	// Поиск файла обработчика. Если такой обработчик не существует - установка обработчика "notfound"
	if ( !is_file( 'actions/' . $requestedAction . '.php' ) ) {
		// Файл обработчика не существует
		$requestedAction = 'notfound';
	}
	
	// Подключение файла обработчика из каталога '/actions'
	header( 'Content-Type: text/html; charset=utf-8', true );
	include( 'actions/' . $requestedAction . '.php' );