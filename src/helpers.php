<?php
if (!function_exists('captcha')) {
	function captcha($config = 'default', $formId = false) {
		return app('captcha')->create($config, $formId);
	}
}

if (!function_exists('captcha_src')) {
	function captcha_src($config = 'default', $formId = false) {
		return app('captcha')->src($config, $formId);
	}
}

if (!function_exists('captcha_img')) {
	function captcha_img($config = 'default', $formId = false) {
		return app('captcha')->img($config, $formId);
	}
}

if (!function_exists('captcha_lazy_img')) {
	function captcha_lazy_img($config = 'default', $formId = false) {
		return app('captcha')->lazy_img($config, $formId);
	}
}

if (!function_exists('captcha_check')) {
	function captcha_check($value) {
		return app('captcha')->check($value);
	}
}
