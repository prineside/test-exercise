<?php
	/**
	 *	site.com/?action=notfound
	 *
	 *	Страница 404
	 */ 
	
	header( "HTTP/1.0 404 Not Found" );
	
	$title = Translate::toCurrent( 'Страница не найдена' );
	include( 'templates/notfound.phtml' );