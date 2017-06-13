webpackJsonp([1],{

/***/ 69:
/***/ (function(module, exports, __webpack_require__) {

/*! http://mths.be/placeholder v2.0.8 by @mathias */
var jQuery = __webpack_require__(0);

;(function(window, document, $) {

	// Opera Mini v7 doesn’t support placeholder although its DOM seems to indicate so
	var isOperaMini = Object.prototype.toString.call(window.operamini) == '[object OperaMini]';
	var isInputSupported = 'placeholder' in document.createElement('input') && !isOperaMini;
	var isTextareaSupported = 'placeholder' in document.createElement('textarea') && !isOperaMini;
	var prototype = $.fn;
	var valHooks = $.valHooks;
	var propHooks = $.propHooks;
	var hooks;
	var placeholder;

	if (isInputSupported && isTextareaSupported) {

		placeholder = prototype.placeholder = function() {
			return this;
		};

		placeholder.input = placeholder.textarea = true;

	} else {

		placeholder = prototype.placeholder = function() {
			var $this = this;
			$this
				.filter((isInputSupported ? 'textarea' : ':input') + '[placeholder]')
				.not('.placeholder')
				.bind({
					'focus.placeholder': clearPlaceholder,
					'blur.placeholder': setPlaceholder
				})
				.data('placeholder-enabled', true)
				.trigger('blur.placeholder');
			return $this;
		};

		placeholder.input = isInputSupported;
		placeholder.textarea = isTextareaSupported;

		hooks = {
			'get': function(element) {
				var $element = $(element);

				var $passwordInput = $element.data('placeholder-password');
				if ($passwordInput) {
					return $passwordInput[0].value;
				}

				return $element.data('placeholder-enabled') && $element.hasClass('placeholder') ? '' : element.value;
			},
			'set': function(element, value) {
				var $element = $(element);

				var $passwordInput = $element.data('placeholder-password');
				if ($passwordInput) {
					return $passwordInput[0].value = value;
				}

				if (!$element.data('placeholder-enabled')) {
					return element.value = value;
				}
				if (value == '') {
					element.value = value;
					// Issue #56: Setting the placeholder causes problems if the element continues to have focus.
					if (element != safeActiveElement()) {
						// We can't use `triggerHandler` here because of dummy text/password inputs :(
						setPlaceholder.call(element);
					}
				} else if ($element.hasClass('placeholder')) {
					clearPlaceholder.call(element, true, value) || (element.value = value);
				} else {
					element.value = value;
				}
				// `set` can not return `undefined`; see http://jsapi.info/jquery/1.7.1/val#L2363
				return $element;
			}
		};

		if (!isInputSupported) {
			valHooks.input = hooks;
			propHooks.value = hooks;
		}
		if (!isTextareaSupported) {
			valHooks.textarea = hooks;
			propHooks.value = hooks;
		}

		$(function() {
			// Look for forms
			$(document).delegate('form', 'submit.placeholder', function() {
				// Clear the placeholder values so they don't get submitted
				var $inputs = $('.placeholder', this).each(clearPlaceholder);
				setTimeout(function() {
					$inputs.each(setPlaceholder);
				}, 10);
			});
		});

		// Clear placeholder values upon page reload
		$(window).bind('beforeunload.placeholder', function() {
			$('.placeholder').each(function() {
				this.value = '';
			});
		});

	}

	function args(elem) {
		// Return an object of element attributes
		var newAttrs = {};
		var rinlinejQuery = /^jQuery\d+$/;
		$.each(elem.attributes, function(i, attr) {
			if (attr.specified && !rinlinejQuery.test(attr.name)) {
				newAttrs[attr.name] = attr.value;
			}
		});
		return newAttrs;
	}

	function clearPlaceholder(event, value) {
		var input = this;
		var $input = $(input);
		if (input.value == $input.attr('placeholder') && $input.hasClass('placeholder')) {
			if ($input.data('placeholder-password')) {
				$input = $input.hide().next().show().attr('id', $input.removeAttr('id').data('placeholder-id'));
				// If `clearPlaceholder` was called from `$.valHooks.input.set`
				if (event === true) {
					return $input[0].value = value;
				}
				$input.focus();
			} else {
				input.value = '';
				$input.removeClass('placeholder');
				input == safeActiveElement() && input.select();
			}
		}
	}

	function setPlaceholder() {
		var $replacement;
		var input = this;
		var $input = $(input);
		var id = this.id;
		if (input.value == '') {
			if (input.type == 'password') {
				if (!$input.data('placeholder-textinput')) {
					try {
						$replacement = $input.clone().attr({ 'type': 'text' });
					} catch(e) {
						$replacement = $('<input>').attr($.extend(args(this), { 'type': 'text' }));
					}
					$replacement
						.removeAttr('name')
						.data({
							'placeholder-password': $input,
							'placeholder-id': id
						})
						.bind('focus.placeholder', clearPlaceholder);
					$input
						.data({
							'placeholder-textinput': $replacement,
							'placeholder-id': id
						})
						.before($replacement);
				}
				$input = $input.removeAttr('id').hide().prev().attr('id', id).show();
				// Note: `$input[0] != input` now!
			}
			$input.addClass('placeholder');
			$input[0].value = $input.attr('placeholder');
		} else {
			$input.removeClass('placeholder');
		}
	}

	function safeActiveElement() {
		// Avoid IE9 `document.activeElement` of death
		// https://github.com/mathiasbynens/jquery-placeholder/pull/99
		try {
			return document.activeElement;
		} catch (exception) {}
	}

}(this, document, jQuery));


/***/ })

});
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9+L2pxdWVyeS5wbGFjZWhvbGRlci9qcXVlcnkucGxhY2Vob2xkZXIuanMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6Ijs7Ozs7QUFBQTtBQUNBOztBQUVBLENBQUM7O0FBRUQ7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUVBOztBQUVBO0FBQ0E7QUFDQTs7QUFFQTs7QUFFQSxFQUFFOztBQUVGO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxLQUFLO0FBQ0w7QUFDQTtBQUNBO0FBQ0E7O0FBRUE7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7O0FBRUE7QUFDQTtBQUNBO0FBQ0E7O0FBRUE7QUFDQSxJQUFJO0FBQ0o7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLEtBQUs7QUFDTDtBQUNBLEtBQUs7QUFDTDtBQUNBO0FBQ0Esd0NBQXdDO0FBQ3hDO0FBQ0E7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsS0FBSztBQUNMLElBQUk7QUFDSixHQUFHOztBQUVIO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsSUFBSTtBQUNKLEdBQUc7O0FBRUg7O0FBRUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLEdBQUc7QUFDSDtBQUNBOztBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxJQUFJO0FBQ0o7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBLDBDQUEwQyxpQkFBaUI7QUFDM0QsTUFBTTtBQUNOLDZEQUE2RCxpQkFBaUI7QUFDOUU7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsT0FBTztBQUNQO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxPQUFPO0FBQ1A7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxHQUFHO0FBQ0g7QUFDQTtBQUNBOztBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxHQUFHO0FBQ0g7O0FBRUEsQ0FBQyIsImZpbGUiOiIxLmpzIiwic291cmNlc0NvbnRlbnQiOlsiLyohIGh0dHA6Ly9tdGhzLmJlL3BsYWNlaG9sZGVyIHYyLjAuOCBieSBAbWF0aGlhcyAqL1xudmFyIGpRdWVyeSA9IHJlcXVpcmUoJ2pxdWVyeScpO1xuXG47KGZ1bmN0aW9uKHdpbmRvdywgZG9jdW1lbnQsICQpIHtcblxuXHQvLyBPcGVyYSBNaW5pIHY3IGRvZXNu4oCZdCBzdXBwb3J0IHBsYWNlaG9sZGVyIGFsdGhvdWdoIGl0cyBET00gc2VlbXMgdG8gaW5kaWNhdGUgc29cblx0dmFyIGlzT3BlcmFNaW5pID0gT2JqZWN0LnByb3RvdHlwZS50b1N0cmluZy5jYWxsKHdpbmRvdy5vcGVyYW1pbmkpID09ICdbb2JqZWN0IE9wZXJhTWluaV0nO1xuXHR2YXIgaXNJbnB1dFN1cHBvcnRlZCA9ICdwbGFjZWhvbGRlcicgaW4gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnaW5wdXQnKSAmJiAhaXNPcGVyYU1pbmk7XG5cdHZhciBpc1RleHRhcmVhU3VwcG9ydGVkID0gJ3BsYWNlaG9sZGVyJyBpbiBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCd0ZXh0YXJlYScpICYmICFpc09wZXJhTWluaTtcblx0dmFyIHByb3RvdHlwZSA9ICQuZm47XG5cdHZhciB2YWxIb29rcyA9ICQudmFsSG9va3M7XG5cdHZhciBwcm9wSG9va3MgPSAkLnByb3BIb29rcztcblx0dmFyIGhvb2tzO1xuXHR2YXIgcGxhY2Vob2xkZXI7XG5cblx0aWYgKGlzSW5wdXRTdXBwb3J0ZWQgJiYgaXNUZXh0YXJlYVN1cHBvcnRlZCkge1xuXG5cdFx0cGxhY2Vob2xkZXIgPSBwcm90b3R5cGUucGxhY2Vob2xkZXIgPSBmdW5jdGlvbigpIHtcblx0XHRcdHJldHVybiB0aGlzO1xuXHRcdH07XG5cblx0XHRwbGFjZWhvbGRlci5pbnB1dCA9IHBsYWNlaG9sZGVyLnRleHRhcmVhID0gdHJ1ZTtcblxuXHR9IGVsc2Uge1xuXG5cdFx0cGxhY2Vob2xkZXIgPSBwcm90b3R5cGUucGxhY2Vob2xkZXIgPSBmdW5jdGlvbigpIHtcblx0XHRcdHZhciAkdGhpcyA9IHRoaXM7XG5cdFx0XHQkdGhpc1xuXHRcdFx0XHQuZmlsdGVyKChpc0lucHV0U3VwcG9ydGVkID8gJ3RleHRhcmVhJyA6ICc6aW5wdXQnKSArICdbcGxhY2Vob2xkZXJdJylcblx0XHRcdFx0Lm5vdCgnLnBsYWNlaG9sZGVyJylcblx0XHRcdFx0LmJpbmQoe1xuXHRcdFx0XHRcdCdmb2N1cy5wbGFjZWhvbGRlcic6IGNsZWFyUGxhY2Vob2xkZXIsXG5cdFx0XHRcdFx0J2JsdXIucGxhY2Vob2xkZXInOiBzZXRQbGFjZWhvbGRlclxuXHRcdFx0XHR9KVxuXHRcdFx0XHQuZGF0YSgncGxhY2Vob2xkZXItZW5hYmxlZCcsIHRydWUpXG5cdFx0XHRcdC50cmlnZ2VyKCdibHVyLnBsYWNlaG9sZGVyJyk7XG5cdFx0XHRyZXR1cm4gJHRoaXM7XG5cdFx0fTtcblxuXHRcdHBsYWNlaG9sZGVyLmlucHV0ID0gaXNJbnB1dFN1cHBvcnRlZDtcblx0XHRwbGFjZWhvbGRlci50ZXh0YXJlYSA9IGlzVGV4dGFyZWFTdXBwb3J0ZWQ7XG5cblx0XHRob29rcyA9IHtcblx0XHRcdCdnZXQnOiBmdW5jdGlvbihlbGVtZW50KSB7XG5cdFx0XHRcdHZhciAkZWxlbWVudCA9ICQoZWxlbWVudCk7XG5cblx0XHRcdFx0dmFyICRwYXNzd29yZElucHV0ID0gJGVsZW1lbnQuZGF0YSgncGxhY2Vob2xkZXItcGFzc3dvcmQnKTtcblx0XHRcdFx0aWYgKCRwYXNzd29yZElucHV0KSB7XG5cdFx0XHRcdFx0cmV0dXJuICRwYXNzd29yZElucHV0WzBdLnZhbHVlO1xuXHRcdFx0XHR9XG5cblx0XHRcdFx0cmV0dXJuICRlbGVtZW50LmRhdGEoJ3BsYWNlaG9sZGVyLWVuYWJsZWQnKSAmJiAkZWxlbWVudC5oYXNDbGFzcygncGxhY2Vob2xkZXInKSA/ICcnIDogZWxlbWVudC52YWx1ZTtcblx0XHRcdH0sXG5cdFx0XHQnc2V0JzogZnVuY3Rpb24oZWxlbWVudCwgdmFsdWUpIHtcblx0XHRcdFx0dmFyICRlbGVtZW50ID0gJChlbGVtZW50KTtcblxuXHRcdFx0XHR2YXIgJHBhc3N3b3JkSW5wdXQgPSAkZWxlbWVudC5kYXRhKCdwbGFjZWhvbGRlci1wYXNzd29yZCcpO1xuXHRcdFx0XHRpZiAoJHBhc3N3b3JkSW5wdXQpIHtcblx0XHRcdFx0XHRyZXR1cm4gJHBhc3N3b3JkSW5wdXRbMF0udmFsdWUgPSB2YWx1ZTtcblx0XHRcdFx0fVxuXG5cdFx0XHRcdGlmICghJGVsZW1lbnQuZGF0YSgncGxhY2Vob2xkZXItZW5hYmxlZCcpKSB7XG5cdFx0XHRcdFx0cmV0dXJuIGVsZW1lbnQudmFsdWUgPSB2YWx1ZTtcblx0XHRcdFx0fVxuXHRcdFx0XHRpZiAodmFsdWUgPT0gJycpIHtcblx0XHRcdFx0XHRlbGVtZW50LnZhbHVlID0gdmFsdWU7XG5cdFx0XHRcdFx0Ly8gSXNzdWUgIzU2OiBTZXR0aW5nIHRoZSBwbGFjZWhvbGRlciBjYXVzZXMgcHJvYmxlbXMgaWYgdGhlIGVsZW1lbnQgY29udGludWVzIHRvIGhhdmUgZm9jdXMuXG5cdFx0XHRcdFx0aWYgKGVsZW1lbnQgIT0gc2FmZUFjdGl2ZUVsZW1lbnQoKSkge1xuXHRcdFx0XHRcdFx0Ly8gV2UgY2FuJ3QgdXNlIGB0cmlnZ2VySGFuZGxlcmAgaGVyZSBiZWNhdXNlIG9mIGR1bW15IHRleHQvcGFzc3dvcmQgaW5wdXRzIDooXG5cdFx0XHRcdFx0XHRzZXRQbGFjZWhvbGRlci5jYWxsKGVsZW1lbnQpO1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0fSBlbHNlIGlmICgkZWxlbWVudC5oYXNDbGFzcygncGxhY2Vob2xkZXInKSkge1xuXHRcdFx0XHRcdGNsZWFyUGxhY2Vob2xkZXIuY2FsbChlbGVtZW50LCB0cnVlLCB2YWx1ZSkgfHwgKGVsZW1lbnQudmFsdWUgPSB2YWx1ZSk7XG5cdFx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdFx0ZWxlbWVudC52YWx1ZSA9IHZhbHVlO1xuXHRcdFx0XHR9XG5cdFx0XHRcdC8vIGBzZXRgIGNhbiBub3QgcmV0dXJuIGB1bmRlZmluZWRgOyBzZWUgaHR0cDovL2pzYXBpLmluZm8vanF1ZXJ5LzEuNy4xL3ZhbCNMMjM2M1xuXHRcdFx0XHRyZXR1cm4gJGVsZW1lbnQ7XG5cdFx0XHR9XG5cdFx0fTtcblxuXHRcdGlmICghaXNJbnB1dFN1cHBvcnRlZCkge1xuXHRcdFx0dmFsSG9va3MuaW5wdXQgPSBob29rcztcblx0XHRcdHByb3BIb29rcy52YWx1ZSA9IGhvb2tzO1xuXHRcdH1cblx0XHRpZiAoIWlzVGV4dGFyZWFTdXBwb3J0ZWQpIHtcblx0XHRcdHZhbEhvb2tzLnRleHRhcmVhID0gaG9va3M7XG5cdFx0XHRwcm9wSG9va3MudmFsdWUgPSBob29rcztcblx0XHR9XG5cblx0XHQkKGZ1bmN0aW9uKCkge1xuXHRcdFx0Ly8gTG9vayBmb3IgZm9ybXNcblx0XHRcdCQoZG9jdW1lbnQpLmRlbGVnYXRlKCdmb3JtJywgJ3N1Ym1pdC5wbGFjZWhvbGRlcicsIGZ1bmN0aW9uKCkge1xuXHRcdFx0XHQvLyBDbGVhciB0aGUgcGxhY2Vob2xkZXIgdmFsdWVzIHNvIHRoZXkgZG9uJ3QgZ2V0IHN1Ym1pdHRlZFxuXHRcdFx0XHR2YXIgJGlucHV0cyA9ICQoJy5wbGFjZWhvbGRlcicsIHRoaXMpLmVhY2goY2xlYXJQbGFjZWhvbGRlcik7XG5cdFx0XHRcdHNldFRpbWVvdXQoZnVuY3Rpb24oKSB7XG5cdFx0XHRcdFx0JGlucHV0cy5lYWNoKHNldFBsYWNlaG9sZGVyKTtcblx0XHRcdFx0fSwgMTApO1xuXHRcdFx0fSk7XG5cdFx0fSk7XG5cblx0XHQvLyBDbGVhciBwbGFjZWhvbGRlciB2YWx1ZXMgdXBvbiBwYWdlIHJlbG9hZFxuXHRcdCQod2luZG93KS5iaW5kKCdiZWZvcmV1bmxvYWQucGxhY2Vob2xkZXInLCBmdW5jdGlvbigpIHtcblx0XHRcdCQoJy5wbGFjZWhvbGRlcicpLmVhY2goZnVuY3Rpb24oKSB7XG5cdFx0XHRcdHRoaXMudmFsdWUgPSAnJztcblx0XHRcdH0pO1xuXHRcdH0pO1xuXG5cdH1cblxuXHRmdW5jdGlvbiBhcmdzKGVsZW0pIHtcblx0XHQvLyBSZXR1cm4gYW4gb2JqZWN0IG9mIGVsZW1lbnQgYXR0cmlidXRlc1xuXHRcdHZhciBuZXdBdHRycyA9IHt9O1xuXHRcdHZhciByaW5saW5lalF1ZXJ5ID0gL15qUXVlcnlcXGQrJC87XG5cdFx0JC5lYWNoKGVsZW0uYXR0cmlidXRlcywgZnVuY3Rpb24oaSwgYXR0cikge1xuXHRcdFx0aWYgKGF0dHIuc3BlY2lmaWVkICYmICFyaW5saW5lalF1ZXJ5LnRlc3QoYXR0ci5uYW1lKSkge1xuXHRcdFx0XHRuZXdBdHRyc1thdHRyLm5hbWVdID0gYXR0ci52YWx1ZTtcblx0XHRcdH1cblx0XHR9KTtcblx0XHRyZXR1cm4gbmV3QXR0cnM7XG5cdH1cblxuXHRmdW5jdGlvbiBjbGVhclBsYWNlaG9sZGVyKGV2ZW50LCB2YWx1ZSkge1xuXHRcdHZhciBpbnB1dCA9IHRoaXM7XG5cdFx0dmFyICRpbnB1dCA9ICQoaW5wdXQpO1xuXHRcdGlmIChpbnB1dC52YWx1ZSA9PSAkaW5wdXQuYXR0cigncGxhY2Vob2xkZXInKSAmJiAkaW5wdXQuaGFzQ2xhc3MoJ3BsYWNlaG9sZGVyJykpIHtcblx0XHRcdGlmICgkaW5wdXQuZGF0YSgncGxhY2Vob2xkZXItcGFzc3dvcmQnKSkge1xuXHRcdFx0XHQkaW5wdXQgPSAkaW5wdXQuaGlkZSgpLm5leHQoKS5zaG93KCkuYXR0cignaWQnLCAkaW5wdXQucmVtb3ZlQXR0cignaWQnKS5kYXRhKCdwbGFjZWhvbGRlci1pZCcpKTtcblx0XHRcdFx0Ly8gSWYgYGNsZWFyUGxhY2Vob2xkZXJgIHdhcyBjYWxsZWQgZnJvbSBgJC52YWxIb29rcy5pbnB1dC5zZXRgXG5cdFx0XHRcdGlmIChldmVudCA9PT0gdHJ1ZSkge1xuXHRcdFx0XHRcdHJldHVybiAkaW5wdXRbMF0udmFsdWUgPSB2YWx1ZTtcblx0XHRcdFx0fVxuXHRcdFx0XHQkaW5wdXQuZm9jdXMoKTtcblx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdGlucHV0LnZhbHVlID0gJyc7XG5cdFx0XHRcdCRpbnB1dC5yZW1vdmVDbGFzcygncGxhY2Vob2xkZXInKTtcblx0XHRcdFx0aW5wdXQgPT0gc2FmZUFjdGl2ZUVsZW1lbnQoKSAmJiBpbnB1dC5zZWxlY3QoKTtcblx0XHRcdH1cblx0XHR9XG5cdH1cblxuXHRmdW5jdGlvbiBzZXRQbGFjZWhvbGRlcigpIHtcblx0XHR2YXIgJHJlcGxhY2VtZW50O1xuXHRcdHZhciBpbnB1dCA9IHRoaXM7XG5cdFx0dmFyICRpbnB1dCA9ICQoaW5wdXQpO1xuXHRcdHZhciBpZCA9IHRoaXMuaWQ7XG5cdFx0aWYgKGlucHV0LnZhbHVlID09ICcnKSB7XG5cdFx0XHRpZiAoaW5wdXQudHlwZSA9PSAncGFzc3dvcmQnKSB7XG5cdFx0XHRcdGlmICghJGlucHV0LmRhdGEoJ3BsYWNlaG9sZGVyLXRleHRpbnB1dCcpKSB7XG5cdFx0XHRcdFx0dHJ5IHtcblx0XHRcdFx0XHRcdCRyZXBsYWNlbWVudCA9ICRpbnB1dC5jbG9uZSgpLmF0dHIoeyAndHlwZSc6ICd0ZXh0JyB9KTtcblx0XHRcdFx0XHR9IGNhdGNoKGUpIHtcblx0XHRcdFx0XHRcdCRyZXBsYWNlbWVudCA9ICQoJzxpbnB1dD4nKS5hdHRyKCQuZXh0ZW5kKGFyZ3ModGhpcyksIHsgJ3R5cGUnOiAndGV4dCcgfSkpO1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0XHQkcmVwbGFjZW1lbnRcblx0XHRcdFx0XHRcdC5yZW1vdmVBdHRyKCduYW1lJylcblx0XHRcdFx0XHRcdC5kYXRhKHtcblx0XHRcdFx0XHRcdFx0J3BsYWNlaG9sZGVyLXBhc3N3b3JkJzogJGlucHV0LFxuXHRcdFx0XHRcdFx0XHQncGxhY2Vob2xkZXItaWQnOiBpZFxuXHRcdFx0XHRcdFx0fSlcblx0XHRcdFx0XHRcdC5iaW5kKCdmb2N1cy5wbGFjZWhvbGRlcicsIGNsZWFyUGxhY2Vob2xkZXIpO1xuXHRcdFx0XHRcdCRpbnB1dFxuXHRcdFx0XHRcdFx0LmRhdGEoe1xuXHRcdFx0XHRcdFx0XHQncGxhY2Vob2xkZXItdGV4dGlucHV0JzogJHJlcGxhY2VtZW50LFxuXHRcdFx0XHRcdFx0XHQncGxhY2Vob2xkZXItaWQnOiBpZFxuXHRcdFx0XHRcdFx0fSlcblx0XHRcdFx0XHRcdC5iZWZvcmUoJHJlcGxhY2VtZW50KTtcblx0XHRcdFx0fVxuXHRcdFx0XHQkaW5wdXQgPSAkaW5wdXQucmVtb3ZlQXR0cignaWQnKS5oaWRlKCkucHJldigpLmF0dHIoJ2lkJywgaWQpLnNob3coKTtcblx0XHRcdFx0Ly8gTm90ZTogYCRpbnB1dFswXSAhPSBpbnB1dGAgbm93IVxuXHRcdFx0fVxuXHRcdFx0JGlucHV0LmFkZENsYXNzKCdwbGFjZWhvbGRlcicpO1xuXHRcdFx0JGlucHV0WzBdLnZhbHVlID0gJGlucHV0LmF0dHIoJ3BsYWNlaG9sZGVyJyk7XG5cdFx0fSBlbHNlIHtcblx0XHRcdCRpbnB1dC5yZW1vdmVDbGFzcygncGxhY2Vob2xkZXInKTtcblx0XHR9XG5cdH1cblxuXHRmdW5jdGlvbiBzYWZlQWN0aXZlRWxlbWVudCgpIHtcblx0XHQvLyBBdm9pZCBJRTkgYGRvY3VtZW50LmFjdGl2ZUVsZW1lbnRgIG9mIGRlYXRoXG5cdFx0Ly8gaHR0cHM6Ly9naXRodWIuY29tL21hdGhpYXNieW5lbnMvanF1ZXJ5LXBsYWNlaG9sZGVyL3B1bGwvOTlcblx0XHR0cnkge1xuXHRcdFx0cmV0dXJuIGRvY3VtZW50LmFjdGl2ZUVsZW1lbnQ7XG5cdFx0fSBjYXRjaCAoZXhjZXB0aW9uKSB7fVxuXHR9XG5cbn0odGhpcywgZG9jdW1lbnQsIGpRdWVyeSkpO1xuXG5cblxuLy8vLy8vLy8vLy8vLy8vLy8vXG4vLyBXRUJQQUNLIEZPT1RFUlxuLy8gLi9+L2pxdWVyeS5wbGFjZWhvbGRlci9qcXVlcnkucGxhY2Vob2xkZXIuanNcbi8vIG1vZHVsZSBpZCA9IDY5XG4vLyBtb2R1bGUgY2h1bmtzID0gMSJdLCJzb3VyY2VSb290IjoiIn0=