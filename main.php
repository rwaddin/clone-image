<?php
# @uses 
# php main.php -> getting file name
# php main.php download -> download file base on folder data

if (php_sapi_name() != "cli") {
	echo "use a terminal";exit;
}
$App = new Main($argv);
$App->execute();

class Main
{

	public function __construct($params = false)
	{
		$this->download = isset($params[1]) && $params[1] == "download";
		$this->dirName  = "data";
		$this->baseURL = "https://svgsilh.com";
		// print_r($params);
		// echo $this->download;
		// exit();
	}


	public function execute()
	{
		if ($this->download) {
			$jsonFile = array_diff(scandir($this->dirName), array('.', '..'));
			$reIndex  = array_values($jsonFile);
			foreach($reIndex as $json){
				$fileName = file_get_contents($this->dirName."/".$json);
				$fileName = json_decode($fileName);

				$dirImage = "downloads/".str_replace(".json","",$json);
				
				if (!file_exists($dirImage)) {
					mkdir($dirImage, 0777, true);
				}
				foreach ($fileName as $key => $imageNameArr) {
					foreach ($imageNameArr as $imageName) {
						$this->download($imageName, $dirImage);
					}
				}
			}
		}else{
			// $categoryName = $this->getCategory();
			$categoryName = ["round"];
			foreach($categoryName as $item){
				$this->getLinkImage($item);
			}
		}
		
	}

	private function download($fileName, $dirStorage = "")
	{
		$fileName = $fileName.".svg";
		$filePath = "/svg/".$fileName;
		$fullPath = $this->baseURL.$filePath;
		echo "Downloading file {$fullPath}\n";

		$image = file_get_contents($fullPath);
		file_put_contents($dirStorage."/".$fileName, $image);
	}

	private function getCategory($link = false)
	{
		$home = $this->cURL();
		$explode = explode('<h3 class="mt-3 mb-3">Categories</h3>', $home);
		$explode1 = explode('<div class="overlay">', $explode[1]);
		preg_match_all("/(\/tag.*)(html)/", $explode1[0], $pathURL);
	
		if ($link) {
			return $pathURL[0];
		}

		$category = [];
		foreach ($pathURL[0] as $row) {
			$preReplace = str_replace("/tag/", "", $row);
			$sufReplace = str_replace("-1.html", "", $preReplace);
			$category[] = $sufReplace;
		}
		return $category;
	}	

	private function getLinkImage($category)
	{
		$result = [];
		$index = 1;
		while (true) {
			$path = "/tag/{$category}-{$index}.html";
			$fileName = $this->getFileName($path);
			if ($fileName) {
				$result[] = $fileName;
			}else{
				break;
			}
			$index++;
		}
		file_put_contents($this->dirName."/{$category}.json", json_encode($result,JSON_PRETTY_PRINT));
		return $result;
	}

	private function getFileName($path)
	{
		//$path = "/tag/world-1.html";
		$page = $this->cURL($path);
		$result = [];
		if ($page) {
			$exp = explode('<div class="card-columns">', $page);
			$exp1 = explode('<nav>', $exp[1]);
			$output  = preg_split("#\n\s*\n#Uis", $exp1[0]);

			foreach($output as $row){
				preg_match_all('/(img class.*)(\/\d+)/', $row, $out);
				if (isset($out[2][0]) && !empty($out[2][0])) {
					$fileName = str_replace("/","",$out[2][0]);
					$result[] = $fileName;
				}
			}
			return $result;
		}
		return false;
	}

	private function cURL($url = "")
	{
		$ch = curl_init();

		$baseURL = $this->baseURL;
		echo "cURL : {$baseURL}{$url}\n";
		curl_setopt($ch, CURLOPT_URL, $baseURL.$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$result = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if (curl_errno($ch)) {
		    echo 'Error:' . curl_error($ch);
		}
		curl_close($ch);

		if ($httpCode != 200) {
			return false;
		}
		return $result;
	}
}
