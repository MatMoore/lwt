<?php

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' ); 

$tl=$_GET["tl"];
$sl=$_GET["sl"];
$text=$_GET["text"];

function array_iunique($array) {
	return array_intersect_key(
		$array,
		array_unique(array_map("StrToLower",$array))
	);
}

class GoogleTranslate {
	public $lastResult = "";
	private $langFrom;
	private $langTo;
	private static $urlFormat = "http://translate.google.com/translate_a/t?client=t&text=%s&hl=en&sl=%s&tl=%s&ie=UTF-8&oe=UTF-8&multires=1&otf=1&pc=1&trs=1&ssel=3&tsel=6&sc=1";
	public function setLangFrom($lang) {
		$this->langFrom = $lang;
		return $this;
	}
	public function setLangTo($lang) {
		$this->langTo = $lang;
		return $this;
	}
	public function __construct($from, $to) {
		$this->setLangFrom($from)->setLangTo($to);
	}
	public static final function makeCurl($url, $cookieSet = false) {
		if(is_callable('curl_init')){
			if (!$cookieSet) {
				$cookie = tempnam(sys_get_temp_dir(), "CURLCOOKIE");
				$curl = curl_init($url);
				curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				$output = curl_exec($curl);
				unset($curl);
				unlink($cookie);
				return $output;
			}
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$output = curl_exec($curl);
			unset($curl);
		}
		else{
			$ctx = stream_context_create(array("http"=>array("method"=>"GET","header"=>"Referer: \r\n")));
			$output = file_get_contents($url, false, $ctx);
		}
		return $output;
	}

	public function translate($string) {
		return $this->lastResult = self::staticTranslate($string, $this->langFrom, $this->langTo);
	}

	public static function staticTranslate($string, $from, $to) {
		$url = sprintf(self::$urlFormat, rawurlencode($string), $from, $to);
		$result = preg_replace('!([[,])(?=,)!', '$1[]$2', self::makeCurl($url));
		$resultArray = json_decode($result, true);
		$finalResult = "";
		if (!empty($resultArray[0])) {
			foreach ($resultArray[0] as $results) {
				$finalResult[] = $results[0];
			}
			if (!empty($resultArray[1])) {
				foreach ($resultArray[1] as $v) {
					foreach ($v[1] as $results) {
						$finalResult[] = $results;
					}
				}
			}
			if (!empty($resultArray[5])) {
				foreach ($resultArray[5] as $v) {
					if($v[0]==$string){
						foreach ($v[2] as $results) {
							$finalResult[] = $results[0];
						}
					}
				}
			}
			return array_iunique($finalResult);
		}
		return false;
	}
}

header('Pragma: no-cache');
header('Expires: 0');

$file = GoogleTranslate::staticTranslate($text,$sl,$tl);

$gglink = makeOpenDictStr(createTheDictLink('*http://translate.google.com/#' . $sl . '/' . $tl . '/',$text), " more...");

pagestart_nobody('');
if (!isset($_GET['sent'])){
	echo '<h3>Google Translate:  &nbsp; <span class="red2" id="textToSpeak" style="cursor:pointer" title="Click on expression for pronounciation" onclick="var txt = $(\'#textToSpeak\').text();var audio = new Audio();audio.src =\'tts.php?tl=' . $sl . '&q=\' + txt;audio.play();">' . tohtml($text) . '</span></h3>';
	echo '<p>(Click on <img src="icn/tick-button.png" title="Choose" alt="Choose" /> to copy word(s) into above term)<br />&nbsp;</p>';
?>
<script type="text/javascript">
//<![CDATA[
function addTranslation (s) {
	var w = window.parent.frames['ro'];
	if (typeof w == 'undefined') w = window.opener;
	if (typeof w == 'undefined') {
		alert ('Translation can not be copied!');
		return;
	}
	var c = w.document.forms[0].WoTranslation;
	if (typeof c != 'object') {
		alert ('Translation can not be copied!');
		return;
	}
	var oldValue = c.value;
	if (oldValue.trim() == '') {
		c.value = s;
		w.makeDirty();
	}
	else {
		if (oldValue.indexOf(s) == -1) {
			c.value = oldValue + ' / ' + s;
			w.makeDirty();
		}
		else {
			if (confirm('"' + s + '" seems already to exist as a translation.\nInsert anyway?')) { 
				c.value = oldValue + ' / ' + s;
				w.makeDirty();
			}
		}
	}
}
//]]>
</script>
<?php
	foreach($file as $word){
		echo '<span class="click" onclick="addTranslation(' . prepare_textdata_js($word) . ');"><img src="icn/tick-button.png" title="Copy" alt="Copy" /> &nbsp; ' . $word . '</span><br />';
	}
	if (!empty($file)) {
		echo '<br />' . $gglink . "\n";
	}
}
else echo '<h3>Sentence:</h3><span class="red2">' . tohtml($text) . '</span><br><br><h3>Google Translate:</h3>' . $gglink . '<br><table class="tab2" cellspacing="0" cellpadding="0"><tr><td class="td1bot center" style="border-top-right-radius:inherit;" colspan="1">'. $file[0] . '</td></tr></table>';
pageend();

?>