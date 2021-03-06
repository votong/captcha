<?php
namespace VoTong\Captcha;


/**
 *
 * Laravel 5 Captcha package
 * 
 * @copyright Copyright (c) 2015 MeWebStudio
 * @version 2.0.0
 * @author Muharrem ERİN
 *         @contact me@mewebstudio.com
 *         @web http://www.mewebstudio.com
 *         @date 2015-04-03
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *         
 */
use Illuminate\Support\Facades\Input;
use Log, Exception;
use Illuminate\Config\Repository;
use Illuminate\Hashing\BcryptHasher as Hasher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Illuminate\Session\Store as SessionStore;
use Session;

class Captcha {
	
	/**
	 *
	 * @var Filesystem
	 */
	protected $files;
	
	/**
	 *
	 * @var Repository
	 */
	protected $config;
	
	/**
	 *
	 * @var ImageManager
	 */
	protected $imageManager;
	
	/**
	 *
	 * @var Session
	 */
	protected $session;
	
	/**
	 *
	 * @var Hasher
	 */
	protected $hasher;
	
	/**
	 *
	 * @var Str
	 */
	protected $str;
	
	/**
	 *
	 * @var Bool
	 */
	protected $unique = true;
	
	/**
	 *
	 * @var String
	 */
	protected $formId;
	
	/**
	 *
	 * @var ImageManager->canvas
	 */
	protected $canvas;
	
	/**
	 *
	 * @var ImageManager->image
	 */
	protected $image;
	
	/**
	 *
	 * @var array
	 */
	protected $backgrounds = [];
	
	/**
	 *
	 * @var array
	 */
	protected $fonts = [];
	
	/**
	 *
	 * @var array
	 */
	protected $fontColors = [];
	
	/**
	 *
	 * @var int
	 */
	protected $length = 5;
	
	/**
	 *
	 * @var int
	 */
	protected $width = 120;
	
	/**
	 *
	 * @var int
	 */
	protected $height = 36;
	
	/**
	 *
	 * @var int
	 */
	protected $angle = 15;
	
	/**
	 *
	 * @var int
	 */
	protected $lines = 3;
	
	/**
	 *
	 * @var string
	 */
	protected $characters = '2346789abcdefghjmnpqrtuxyzABCDEFGHJMNPQRTUXYZ';
	
	/**
	 *
	 * @var string
	 */
	protected $text;
	
	/**
	 *
	 * @var int
	 */
	protected $contrast = 0;
	
	/**
	 *
	 * @var int
	 */
	protected $quality = 90;
	
	/**
	 *
	 * @var int
	 */
	protected $sharpen = 0;
	
	/**
	 *
	 * @var int
	 */
	protected $blur = 0;
	
	/**
	 *
	 * @var bool
	 */
	protected $bgImage = true;
	
	/**
	 *
	 * @var string
	 */
	protected $bgColor = '#ffffff';
	
	/**
	 *
	 * @var bool
	 */
	protected $invert = false;
	
	/**
	 *
	 * @var bool
	 */
	protected $sensitive = false;

	/**
	 * Constructor
	 *
	 * @param Filesystem $files        	
	 * @param Repository $config        	
	 * @param ImageManager $imageManager        	
	 * @param Session $session        	
	 * @param Hasher $hasher        	
	 * @param Str $str        	
	 * @throws Exception
	 * @internal param Validator $validator
	 */
	public function __construct(Filesystem $files, Repository $config, ImageManager $imageManager, SessionStore $session, Hasher $hasher, Str $str) {
		$this->files = $files;
		$this->config = $config;
		$this->imageManager = $imageManager;
		$this->session = $session;
		$this->hasher = $hasher;
		$this->str = $str;
	}

	/**
	 *
	 * @param string $config        	
	 * @return void
	 */
	protected function configure($config, $formId = false) {
		if ($this->config->has('captcha.' . $config)) {
			foreach ($this->config->get('captcha.' . $config) as $key => $val) {
				$this->{$key} = $val;
			}
		}
		
		if ($formId !== false) {
			$this->formId = $formId;
		}
		
		elseif ($this->unique) {
			$this->formId = $this->str->random(32);
		}
	}

	/**
	 *
	 * @param string $config        	
	 * @return ImageManager->response
	 */
	public function create($config = 'default', $formId = false) {
		$this->backgrounds = $this->files->files(__DIR__ . '/../assets/backgrounds');
		$this->fonts = $this->files->files(__DIR__ . '/../assets/fonts');
		$this->fonts = array_values($this->fonts); // reset fonts array index
		
		$this->configure($config, $formId);
		$this->text = $this->generate();
		$this->canvas = $this->imageManager->canvas($this->width, $this->height, $this->bgColor);
		
		if ($this->bgImage) {
			$this->image = $this->imageManager->make($this->background())->resize($this->width, $this->height);
			$this->canvas->insert($this->image);
		}
		else {
			$this->image = $this->canvas;
		}
		
		if ($this->contrast != 0) {
			$this->image->contrast($this->contrast);
		}
		
		$this->text();
		
		$this->lines();
		
		if ($this->sharpen) {
			$this->image->sharpen($this->sharpen);
		}
		if ($this->invert) {
			$this->image->invert($this->invert);
		}
		if ($this->blur) {
			$this->image->blur($this->blur);
		}
		
		return $this->image->response('png', $this->quality);
	}

