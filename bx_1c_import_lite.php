<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
header("Content-type:text/html; charset=UTF-8");

// D7
use Bitrix\Main\Application;
$request = Application::getInstance()->getContext()->getRequest();

$arlistValues = $request->getValues();

if (isset($arlistValues['getlistfiles']) && $arlistValues['getlistfiles'] === 'Y') {
	$pathDir = '/upload/1c_catalog/Reports/Exchange_(1234)2020-08-24/000000001';

	ToolsDebug::getListFiles($pathDir);
}

class ToolsDebug
{
	public static function getListFiles($pathDir) {
		$docRoot = Application::getDocumentRoot();

		$arg = [
			'path' => $docRoot . $pathDir,
			'absolute_path' => true,
		];
		$objClass = new ListImportFiles($arg);
		$files = $objClass->getListFiles();
		$test = \Bitrix\Main\Web\Json::encode($files);
		echo $test;
	}

}

class ListImportFiles {
	private $arResulter = [];
	private $option = [];

	public function __construct($arg) {
		$this->option = [
			'path' => $arg["path"],
			'absolute_path' => $arg["absolute_path"],
		];

		$dir = new Directory($this->option['path']);
		if ($dir->isExists()) {
			$arDir = $dir->getChildren();
			self::getParentFiles($arDir);
			foreach($arDir as $dirItem){
				self::getFiles($dirItem);
				if ($dirItem->isDirectory()) {
					$this->arResulter['DIRECTORY'][] = $dirItem;
					self::getChildDir($dirItem);
				}
			}
		};
	}

	public function getParentFiles(&$arDir) {
		foreach($arDir as $keyDirItem => &$dirItem){
			if ($dirItem->isFile() && preg_match('/.*\.xml$/', $dirItem->getName())) {
				if ($this->option['absolute_path']) {
					$pattern = '/' . preg_replace('/\//', '\/', \Bitrix\Main\Application::getDocumentRoot() . '/upload/1c_catalog')  . '/';
					$this->arResulter['FILES'][] = preg_replace($pattern, '', $dirItem->getPath());
				} else {
					$this->arResulter['FILES'][] = $dirItem->getPath();
				}
				unset($arDir[$keyDirItem]);
			}
		}
	}

	public function getListFiles() {
		return $this->arResulter["FILES"];
	}

	protected function getFiles($dirItem) {
		if ($dirItem->isFile() && preg_match('/.*\.xml$/', $dirItem->getName())) {
			if ($this->option['absolute_path']) {
				$pattern = '/' . preg_replace('/\//', '\/', \Bitrix\Main\Application::getDocumentRoot() . '/upload/1c_catalog')  . '/';
				$this->arResulter['FILES'][] = preg_replace($pattern, '', $dirItem->getPath());
			} else {
				$this->arResulter['FILES'][] = $dirItem->getPath();
			}
		}
	}

	protected function getChildDir($dirItem) {
		$dirChild = new Directory($dirItem->getPath());
		if ($dirChild->isExists()) {
			$arDir = $dirChild->getChildren();
			foreach($arDir as $dirItem){
				self::getFiles($dirItem);
				if ($dirItem->isDirectory()) {
					$this->arResulter['DIRECTORY'][] = $dirItem;
					self::getChildDir($dirItem);
				}
			}
		}
	}
}

$_SESSION["BX_CML2_IMPORT"]["NS"]["STEP"]=0;
?>
<html>
<a  href="javascript:start('import.xml')">импорт import.xml</a>
<a href="javascript:start('offers.xml')">импорт offers.xml</a>
<a href="javascript:start('company.xml')"> импорт company.xml</a>
<a style='color:red;' href="javascript:reset()">обнулить шаг</a>
<a style='color:red;' href="javascript:status='stop'">остановить импорт</a><hr>
<div id='main' style='display:none;width:400;font-size:12;border:1px solid #ADC3D5; padding:5'>
	<div id="log"></div>
	<div align=right id="load"></div>
</div>
<div id="timer"></div>

