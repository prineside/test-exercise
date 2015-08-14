Main = {
	language : null,
	action : null,
	
	/**
	 *	Инициализация основного скрипта
	 **/
	init : function( action, language ) {
		this.action = action;
		this.language = language;
		
		// Фокус элемента, в котором установлен атрибут data-focusOnLoadPage
		var requiresFocus = document.querySelector('[data-focusOnLoadPage]');
		if ( requiresFocus != null ) {
			requiresFocus.focus();
		}
		
		console.log( "Текущий обработчик: " + action + ", язык: " + language );
	},
	
	/**
	 *	Отправка POST-запроса
	 **/
	post : function( action, language, data, cb ) {
		var xmlhttp = new XMLHttpRequest();
		
		xmlhttp.onreadystatechange = function() {
			if ( xmlhttp.readyState == XMLHttpRequest.DONE ) {
				if ( xmlhttp.status == 200 ) {
					try {
						var parsed = JSON.parse( xmlhttp.responseText );
						console.log( parsed );
						cb( parsed, true );
					} catch ( e ) {
						console.error( xmlhttp.responseText );
						cb( xmlhttp.responseText, false );
					}
				} else {
					cb( xmlhttp, false );
				}
			}
		}

		xmlhttp.open( "POST", "/?action=" + action + "&language=" + language, true);
		xmlhttp.setRequestHeader( "Content-type", "application/x-www-form-urlencoded" );
		xmlhttp.send( data );
	},
	
	/**
	 *	Перевод текста (на стороне сервера)
	 *	Для уменьшения нагрузки можно при инициализации страницы загружать библиотеки и использовать на стороне клиента,
	 *	но это требует разделения библиотек переводчика на две части (одна основная, вторая - подмножество основной - фразы, которые
	 *	используются в Javascript), чтобы уменьшить трафик
	 **/
	translate : function( text, toLanguage, cb ) {
		this.post( 'ajax', this.language, this.formatPostData( { do : 'translate', text : text, toLanguage : toLanguage } ), function( response, status ) {
			cb( response.translation );
		} );
	},
	
	/**
	 *	Преобразование объекта в строку для отправки в Main.post (data)
	 *	Возвращает строку, например: a=10&b=20
	 **/
	formatPostData : function( data ) {
		var result = "";
		for ( var key in data ) {
		   result += encodeURIComponent( key ) + "=" + encodeURIComponent( data[ key ] ) + "&";
		}
		
		if ( result.length != 0 ) {
			result = result.substr( 0, result.length-1 );
		}
		
		return result;
	},
	
	/**
	 *	Перенаправление на эту же страницу, но на другом языке
	 **/
	changeLanguage : function( newLanguage ) {
		if ( newLanguage != this.language ) {
			window.location.href = '/?action=' + this.action + '&language=' + newLanguage;
		}
	},
	
	/**
	 *	Проверка формата значения
	 **/
	isValidValue : function( value, filter ) {
		switch ( filter ) {
			case 'login' : {
				if ( value.length < 3 || value.length > 32 ) {
					return false;
				} else {
					var filtered = value.match( /[a-z0-9_]+/i );
					return ( filtered != null && filtered[0] == value );
				}
			}
			case 'password' : {
				if ( value.length < 6 ) {
					return false;
				} else {
					return !( value.match( /[a-zа-яїёі]+/i ) == null || value.match( /[0-9]+/ ) == null );
				}
			}
			case 'email' : {
				var filtered = value.match( /[a-z0-9_\-\.]+@[a-z0-9_\-]+\.[a-z0-9_\-]+/i );
				return ( filtered != null && filtered[0] == value );
			}
			case 'name' : {
				if ( value.length < 1 || value.length > 32 ) {
					return false;
				} else {
					var filtered = value.match( /[a-zа-яїёі_\s]+/i );
					return ( filtered != null && filtered[0] == value );
				}
			}
			case 'surname' : {
				if ( value.length == 0 ) {
					return true;
				} else {
					var filtered = value.match( /[a-zа-яїёі_\s]+/i );
					return ( filtered != null && filtered[0] == value );
				}
			}
		}
		
		return true;
	},
	
	/**
	 *	Проверка поля формы на валидность введенных данных
	 *	Шаблон устанавливается в атрибуте data-validation
	 *	После проверки возле поля появится подсказка (правильно введено значение или нет)
	 **/
	validateFormField : function( formField, cb ) {
		if ( typeof( cb ) == "undefined" ) cb = function(){};
	
		var filter = formField.getAttribute( "data-validation" );
		if ( filter == null ) {
			cb( true );
		} else {
			// Удаление старых подсказок
			var removeValidationPopups = function() {
				var parent = formField.parentNode;
				
				var oldValidationPopups = parent.getElementsByClassName( "form-validation-popup" );
				var idx;
				for ( idx = 0; idx < oldValidationPopups.length; idx++ ) {
					parent.removeChild( oldValidationPopups[ idx] );
				}
			};
			
			// Отображение прогресса проверки
			var showValidationProgressPopup = function( message, type ) {
				removeValidationPopups();
				
				var parent = formField.parentNode;
			
				var newPopup = document.createElement( "span" );
				newPopup.innerHTML = '<i class="fa fa-spinner fa-pulse"></i>';
				newPopup.className = "form-validation-popup progress";
				parent.insertBefore( newPopup, formField.nextSibling );
			};
			
			// Отображение результата проверки
			var showValidationPopup = function( message, type ) {
				removeValidationPopups();
				
				var parent = formField.parentNode;
				
				var newPopup = document.createElement( "span" );
				newPopup.innerHTML = '<i class="fa fa-' + ( type == 'valid' ? 'check' : 'times' ) + '"></i>';
				newPopup.innerHTML += message;
				newPopup.className = "form-validation-popup " + type;
				parent.insertBefore( newPopup, formField.nextSibling );
			};
			
			// Проверка кэша последней проверки - если значение поля не изменилось, возвращаем те же данные
			if ( formField.getAttribute( "data-validation-last-value" ) != null ) {
				if ( formField.getAttribute( "data-validation-last-value" ) == formField.value ) {
					console.log( "cache" );
					cb( formField.getAttribute( "data-validation-last-result" ) == "true" );
					return;
				}
			}
			
			// Начало проверок
			switch ( filter ) {
				case 'account-exists' : {
					// Проверка существования аккаунта
					// Сначала происходит проверка на валидность логина или email
					if ( Main.isValidValue( formField.value, 'login' ) || Main.isValidValue( formField.value, 'email' ) ) {
						// Логин или email введены в праильном формате, проверка существования
						showValidationProgressPopup();
						
						var postData = {
							do : "validate",
							filter : "account-exists",
							value : formField.value
						};
						this.post( 'ajax', this.language, this.formatPostData( postData ), function( response, status ) {
							showValidationPopup( response.message, response.status );
							formField.setAttribute( "data-validation-last-value", formField.value );
							formField.setAttribute( "data-validation-last-result", response.valid ? "true" : "false" );
							cb( response.valid );
						} );
					} else {
						// Логин или email введены в неправильном формате
						Main.translate( "Неправильный формат логина или адреса электронной почты", Main.language, function( translated ) {
							showValidationPopup( translated, "invalid" );
							formField.setAttribute( "data-validation-last-value", formField.value );
							formField.setAttribute( "data-validation-last-result", "false" );
							cb( false );
						} );
					}
					break;
				}
				case 'email-not-exists' : {
					// Проверка существования электронной почты (true если почта не занята)
					// Сначала происходит проверка на валидность email
					if ( Main.isValidValue( formField.value, 'email' ) ) {
						// Email введен в праильном формате, проверка существования
						showValidationProgressPopup();
						
						var postData = {
							do : "validate",
							filter : "email-not-exists",
							value : formField.value
						};
						this.post( 'ajax', this.language, this.formatPostData( postData ), function( response, status ) {
							showValidationPopup( response.message, response.status );
							formField.setAttribute( "data-validation-last-value", formField.value );
							formField.setAttribute( "data-validation-last-result", response.valid ? "true" : "false" );
							cb( response.valid );
						} );
					} else {
						// Неправильный формат email
						Main.translate( "Неправильный формат адреса электронной почты", Main.language, function( translated ) {
							showValidationPopup( translated, "invalid" );
							formField.setAttribute( "data-validation-last-value", formField.value );
							formField.setAttribute( "data-validation-last-result", "false" );
							cb( false );
						} );
					}
					break;
				}
				case 'login-not-exists' : {
					// Проверка существования логина. Если логин не существует, возвращает true
					// Сначала происходит проверка на валидность логина
					if ( Main.isValidValue( formField.value, 'login' ) ) {
						// Логин введен в праильном формате, проверка существования
						showValidationProgressPopup();
						
						var postData = {
							do : "validate",
							filter : "login-not-exists",
							value : formField.value
						};
						this.post( 'ajax', this.language, this.formatPostData( postData ), function( response, status ) {
							showValidationPopup( response.message, response.status );
							formField.setAttribute( "data-validation-last-value", formField.value );
							formField.setAttribute( "data-validation-last-result", response.valid ? "true" : "false" );
							cb( response.valid );
						} );
					} else {
						// Логин введен в неправильном формате
						Main.translate( "Неправильный формат логина", Main.language, function( translated ) {
							showValidationPopup( translated, "invalid" );
							formField.setAttribute( "data-validation-last-value", formField.value );
							formField.setAttribute( "data-validation-last-result", "false" );
							cb( false );
						} );
					}
					break;
				}
				case 'password' : {
					if ( Main.isValidValue( formField.value, 'password' ) ) {
						// Пароль введен в правильном формате
						Main.translate( "Пароль введен в правильном формате", Main.language, function( translated ) {
							showValidationPopup( translated, "valid" );
							formField.setAttribute( "data-validation-last-value", formField.value );
							formField.setAttribute( "data-validation-last-result", "true" );
							cb( true );
						} );
					} else {
						// Пароль введен в неправильном формате
						Main.translate( "Неправильный формат пароля", Main.language, function( translated ) {
							showValidationPopup( translated, "invalid" );
							formField.setAttribute( "data-validation-last-value", formField.value );
							formField.setAttribute( "data-validation-last-result", "false" );
							cb( false );
						} );
					}
					break;
				}
				case 'password-confirm' : {
					if ( Main.isValidValue( formField.value, 'password' ) ) {
						// Пароль введен в правильном формате, проверка совпадения
						var compareToFormField = document.getElementById( formField.getAttribute( "data-password-field" ) );
						
						if ( compareToFormField.value == formField.value ) {
							// Пароли совпадают
							Main.translate( "Пароли совпадают", Main.language, function( translated ) {
								showValidationPopup( translated, "valid" );
								formField.setAttribute( "data-validation-last-value", formField.value );
								formField.setAttribute( "data-validation-last-result", "true" );
								cb( true );
							} );
						} else {
							// Пароли не совпали
							Main.translate( "Пароли не совпадают", Main.language, function( translated ) {
								showValidationPopup( translated, "invalid" );
								formField.setAttribute( "data-validation-last-value", formField.value );
								formField.setAttribute( "data-validation-last-result", "false" );
								cb( false );
							} );
						}
					} else {
						// Пароль введен в неправильном формате
						Main.translate( "Неправильный формат пароля", Main.language, function( translated ) {
							showValidationPopup( translated, "invalid" );
							formField.setAttribute( "data-validation-last-value", formField.value );
							formField.setAttribute( "data-validation-last-result", "false" );
							cb( false );
						} );
					}
					break;
				}
				case 'name' : {
					if ( Main.isValidValue( formField.value, 'name' ) ) {
						// Имя введено в правильном формате
						Main.translate( "Имя введено в правильном формате", Main.language, function( translated ) {
							showValidationPopup( translated, "valid" );
							formField.setAttribute( "data-validation-last-value", formField.value );
							formField.setAttribute( "data-validation-last-result", "true" );
							cb( true );
						} );
					} else {
						// Имя введено в неправильном формате
						Main.translate( "Неправильный формат имени", Main.language, function( translated ) {
							showValidationPopup( translated, "invalid" );
							formField.setAttribute( "data-validation-last-value", formField.value );
							formField.setAttribute( "data-validation-last-result", "false" );
							cb( false );
						} );
					}
					break;
				}
				case 'surname' : {
					if ( Main.isValidValue( formField.value, 'surname' ) ) {
						// Фамилия введена в правильном формате
						Main.translate( "Фамилия введена в правильном формате", Main.language, function( translated ) {
							showValidationPopup( translated, "valid" );
							formField.setAttribute( "data-validation-last-value", formField.value );
							formField.setAttribute( "data-validation-last-result", "true" );
							cb( true );
						} );
					} else {
						// Фамилия введена в неправильном формате
						Main.translate( "Неправильный формат фамилии", Main.language, function( translated ) {
							showValidationPopup( translated, "invalid" );
							formField.setAttribute( "data-validation-last-value", formField.value );
							formField.setAttribute( "data-validation-last-result", "false" );
							cb( false );
						} );
					}
					break;
				}
				case 'image' : {
					if ( formField.files.length == 0 ) {
						// Файл не выбран - это допустимо. Подсказку не показываем
						removeValidationPopups();
						cb( true );
					} else {
						// Файл выбран - проверка формата
						if ( formField.files[0].type == "image/jpeg" || formField.files[0].type == "image/png" || formField.files[0].type == "image/gif" ) {
							// Правильное разрешение файла, проверка размера файла
							var maxImageSize = parseInt( formField.getAttribute( "data-image-max-size" ) );
							if ( formField.files[0].size <= maxImageSize ) {
								// Размер изображения не превышает лимит
								Main.translate( "Выбрано подходящее изображение", Main.language, function( translated ) {
									showValidationPopup( translated, "valid" );
									formField.setAttribute( "data-validation-last-value", formField.value );
									formField.setAttribute( "data-validation-last-result", "true" );
									cb( true );
								} );
							} else {
								// Изображение больше лимита на загрузку
								Main.translate( "Слишком большой файл", Main.language, function( translated ) {
									showValidationPopup( translated, "invalid" );
									formField.setAttribute( "data-validation-last-value", formField.value );
									formField.setAttribute( "data-validation-last-result", "false" );
									cb( false );
								} );
							}
						} else {
							// Неверное разрешение файла
							Main.translate( "Неверный формат файла - допускаются только jpg, png и gif", Main.language, function( translated ) {
								showValidationPopup( translated, "invalid" );
								formField.setAttribute( "data-validation-last-value", formField.value );
								formField.setAttribute( "data-validation-last-result", "false" );
								cb( false );
							} );
						}
					}
					break;
				}
				default : {
					cb( true );
					break;
				}
			}
		}
	},
	
	/**
	 *	Проверка полей формы и подсветка полей с правильностью введения данных
	 *	cb - функция, в которую передается результат проверки
	 **/
	validateForm : function( form, cb ) {
		if ( typeof( cb ) == "undefined" ) cb = function(){};
		
		var formFields = form.getElementsByTagName( 'input' );
		var idx;
		var checkedCnt = 0;
		var validForm = true;
		for ( idx = 0; idx < formFields.length; idx++ ) {
			this.validateFormField( formFields[ idx ], function( result ) {
				if ( !result ) {
					validForm = false;
				}
				
				checkedCnt++;
				if ( checkedCnt == formFields.length ) {
					cb( validForm );
				}
			} );
		}
		
		return false;
	},
	
	/**
	 *	Проверка полей формы и подсветка полей с правильностью введения данных
	 *	Если все поля введены верно, отправляет form.submit()
	 *
	 *	Используется в <button type="submit" onClick="return Main.validateAndSubmitForm( this.form )"> внутри формы. 
	 *	Всегда возвращает false для отмены стандартной отправки формы. Такая техника не исключает нормальной
	 *	отправки формы при отключенном Javascript
	 **/
	validateAndSubmitForm : function( form ) {
		this.validateForm( form, function( result ) {
			console.log( result );
			if ( result ) {
				form.submit();
			}
		} );
		return false;
	}
};
// Отправка события - загружен скрипт Main
(function(){
	var event = new Event( "mainScriptLoad" );
	document.dispatchEvent( event );
})();