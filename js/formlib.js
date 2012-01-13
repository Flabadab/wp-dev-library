var Formlib = (function($){
var	LIB = {
	/**
	 * console.log wrapper
	 */
	log: function(){
		if( window.console && window.console.log ){
			window.console.log(Array.prototype.slice.call(arguments));
		}
	}//---	lib.tools.write
	,
	/**
	 * DOM manipulation
	 */
	dom : {
		/**
		 * Common Regex patterns for evaluating stuff
		 */
		patterns : {
			className : function(selector){ return new RegExp("(^|\\s)"+selector+"(\\s|$)"); }
		}
		,
		/**
		 * If not browser-supported
		 * @see http://www.dustindiaz.com/getelementsbyclass/
		 */
		getElementsByClass : function (searchClass,node,tag) {
			if ( node == null ) node = document;
			
			//check if method already provided
			if( document.getElementsByClassName ){ 
				return node.getElementsByClassName(searchClass);
			}
			
			if ( tag == null )	tag = '*';
			
			var classElements = new Array();
			var els = node.getElementsByTagName(tag);
			var pattern = this.patterns.className(searchClass);
			for (i = 0, j = 0, iStop = els.length; i < iStop; i++) {
				if ( pattern.test(els[i].className) ) {
					classElements[j] = els[i];
					j++;
				}
			}

			return classElements;
		}//---	lib.dom.getElementsByClass
		,
		hasClass : function(node, selector){
			var pattern = this.patterns.className(selector);
			return pattern.test( node.className );
		}//---	lib.dom.hasclass
		,
		addClass : function(node, selector){
			if( node.className !== "" ){ selector = " " + selector; }
			node.className += selector;
		}//---	lib.dom.addClass
		,
		removeClass : function(node, selector){
			node.className.replace(selector, '');	///TODO whitespace?
		}//---	lib.dom.removeClass
	}///----	lib.dom
	,
	tools : {
		/**
		 * Merge b to a
		 */
		merge : function(a, b){
			if( b === undefined ){ return a; }
			for(var i in b){
				if( b.hasOwnProperty(i) )
			//for(var i = 0, iStop = b.length; i < iStop; i++){
				a[i] = b[i];
			}
			return a;
		}//--	lib.tools.merge
		,
		/**
		 * Loop through items of an object, perform callback function fn(index, element) on each
		 * @param o the object
		 * @param fn the callback
		 */
		each : function(o, fn){
			for(var i = 0, iStop = o.length; i < iStop; i++){
				fn(i, o[i]);
			}
		}//--	lib.tools.each
		,
		/**
		 * Empty object check, since jQuery not always up to date
		 * @see https://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.js
		 */
		isEmptyObject : function(obj){
			for ( var name in obj ) return false;
			return true;
		}//----		LIB.tools.isEmptyObject
	}///----	lib.tools
	,
	form : {
		/**
		 * Internal storage of settings
		 */
		default_settings : {
			usejQuery : ( undefined !== jQuery )
			, inspect : 'input'
			, indicate_required : true
			, inputTypes : ['input', 'textarea', 'select']
			, regex : {
					email : {exp: /^([A-Za-z0-9_\-\.\+])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/, msg: 'Invalid email'}
					,phone : {exp: /^[(]?\d{3}[)]?[-. ]?\d{3}[-. ]?\d{4}$/, msg:'Invalid phone'}
					,numeric: {exp:/^\d*$/, msg:'Numbers only'}
					,alpha: {exp:/^[\w\s]*$/, msg:'Letters only'}
					,alpha_numeric: {exp:/^[\w\s\d]*$/, msg:'Letters or numbers only'}
					,simple_chars: {exp:/^[\w\s\d-\.\'\"]*$/, msg:'Invalid characters - please only use letters, numbers, spaces, and the following: . - \' "'}
					,zip: {exp:/^(\d{5})|(\d{5}-\d{4})$/, msg:'Invalid zipcode/ZIP+4'}
					,hpt: {exp:/^$/, msg:'No auto-fillers allowed.  Refresh page and fill fields manually'}
					}
		}
		,
		settings : { }
		,
		/**
		 * initialize form formatting, etc
		 */
		init : function(form, options){
			//merge user options with settings
			LIB.tools.merge(LIB.form.settings, LIB.form.default_settings);
			LIB.tools.merge(LIB.form.settings, options);
			
			if( LIB.form.settings.usejQuery ){
				this.init_jQuery(form);
			}
			else {
				this.init_nojQuery(form);
			}
		}//--		LIB.form.init
		,
		/**
		 * initialize form formatting, etc (using jQuery)
		 */
		init_jQuery : function(form){
			var $inputs = []
				;
			
			//turn into object
			if( 'string' === typeof form ){
				form = $('#' + form);
			}
			
			//get all form inputs; ignore hidden fields & buttons
			$inputs = form.find( LIB.form.settings.inputTypes.join(',') ).not(':hidden, :submit');
			
			
			$inputs.each( function(i,o){
				var $input = $(o)
					, $parent = $input.parents('.field').first()	//ultimate parent that is field
					, labelSelector = ($input.is(':radio') || $input.is(':checkbox') ? 'legend' : 'label')
					, $inspect = ( 'input' === LIB.form.settings.inspect ? $input : $parent )
					;
				
				//make sure we're getting the parent - parentsUntil for immediate child breaks
				if( 0 == $parent.length ) $parent = $input.parent();
				
				// indicate required by adding * to label
				if( LIB.form.settings.indicate_required ) {
					//check for required
					if( $inspect.hasClass('required') ){
						var $label = $parent.find(labelSelector);
						
						$label.html('<em class="req"> * </em>');
					}
				}// if indicate_required
				
				//turn into tooltips, only if we haven't already done so
				if( 0 == $parent.find('.tooltip > .info').length ) {
					$parent.find('.tooltip').each(function(i, o){
						var $tooltip = $(o)
							, text = $tooltip.html()
							, title = $parent.find(labelSelector).text()
							;
						
						$tooltip
							.addClass('icon')
							.html( '<a class="info"><span class="inner"><strong class="title">' + title + '</strong><span>' + text + '</span></span></a>' );
					});
				}// if not already has tooltip
				
				//find suffixes?
				if( undefined !== $input.data('suffix') ){
					$input.after( $($input.data('suffix')));
					$input.removeData('suffix');
				}
			});
			
			//bind submit
			form.bind('submit', function(e){
				var result = LIB.form.validate(form);
				if( ! result ) e.preventDefault();
				return result;
			});
			
		}//--		LIB.form.init_jQuery
		,
		/**
		 * initialize form formatting, etc (without using jQuery)
		 */
		init_nojQuery : function(form){
			var $inputs = []
				;
			
			//if form given as id, turn into object
			if( 'string' === typeof form ){
				form = document.getElementById(form);
			}
			
			
			//get all form inputs
			for(t in LIB.form.settings.inputTypes){
				LIB.tools.merge($inputs, form.getElementsByTagName(LIB.form.settings.inputTypes[t]));
			}
			
			//loop through all inputs in form, check for attributes
			LIB.tools.each( $inputs, function(i,o){
				var $input = o
					, $inspect = ( 'input' === LIB.form.settings.inspect ? $input : $input.parentNode )
					;
				
				if( LIB.form.settings.indicate_required ) {
					//check for required
					if( LIB.dom.hasClass($inspect, 'required') ){
						var $label = $input.parentNode.getElementsByTagName('label')[0];
						
						$label.innerHTML += '<em class="req"> * </em>';
					}
				}// if indicate_required
			});
			
			//bind submit
			try {
				form.addEventListener("submit", function(e){
					var result = LIB.form.validateWithoutJquery(form);
					if( ! result ) e.preventDefault();
					return result;
					}, false);
			} catch(e) {
				form.attachEvent("submit", function(e){ 
					var result = LIB.form.validateWithoutJquery(form);
					if( ! result ) e.preventDefault();
					return result; }); //Internet Explorer
			}

			
		}//--		LIB.form.init_nojQuery
		,
		/**
		 * on submit callback - not as nice without jQuery
		 */
		validateWithoutJquery:  function(form){
			var inputTypes = ['input', 'textarea', 'select']
				, $inputs = []
				, isValid = true
				, errors = {}
				, regex = {}
				;
			
			//combine user-defined patterns
			regex = LIB.tools.merge(regex, LIB.form.default_settings.regex);
			
			//get all form inputs
			for(t in inputTypes){
				LIB.tools.merge($inputs, form.getElementsByTagName(inputTypes[t]));
			}
			
			//remove all error classes, validation messages
			var $inputsWithErrors = LIB.dom.getElementsByClass('error', form);
			LIB.tools.each( $inputsWithErrors, function(i,o){
				LIB.dom.removeClass(o,'error');
				var $errorMessages = LIB.dom.getElementsByClass('error-message', o);
				LIB.tools.each( $errorMessages, function(ii,p){
					o.removeChild( p );
				});
			});
			
			//loop through all inputs in form, check for attributes
			LIB.tools.each( $inputs, function(i,o){
				var $input = o
					, $inspect = ( 'input' === LIB.form.settings.inspect ? $input : $input.parentNode )
					, inputValue = $input.value
					, inputId = $input.id
					;
				
				//check for required
				if( ( LIB.dom.hasClass($inspect, 'required') ) && inputValue == ''){
					
					if(!errors[inputId]){ errors[inputId] = []; }
					
					errors[inputId].push('This field is required');
				}
				
				//check for inputs that must match other inputs
				if( ( LIB.dom.hasClass($inspect, 'match') ) ){
					var $match = document.getElementById( $inspect.attributes.rel.nodeValue );
					if( $input.value != $match.value ){
						if(!errors[inputId]){ errors[inputId] = []; }
						errors[inputId].push('Value does not match');
					}
				}
				
				
				//check for regex patterns
				for(pattern in regex){
					if( ( LIB.dom.hasClass($inspect, pattern)	) && (inputValue != '' && !inputValue.match(regex[pattern].exp)) ){
						if(!errors[inputId]){ errors[inputId] = []; }
						
						errors[inputId].push(regex[pattern].msg);
					}
					
				}
			});
			
			//add errors to fields
			if( !LIB.tools.isEmptyObject(errors) ){
				var $errorMsg = document.createElement('em');
				$errorMsg.className = 'error-message';
				
				for( key in errors ){
					var $input = document.getElementById(key)
						, $parent = $input.parentNode
						;
					
					LIB.dom.addClass($parent, 'error');
					for(err in errors[key]){
						$errorMsg.innerHTML = errors[key][err];
						$parent.appendChild( $errorMsg.cloneNode(true) );
					}
					
				}
				isValid = false;
			}
	
			return isValid;
		}//---	lib.form.validateWithoutJquery
		,
		/**
		 * on submit callback
		 */
		validate:  function(form){
			var $this = $(form)
				,$inputs = $this.find(':input')
				, isValid = true
				, isHtml5 = $this.hasClass('html5')
				, errors = {}
				, regex = {}
				;
			
			//combine user-defined patterns
			$.extend(regex, LIB.form.settings.regex);
			
			//remove all error classes, validation messages
			$this.find('.error').removeClass('error').find('.error-message').empty().remove();
			
			//loop through all inputs in form, check for attributes
			$inputs.each(function(){
				var $input = $(this)
					, $inspect = ( 'input' === LIB.form.settings.inspect ? $input : $input.parent() )
					, inputValue = $input.val()
					, inputId = '#' + $input.attr('id')
					, html5Validation = (isHtml5 ? $inspect.data('validation') : '')
					;
				
				//check for required
				if( ( (isHtml5 && html5Validation.indexOf('required') != -1)
					|| $inspect.hasClass('required')
					) && inputValue == ''){
					
					if(!errors[inputId]){
						errors[inputId] = [];
					}
					
					errors[inputId].push('This field is required');
				}
				
				//check for inputs that must match other inputs
				if( $inspect.hasClass('match') ){
					var $match = $( $inspect.attr('rel') );
					if( $input.value != $match.value ){
						if(!errors[inputId]){ errors[inputId] = []; }
						errors[inputId].push('Values do not match');
					}
				}
				
				//check for regex patterns
				for(pattern in regex){
					if( ( (isHtml5 && html5Validation.indexOf(pattern) != -1)
						|| $inspect.hasClass(pattern)
						) && (inputValue != '' && !inputValue.match(regex[pattern].exp)) ){
						
						if(!errors[inputId]){
							errors[inputId] = [];
						}
						
						errors[inputId].push('This field must be a valid pattern: ' + regex[pattern].msg);
					}
					
				}
			});
			
			if( !$.isEmptyObject(errors) ){
				for( key in errors ){
					$(key)
						.addClass('error')
						.parent()
							.addClass('error');
					for(err in errors[key]){
						$(key).after( $(document.createElement('em')).addClass('error error-message').html( errors[key][err] + '.' ) );
					}
					
				}
				isValid = false;
			}
	
			return isValid;
		}//---	lib.form.validate


	}///----	lib.form
};////-----		lib

return LIB;
})( jQuery );