<script>
	var
		log=document.getElementById("log");
	timer=document.getElementById("timer");
	load=document.getElementById("load");
	var zup_import=false;
	//переменные таймера
	m_second=0;
	seconds=0;
	minute=0;
	//переменные импорта
	i=1;
	a='';
	proccess=true;
	status="continue";


	function createHttpRequest()

	{
		var httpRequest;
		if (window.XMLHttpRequest)
			httpRequest = new XMLHttpRequest();
		else if (window.ActiveXObject) {
			try {
				httpRequest = new ActiveXObject('Msxml2.XMLHTTP');
			} catch (e){}
			try {
				httpRequest = new ActiveXObject('Microsoft.XMLHTTP');
			} catch (e){}
		}
		return httpRequest;

	}

	function start(file, callback)
	{
		document.getElementById("main").style.display='block';
		load.innerHTML="<b>Загрузка</b>...<img align='center'                 src='http://gifanimation.ru/images/ludi/17_3.gif' width='30'/>"
		i=1;
		a="";
		m_second=0;
		seconds=0;
		proccess=true;
		start_timer();
		timer.innerHTML="";
		if (file=="company.xml") {zup_import=true;}
		log.innerHTML="<b>Импорт "+file+"</b><hr>";
		query_1c(file, callback)
	}

	function query_1c(file, callback)
	{
		var import_1c=createHttpRequest();
		if (zup_import==true)
		{
			r="/bitrix/admin/1c_intranet.php?type=catalog&mode=import&filename="+file;
		} else{r="/bitrix/admin/1c_exchange.php?type=catalog&mode=import&filename="+file;}
		load.style.display="block";
		import_1c.open("GET", r, true);
		import_1c.onreadystatechange = function()
		{
			a=log.innerHTML;
			if (import_1c.readyState == 4 && import_1c.status == 0)
			{
				error_text="<em>Ошибка в процессе выгрузки</em><div style='width:270;font-size:11;border:1px solid             black;background-color:#ADC3D5;padding:5'>Сервер упал и не вернул заголовков.</div>"
				log.innerHTML=a+"Шаг "+i+": "+error_text;
				load.style.display="none";
				status="continue"
				alert("Import is crashed!");
			}

			if (import_1c.readyState == 4 && import_1c.status == 200)
			{
				if ((import_1c.responseText.substr(0,8 )!="progress")&&(import_1c.responseText.substr(0,7)!="success"))
				{
					error_text="<em>Ошибка в процессе выгрузки</em><div style='width:270;font-size:11;border:1px solid black;background-color:#ADC3D5;padding:5'>"+import_1c.responseText+"</div>"
					log.innerHTML=a+"Шаг "+i+": "+error_text;
					status="error";
				}
				else
				{
					n=import_1c.responseText.lastIndexOf('s')+1;
					l=import_1c.responseText.length;
					mess=import_1c.responseText.substr(n,l);
					log.innerHTML=a+"Шаг "+i+": "+mess+" ("+seconds+" сек.)"+"<br>";
					seconds=0;
					load.style.display="none";
					i++;
				}
				if ((import_1c.responseText.substr(0,7)=="success")||(status=="error")||(status=="stop"))
				{
					load.style.display="none";
					status="continue"
					proccess=false;
					timer.innerHTML="<hr>Время выгрузки: <b>"+minute+" мин. "+m_second+" сек.</b>";
					callback && callback();
				}
				else
				{
					query_1c(file, callback);
				}
			}



		};
		import_1c.send(null);
	}
	function start_timer()
	{
		if (m_second==60)
		{
			m_second=0;
			minute+=1;
		}
		if (proccess==true)
		{
			seconds+=1;
			m_second+=1;

			setTimeout("start_timer()",1000);
		}
	}

	function reset()
	{
		var rest=createHttpRequest();
		q="bx_1c_import_lite.php";
		rest.open("GET", q, true);
		rest.onreadystatechange=function()
		{
			if (rest.readyState == 4 && rest.status == 200)
				alert("Шаг импорта обнулён!");
		}

		rest.send(null);

	}

	function startPromise(file)
	{
		return new Promise(function(resolve, reject) {
			start(file, resolve);
		});
	}

	function getImportFiles()
	{
		return fetch('/debug.php')
			.then(function(response) {
				return response.json();
			});
	}

	async function startAllFiles()
	{
		const files = await getImportFiles();

		for (let i = 0; i < files.length; i++) {
			await startPromise(files[i]);
		}
	}


</script>

<style>
	a {
		text-decoration: none;
		color:#36648B;
		background:#FFEFD5;
		font-size:13;
		padding:5;
		font-family:Arial;
		border:1px dashed #ADC3D5;
	}

</style>
</html>