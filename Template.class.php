<?php
/**
 * This is a basic template file that can help you
 * build an easy html file and load content into the file.
 * For questions you can always send an email.
 *
 * BEGIN OF EXAMPLE
 *
 * Creating a instance of The Template class
 * $Template = new Template('test.html');
 *
 * Adding a replace tag with it's replace value into the template file
 * Template::addReplaceTag('content', 'some value for content');
 *
 * Output the html file
 * $Template->Output();
 *
 * END OF EXAMPLE
 */


/**
 * Template
 * @author Ronald van Zon <ronald.vanzon1984@gmail.com>
 */
class Template {
	/** @var $html					- This wil store the end result of your html with the replaced tags */
	private static $html;

	/** @var $template				- This wil store your template with the tags still intact*/
	private static $template;

	/** @var array $tags			- This wil store all the tags you have used inside your html file */
	private static $tags = array();

	/** @var array $defaultFiles	- This wil search within a path for the type of files you have defined
	 *  Example: array('js/*.js') 	- Will find all the javascript files for you and load them into the var $js
	 *  Example: array('css/*.css')	- Will find all the stylesheet files for you and load them into the var $css
	 */
	protected static $defaultFiles = array();

	/** @var array $js				- This wil store all the javascript files that you call for you template*/
	protected static $js = array();

	/** @var array $css				- This will store all the stylesheet files that you call for you template */
	protected static $css = array();

	/** @var string $templateFile	- Give a path to your html file with the tags inside it.*/
	protected static $templateFile = '';

	/** @var bool $checkForVar		- If you have content where you use tokens in you want to replace you should set this to true
	 *  Example: <a href="{uri}/something.html">something</a> within your content will also be replaced
	 */
	protected static $checkForVar = false;

	/** @var array $customTags		- This var wil store all of your tags with there replacement value */
	protected static $customTags = array();

	/** @var string $prefix			- This will be added before urls */
	protected static $prefix = '';

	/** @var bool $keepWhiteSpace	- When kept to false all the whitespaces will be removed before returning the html */
	protected static $keepWhiteSpace = false;

	/**
	 * By giving the options you can change the protected variables
	 * Example: array('checkForVar' => true, 'defaultFiles' => array('js/*.js', 'css/*.css))
	 * @param $templateFile
	 * @param array $options
	 */
	public function __construct($templateFile, array $options = NULL) {
		self::$templateFile = $templateFile;

		if (empty(self::$templateFile)) {
			trigger_error('You must give a template file');
			return false;
		}

		if (isset($options)) {
			foreach ($options as $key => $value) {
				if (isset(self::$$key)) {
					self::$$key = $value;
				}
			}
		}

		if (!empty(self::$defaultFiles)) {
			$this->loadDefaultFiles();
		}
	}

	/**
	 * This will return the final version of your html file
	 * if you have $checkForVar set to true he wil check if there are still tags left and repeat the run
	 * until he has replaced all the tags
	 *
	 * NOTE: Don't use the same tag within a it self, this will give you a loop!
	 * NOTE: Using checkForVar will slow down your website depending on the nesting amount of your tags
	 *
	 * @return $html
	 */
	public function Output($return = false) {
		if($this->getTemplate() && $this->getTags()) {
			self::$html = self::$template;
			$this->buildPage();
		}

		if (self::$checkForVar == true) {
			$tags = preg_replace('~{([\w-_]+)}~', '$1', self::$tags);

			do {
				if (!preg_match("~{(" . implode('|', $tags) . ")}~", self::$html)) {
					continue;
				}
				
				$this->buildPage();
				
			} while(false);
		}

		if ($return == false) {
			echo self::$html;
		} else {
			return self::$html;
		}
	}

	/**
	 * This will allow the user to add tags that will be replaced when they are in the html file
	 * When a user adds { } within the tag this will be filterd out of the tag
	 * 
	 * @param $token
	 * @param $value
	 * @return void
	 */
	public static function addReplaceTag($tag, $value){
		if(preg_match('~{([\w-_]+)}~', $tag, $matches)) {
			$tag = $matches[1];
		}

		self::$customTags['{' . $tag . '}'] = $value;
	}

