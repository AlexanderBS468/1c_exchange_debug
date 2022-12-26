<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
header("Content-type:text/html; charset=UTF-8");

// D7
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;
use Bitrix\Main\Application;
$request = Application::getInstance()->getContext()->getRequest();

if (!function_exists('pr')) {
	function pr($obj, $visibleForEveryone = false)
	{
		static $isAdmin = null;

		if (version_compare(PHP_VERSION, '5.4.0') >= 0)
		{
			$trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT & DEBUG_BACKTRACE_IGNORE_ARGS, 2);
		}
		else
		{
			$trace = debug_backtrace();
		}

		$trace = $trace[1];

		if (PHP_SAPI == 'cli')
		{
			echo $trace['file'] . ':' . $trace['line'] . PHP_EOL;
			print_r($obj);

			return true;
		}

		$trace['file'] = str_replace($_SERVER['DOCUMENT_ROOT'], '', $trace['file']);

		$trace['file_path'] = dirname($trace['file']);
		$trace['file_name'] = pathinfo($trace['file'], PATHINFO_BASENAME);

		if (is_null($isAdmin))
		{
			$isAdmin = $GLOBALS['USER']->IsAdmin();
		}

		if (!$isAdmin and !$visibleForEveryone)
		{
			return false;
		}

		echo '<pre style="font:normal 10pt/12pt monospace;background:#fff;color:#000;margin:10px;padding:10px;border:1px solid red;text-align:left;max-width:800px;max-height:600px;overflow:scroll">';
		echo '<a style="font:normal 10pt/12pt monospace;color:#00e;text-decoration:underline" href="/bitrix/admin/fileman_admin.php?path='
			. rawurlencode($trace['file_path']) . '" target="_blank">' . $trace['file_path'] . '</a>/';
		echo '<a style="font:normal 10pt/12pt monospace;color:#60d;text-decoration:underline" href="/bitrix/admin/fileman_file_edit.php?path='
			. rawurlencode($trace['file']) . '&full_src=Y" target="_blank">' . $trace['file_name'] . '</a>:'
			. $trace['line'] . '<br />';
		echo htmlspecialcharsEx(print_r($obj, true));
		echo '</pre>';

		return true;
	}
}

if (method_exists($request, 'getValues')) {
	//Нет такого метода в 18 версии ядра
	$arlistValues = $request->getValues();
} else {
	$arlistValues["getlistfiles"] = $request->get("getlistfiles");
	$arlistValues["path"] = $request->get("path");
}

if (isset($arlistValues['getlistfiles']) && $arlistValues['getlistfiles'] === 'Y') {
	$pathDir = $arlistValues["path"];
//'/upload/1c_catalog/Exchange_2020-08-18-debug/000000001'
//   /upload/1c_catalog0
	ToolsDebug::getImportListFiles($pathDir);
}