	/**
	 *
	 * @return string
	 */
	protected function background() {
		return $this->backgrounds[rand(0, count($this->backgrounds) - 1)];
	}

	/**
	 *
	 * @return string
	 */
	protected function generate() {
		$characters = str_split($this->characters);
		$bag = '';
		for ($i = 0; $i < $this->length; $i++) {
			$bag .= $characters[rand(0, count($characters) - 1)];
		}
		
		if (!$this->sensitive) {
			$bag = $this->str->lower($bag);
		}
		
		if ($this->formId) {
			//Log::info('$this->formId: ' . $this->formId . ' = ' . $bag);
			$this->session->put('captcha_' . $this->formId, $this->hasher->make($bag));
			//Log::info('$this->formId: ' . $this->formId . ' = ' . $this->session->get('captcha_' . $this->formId));
		}
		
		else {
			$this->session->put('captcha', $this->hasher->make($bag));
		}
		
		return $bag;
	}

	/**
	 * Writing text
	 */
	protected function text() {
		$marginTop = $this->image->height() / $this->length;
		
		$i = 0;
		foreach (str_split($this->text) as $char) {
			$marginLeft = ($i * $this->image->width() / $this->length);
			
			$this->image->text($char, $marginLeft, $marginTop, function ($font) {
				$font->file($this->font());
				$font->size($this->fontSize());
				$font->color($this->fontColor());
				$font->align('left');
				$font->valign('top');
				$font->angle($this->angle());
			});
			
			$i++;
		}
	}

	/**
	 *
	 * @return string
	 */
	protected function font() {
		return $this->fonts[rand(0, count($this->fonts) - 1)];
	}

	/**
	 *
	 * @return integer
	 */
	protected function fontSize() {
		return rand($this->image->height() - 10, $this->image->height());
	}

	/**
	 *
	 * @return array
	 */
	protected function fontColor() {
		if (!empty($this->fontColors)) {
			$color = $this->fontColors[rand(0, count($this->fontColors) - 1)];
		}
		else {
			$color = [
				rand(0, 255),
				rand(0, 255),
				rand(0, 255)
			];
		}
		
		return $color;
	}

	/**
	 *
	 * @return int
	 */
	protected function angle() {
		return rand((-1 * $this->angle), $this->angle);
	}

	/**
	 *
	 * @return \Intervention\Image\Image
	 */
	protected function lines() {
		for ($i = 0; $i <= $this->lines; $i++) {
			$this->image->line(rand(0, $this->image->width()) + $i * rand(0, $this->image->height()), rand(0, $this->image->height()), rand(0, $this->image->width()), rand(0, $this->image->height()), function ($draw) {
				$draw->color($this->fontColor());
			});
		}
		return $this->image;
	}

	/**
	 *
	 * @param $value
	 * @return bool
	 */
	public function check($value) {
		$store = $this->session->get('captcha' . (Input::has('captcha_id') ? '_' . Input::get('captcha_id') : ''));
		if ($this->sensitive) {
			$value = $this->str->lower($value);
			$store = $this->str->lower($store);
		}
		
		return $this->hasher->check($value, $store);
	}

	/**
	 *
	 * @param null $config        	
	 * @return string
	 */
	public function src($config = null, $formId = false) {
		$this->configure($config, $formId);
		
		if ($this->formId) {
			return url('captcha' . ($config ? '/' . $config : '/default')) . '/' . $this->formId . '?' . $this->str->random(8);
		}
		
		return url('captcha' . ($config ? '/' . $config : '/default')) . '?' . $this->str->random(8);
	}

	/**
	 *
	 * @param null $config        	
	 * @return string
	 */
	public function img($config = null, $formId = false) {
		$this->configure($config, $formId);
		
		$html = '';
		if ($this->formId) {
			$html .= '<input name="captcha_id" type="hidden" value="' . $this->formId . '">';
		}
		
		$html .= '<img src="' . $this->src($config, $this->formId) . '" alt="captcha" />';
		
		return $html;
	}

	/**
	 *
	 * @param null $config        	
	 * @return string
	 */
	public function lazy_img($config = null, $formId = false) {
		$this->configure($config, $formId);
		
		$html = '';
		if ($this->formId) {
			$html .= '<input name="captcha_id" type="hidden" value="' . $this->formId . '">';
		}
		
		$html .= '<img class="js-lazyload" data-original="' . $this->src($config, $this->formId) . '" alt="captcha" />';
		
		return $html;
	}
}