	/**
	 * This will allow user to add a array of tags and replacements that will be used to replace
	 * the tags in the html file.
	 *
	 * @param array $tags
	 * @return void
	 */
	public static function addMultipleTags(array $tags) {
		foreach ($tags as $key => $value) {
			self::addReplaceTag($key, $value);
		}
	}

	/**
	 * This will add a path to the array css variable.
	 * This can be used when you have a file that uses a special stylesheet file
	 *
	 * @param $path
	 * @return void
	 */
	public static function addCSS($path) {
		self::$css[] = $path;
	}

	/**
	 * This will add a path to the array js variable.
	 * This can be used when you have a file that uses a special javascript file
	 *
	 * @param $path
	 * @return void
	 */
	public static function addJS($path) {
		self::$js[] = $path;
	}

	/**
	 * All Javascript files will be added with a script tag around it.
	 *
	 * @return string with all the javascript files within there script tag.
	 * Example: <script src="js/test.js"></script><script src="js/jquery.js"></script>
	 */
	public static function getJs() {
		self::$js	= array_unique(self::$js);
		$js = '';

		foreach (self::$js as $value) {
			$js .= '<script src="' . $value . '"></script>';
		}

		return $js;
	}

	/**
	 * All StyleSheet files will be added with a link tag around it.
	 *
	 * @return string with all the StyleSheet files within there link tag.
	 * Example: <link href="css/test.css" /><link href="css/jquery-ui.css">
	 */
	public static function getCss() {
		self::$css	= array_unique(self::$css);

		$css = '';
		foreach (self::$css as $value) {
			$css .= '<link rel="stylesheet" type="text/css" href="' . $value . '" />';
		}

		return $css;
	}
	
	/**
	 * If you have given a path for the loadDefaultFiles function it will check if the file
	 * exists and add it to the correct variable (css or js) all other files are ignored
	 *
	 * @return void
	 * Example: array('jquery/jquery.js', 'jquery/*.js')
	 *
	 * NOTE: getJs and getCss will filter the array for unique values preventing files be called more then once
	 */
	private function loadDefaultFiles() {
		$files = array();
		$load = self::$defaultFiles;

		foreach ($load as $value) {
			if (glob($value)) {
				$files = array_merge($files, glob($value));
			}
		}

		foreach ($files as $value) {
			preg_match('~\.(js|css)$~', $value, $matches);

			switch($matches[1]) {
				case 'js':
					$this->addJS(self::$prefix . $value);
					break;

				case 'css':
					$this->addCSS(self::$prefix . $value);
					break;
			}
		}
	}

	/**
	 * This function will replace all the tags for there values.
	 * After that the function will remove empty lines
	 * if $keepWhiteSpace is false it will remove all the whitespace between tags
	 * this will increase the load time of your html page
	 *
	 * @return bool
	 *
	 * NOTE: settings $keepWhiteSpace to true can be useful during debugging.
	 */
	private function buildPage() {
		if (empty(self::$customTags)) {
			trigger_error('there are no tokens that can be replaced');
			return false;
		}

		foreach(self::$tags as $value) {
			$replacer = isset(self::$customTags[$value]) ? self::$customTags[$value] : '';
			self::$html = preg_replace("~$value~", $replacer, self::$html);
		}

		self::$html = preg_replace("~(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+~", "\n", self::$html);

		if (self::$keepWhiteSpace == false) {
			self::$html = preg_replace('~\s+~', ' ', self::$html);
		}
	}

	/**
	 * This will check if the template file exists.
	 * If the file does not exists it will return a error message
	 * @return bool
	 */
	private function getTemplate() {
		$file = glob(self::$templateFile);

		if ($file) {
			self::$template = file_get_contents($file[0]);
			return true;
		} else {
			trigger_error('No template file was found on the given path:' . self::$templateFile);
		}

		return false;
	}

	/**
	 * This function will search the content of you html file for all the tags that can be replaced.
	 * If there are no tags withing the html file, the function will return an error message.
	 *
	 * @return bool
	 */
	private function getTags() {
		preg_match_all("~{[\w-]+}~ix", self::$template, $matches);
		self::$tags = array_unique($matches[0]);
		sort(self::$tags);

		if (empty(self::$tags)) {
			trigger_error('There are no tags in the template');
			return false;
		} else {
			return true;
		}
	}
}