class ToolsDebug
{
	public static function getImportListFiles($pathDir) {
		$docRoot = Application::getDocumentRoot();

//		$filename = preg_replace("#^(/tmp/|upload/1c/webdata|/upload/)#", "", $pathDir);
		$arg = [
			'path' => $docRoot . $pathDir,
			'fileName' => $_SERVER["DOCUMENT_ROOT"]."/".COption::GetOptionString("main", "upload_dir", "upload") . '/',
			'absolute_path' => true,
		];
//		define("BX24_HOST_NAME", true);
//		$_SESSION["BX_CML2_IMPORT"]["TEMP_DIR"] = $arg["fileName"];
		$objClass = new ListImportFiles($arg);
		$files = $objClass->getJSONListFiles();
//		$files = $objClass->getListFiles();
		echo $files;
		die();
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
			self::getDirectories($arDir);
//			self::getSortList($arDir, 'dir');

			foreach($arDir as $dirItem) {
				self::getFiles($dirItem);
				self::getChildDir($dirItem);
			}
		}
	}

	protected function getDirectories(&$arDir) {
		/**
		 * @var $arDir \Bitrix\Main\IO\Directory
		 * @var $dirItem \Bitrix\Main\IO\Directory
		 */
		foreach($arDir as &$dirItem){
			if ($dirItem->isDirectory()) {
				$dirItem->dirName = $dirItem->getName();
				$this->arResulter['DIRECTORY'][] = $dirItem;
			}
		}
		unset($dirItem);
	}

	public function getParentFiles(&$arDir) {
		foreach($arDir as $keyDirItem => &$dirItem){
			if ($dirItem->isFile() && preg_match('/.*\.xml$/', $dirItem->getName())) {
				if ($this->option['absolute_path']) {
					//todo fix only "/upload/1c_catalog"
					$pattern = '/' . preg_replace('/\//', '\/', \Bitrix\Main\Application::getDocumentRoot() . '/upload/1c_catalog')  . '/';
					$this->arResulter['FILES'][] = preg_replace($pattern, '', $dirItem->getPath());
				} else {
					$this->arResulter['FILES'][] = $dirItem->getPath();
				}
				unset($arDir[$keyDirItem]);
			}
		}
	}

	public function getSortList(&$arList, $typeItem = 'file') {
		if ($typeItem === 'file') {
			usort($arList, function($a, $b) {
				return $a->fileName <=> $b->fileName;
			});
		}

		if ($typeItem === 'dir') {
			usort($arList, function($a, $b) {
				return $a->dirName <=> $b->dirName;
			});
		}
	}

	public function getListFiles() {
		return $this->arResulter["FILES"];
	}

	public function getJSONListFiles() {
		return \Bitrix\Main\Web\Json::encode($this->arResulter["FILES"]);
	}

	protected function getFiles($dirItem) {
		if ($dirItem->isFile() && preg_match('/.*\.xml$/', $dirItem->getName())) {
			if ($this->option['absolute_path']) {
				//todo fix only "/upload/1c_catalog"
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

			/**
			 * @var $arDir \Bitrix\Main\IO\Directory
			 * @var $dirItem \Bitrix\Main\IO\Directory
			 */
			foreach($arDir as $keyDirItem => &$dirItem){
				$dirItem->fileName = $dirItem->getName();
			}
			unset($dirItem);

			self::getSortList($arDir, 'file');
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
global $APPLICATION;
?>
<html>
<label for="pathfiles">Абсолютный путь</label>
<?//todo add checkboxes for xml type?>
<input type="text" name="pathfiles" id="pathfiles" placeholder="путь до файлов" size="70">
<a style="color:red;" href="javascript:getPathFiles()">импорт Всех файлов</a>
<a  href="javascript:start('import.xml')">импорт import.xml</a>
<a href="javascript:start('offers-test.xml')">импорт offers.xml</a>
<a href="javascript:start('company.xml')"> импорт company.xml</a>
<a style='color:red;' href="javascript:reset()">обнулить шаг</a>
<a style='color:red;' href="javascript:status='stop'">остановить импорт</a><hr>
<div id='main' style='display:none;width:1400;font-size:12;border:1px solid #ADC3D5; padding:5'>
	<div id="log_files"></div>
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
		load.innerHTML="<b>Загрузка</b>..." + '<img align=\'center\' width=\'30\' alt="" src="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBzdGFuZGFsb25lPSJubyI/Pgo8IURPQ1RZUEUgc3ZnIFBVQkxJQyAiLS8vVzNDLy9EVEQgU1ZHIDEuMS8vRU4iICJodHRwOi8vd3d3LnczLm9yZy9HcmFwaGljcy9TVkcvMS4xL0RURC9zdmcxMS5kdGQiPgo8c3ZnIHdpZHRoPSI0MHB4IiBoZWlnaHQ9IjQwcHgiIHZpZXdCb3g9IjAgMCA0MCA0MCIgdmVyc2lvbj0iMS4xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB4bWw6c3BhY2U9InByZXNlcnZlIiBzdHlsZT0iZmlsbC1ydWxlOmV2ZW5vZGQ7Y2xpcC1ydWxlOmV2ZW5vZGQ7c3Ryb2tlLWxpbmVqb2luOnJvdW5kO3N0cm9rZS1taXRlcmxpbWl0OjEuNDE0MjE7IiB4PSIwcHgiIHk9IjBweCI+CiAgICA8ZGVmcz4KICAgICAgICA8c3R5bGUgdHlwZT0idGV4dC9jc3MiPjwhW0NEQVRBWwogICAgICAgICAgICBALXdlYmtpdC1rZXlmcmFtZXMgc3BpbiB7CiAgICAgICAgICAgICAgZnJvbSB7CiAgICAgICAgICAgICAgICAtd2Via2l0LXRyYW5zZm9ybTogcm90YXRlKDBkZWcpCiAgICAgICAgICAgICAgfQogICAgICAgICAgICAgIHRvIHsKICAgICAgICAgICAgICAgIC13ZWJraXQtdHJhbnNmb3JtOiByb3RhdGUoLTM1OWRlZykKICAgICAgICAgICAgICB9CiAgICAgICAgICAgIH0KICAgICAgICAgICAgQGtleWZyYW1lcyBzcGluIHsKICAgICAgICAgICAgICBmcm9tIHsKICAgICAgICAgICAgICAgIHRyYW5zZm9ybTogcm90YXRlKDBkZWcpCiAgICAgICAgICAgICAgfQogICAgICAgICAgICAgIHRvIHsKICAgICAgICAgICAgICAgIHRyYW5zZm9ybTogcm90YXRlKC0zNTlkZWcpCiAgICAgICAgICAgICAgfQogICAgICAgICAgICB9CiAgICAgICAgICAgIHN2ZyB7CiAgICAgICAgICAgICAgICAtd2Via2l0LXRyYW5zZm9ybS1vcmlnaW46IDUwJSA1MCU7CiAgICAgICAgICAgICAgICAtd2Via2l0LWFuaW1hdGlvbjogc3BpbiAxLjVzIGxpbmVhciBpbmZpbml0ZTsKICAgICAgICAgICAgICAgIC13ZWJraXQtYmFja2ZhY2UtdmlzaWJpbGl0eTogaGlkZGVuOwogICAgICAgICAgICAgICAgYW5pbWF0aW9uOiBzcGluIDEuNXMgbGluZWFyIGluZmluaXRlOwogICAgICAgICAgICB9CiAgICAgICAgXV0+PC9zdHlsZT4KICAgIDwvZGVmcz4KICAgIDxnIGlkPSJvdXRlciI+CiAgICAgICAgPGc+CiAgICAgICAgICAgIDxwYXRoIGQ9Ik0yMCwwQzIyLjIwNTgsMCAyMy45OTM5LDEuNzg4MTMgMjMuOTkzOSwzLjk5MzlDMjMuOTkzOSw2LjE5OTY4IDIyLjIwNTgsNy45ODc4MSAyMCw3Ljk4NzgxQzE3Ljc5NDIsNy45ODc4MSAxNi4wMDYxLDYuMTk5NjggMTYuMDA2MSwzLjk5MzlDMTYuMDA2MSwxLjc4ODEzIDE3Ljc5NDIsMCAyMCwwWiIgc3R5bGU9ImZpbGw6YmxhY2s7Ii8+CiAgICAgICAgPC9nPgogICAgICAgIDxnPgogICAgICAgICAgICA8cGF0aCBkPSJNNS44NTc4Niw1Ljg1Nzg2QzcuNDE3NTgsNC4yOTgxNSA5Ljk0NjM4LDQuMjk4MTUgMTEuNTA2MSw1Ljg1Nzg2QzEzLjA2NTgsNy40MTc1OCAxMy4wNjU4LDkuOTQ2MzggMTEuNTA2MSwxMS41MDYxQzkuOTQ2MzgsMTMuMDY1OCA3LjQxNzU4LDEzLjA2NTggNS44NTc4NiwxMS41MDYxQzQuMjk4MTUsOS45NDYzOCA0LjI5ODE1LDcuNDE3NTggNS44NTc4Niw1Ljg1Nzg2WiIgc3R5bGU9ImZpbGw6cmdiKDIxMCwyMTAsMjEwKTsiLz4KICAgICAgICA8L2c+CiAgICAgICAgPGc+CiAgICAgICAgICAgIDxwYXRoIGQ9Ik0yMCwzMi4wMTIyQzIyLjIwNTgsMzIuMDEyMiAyMy45OTM5LDMzLjgwMDMgMjMuOTkzOSwzNi4wMDYxQzIzLjk5MzksMzguMjExOSAyMi4yMDU4LDQwIDIwLDQwQzE3Ljc5NDIsNDAgMTYuMDA2MSwzOC4yMTE5IDE2LjAwNjEsMzYuMDA2MUMxNi4wMDYxLDMzLjgwMDMgMTcuNzk0MiwzMi4wMTIyIDIwLDMyLjAxMjJaIiBzdHlsZT0iZmlsbDpyZ2IoMTMwLDEzMCwxMzApOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8Zz4KICAgICAgICAgICAgPHBhdGggZD0iTTI4LjQ5MzksMjguNDkzOUMzMC4wNTM2LDI2LjkzNDIgMzIuNTgyNCwyNi45MzQyIDM0LjE0MjEsMjguNDkzOUMzNS43MDE5LDMwLjA1MzYgMzUuNzAxOSwzMi41ODI0IDM0LjE0MjEsMzQuMTQyMUMzMi41ODI0LDM1LjcwMTkgMzAuMDUzNiwzNS43MDE5IDI4LjQ5MzksMzQuMTQyMUMyNi45MzQyLDMyLjU4MjQgMjYuOTM0MiwzMC4wNTM2IDI4LjQ5MzksMjguNDkzOVoiIHN0eWxlPSJmaWxsOnJnYigxMDEsMTAxLDEwMSk7Ii8+CiAgICAgICAgPC9nPgogICAgICAgIDxnPgogICAgICAgICAgICA8cGF0aCBkPSJNMy45OTM5LDE2LjAwNjFDNi4xOTk2OCwxNi4wMDYxIDcuOTg3ODEsMTcuNzk0MiA3Ljk4NzgxLDIwQzcuOTg3ODEsMjIuMjA1OCA2LjE5OTY4LDIzLjk5MzkgMy45OTM5LDIzLjk5MzlDMS43ODgxMywyMy45OTM5IDAsMjIuMjA1OCAwLDIwQzAsMTcuNzk0MiAxLjc4ODEzLDE2LjAwNjEgMy45OTM5LDE2LjAwNjFaIiBzdHlsZT0iZmlsbDpyZ2IoMTg3LDE4NywxODcpOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8Zz4KICAgICAgICAgICAgPHBhdGggZD0iTTUuODU3ODYsMjguNDkzOUM3LjQxNzU4LDI2LjkzNDIgOS45NDYzOCwyNi45MzQyIDExLjUwNjEsMjguNDkzOUMxMy4wNjU4LDMwLjA1MzYgMTMuMDY1OCwzMi41ODI0IDExLjUwNjEsMzQuMTQyMUM5Ljk0NjM4LDM1LjcwMTkgNy40MTc1OCwzNS43MDE5IDUuODU3ODYsMzQuMTQyMUM0LjI5ODE1LDMyLjU4MjQgNC4yOTgxNSwzMC4wNTM2IDUuODU3ODYsMjguNDkzOVoiIHN0eWxlPSJmaWxsOnJnYigxNjQsMTY0LDE2NCk7Ii8+CiAgICAgICAgPC9nPgogICAgICAgIDxnPgogICAgICAgICAgICA8cGF0aCBkPSJNMzYuMDA2MSwxNi4wMDYxQzM4LjIxMTksMTYuMDA2MSA0MCwxNy43OTQyIDQwLDIwQzQwLDIyLjIwNTggMzguMjExOSwyMy45OTM5IDM2LjAwNjEsMjMuOTkzOUMzMy44MDAzLDIzLjk5MzkgMzIuMDEyMiwyMi4yMDU4IDMyLjAxMjIsMjBDMzIuMDEyMiwxNy43OTQyIDMzLjgwMDMsMTYuMDA2MSAzNi4wMDYxLDE2LjAwNjFaIiBzdHlsZT0iZmlsbDpyZ2IoNzQsNzQsNzQpOyIvPgogICAgICAgIDwvZz4KICAgICAgICA8Zz4KICAgICAgICAgICAgPHBhdGggZD0iTTI4LjQ5MzksNS44NTc4NkMzMC4wNTM2LDQuMjk4MTUgMzIuNTgyNCw0LjI5ODE1IDM0LjE0MjEsNS44NTc4NkMzNS43MDE5LDcuNDE3NTggMzUuNzAxOSw5Ljk0NjM4IDM0LjE0MjEsMTEuNTA2MUMzMi41ODI0LDEzLjA2NTggMzAuMDUzNiwxMy4wNjU4IDI4LjQ5MzksMTEuNTA2MUMyNi45MzQyLDkuOTQ2MzggMjYuOTM0Miw3LjQxNzU4IDI4LjQ5MzksNS44NTc4NloiIHN0eWxlPSJmaWxsOnJnYig1MCw1MCw1MCk7Ii8+CiAgICAgICAgPC9nPgogICAgPC9nPgo8L3N2Zz4K" />'
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
					error_text="<em>Ошибка в процессе выгрузки</em><div style='width:1270;font-size:11;border:1px solid black;background-color:#ADC3D5;padding:5'>"+import_1c.responseText+"</div>"
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
					if (import_1c.responseText.substr(0,7)=="success") {
						callback && callback();
					}
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

	function getImportFiles(path)
	{
		var curScriptName = "<?=$APPLICATION->GetCurPage(true);?>" + "?getlistfiles=Y&path=" + path;
		var options = {
				method: "POST",
				mode: "cors",
				headers: {
					"Content-Type": "application/json",
					"Accept": "application/json",
				}
			};

		return fetch(curScriptName, options)
			.then(function(response) {
				return response.json();
			});
	}

	async function startAllFiles(path)
	{
		var logFiles = document.getElementById("log_files");
		// let block = document.createElement('li');
		let tmp;

		const files = await getImportFiles(path);
		//todo if files === null
		//todo add checker path
		//todo add ablosute path
		//todo fix /1c_catalog/1c_catalog/ import
		console.log(path);
		console.log(files);
		for (let i = 0; i < files.length; i++) {
			tmp = logFiles.innerHTML;
			logFiles.innerHTML = tmp + "<b>Импортирован файл "+files[i]+"</b><hr>";
			// block.innerHTML = "<b>Файл "+files[i]+"</b><hr>";
			// logFiles.appendChild(block);
			// await startPromise(files[i]);
		}
	}

	function getPathFiles()
	{
		var input = document.getElementById("pathfiles");
		if (input.value != '') {
			startAllFiles(input.value);
		} else {
			alert('Не указан путь!');
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