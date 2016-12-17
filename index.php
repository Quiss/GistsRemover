<?php

class GistsRemover
{

	protected $baseUrl = 'https://api.github.com';

	protected $basicAuth = [
		'userName' => 'your_usename',
		'password' => 'your_password'
	];

	protected $removedGistCounter = 0;

	protected $removeTypeGist = 'all'; // Options: public, private, all

	protected $privateIsEmpty = false;

	public function curlWrapper($requestUri = '', $requestType = 'GET', $requestData = []) {
		reSearchPrivateGist:
		$requestUri = str_replace(':userName', $this->basicAuth['userName'], $requestUri);

		$process = curl_init($this->baseUrl.$requestUri);
		curl_setopt($process,CURLOPT_USERAGENT,$this->basicAuth['userName']);
		curl_setopt($process, CURLOPT_USERPWD, $this->basicAuth['userName'] . ":" . $this->basicAuth['password']);
		curl_setopt($process, CURLOPT_TIMEOUT, 30);

		if($requestType == 'POST') {
			curl_setopt($process, CURLOPT_POST, 1);
		} elseif($requestType == 'DELETE') {
			curl_setopt($process, CURLOPT_CUSTOMREQUEST, "DELETE");
		}

		if(count($requestData) > 0) {
			curl_setopt($process, CURLOPT_POSTFIELDS, $requestData);
		}
		
		curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
		$return = curl_exec($process);

		if($requestType == 'DELETE') {
			$this->removedGistCounter++;
			return 'ok';
		}
			
		$decodedData = json_decode($return, true);

		$removedIds = [];

		foreach ($decodedData as $key => $value) {
			if(($this->removeTypeGist == 'private' && $value['public'] != true) || ($this->removeTypeGist == 'public' && $value['public'] != false) || $this->removeTypeGist == 'all') {
				$removedIds[] = $value['id'];
			}	
		}
		curl_close($process);

		if(count($removedIds) > 0) { 
			foreach ($removedIds as $gistId) {
				echo ( $this->removedGistCounter + 1 ) . ". DELETING: http://gist.github.com/". $this->basicAuth['userName'] . "/" . $gistId."\r\n";

				self::curlWrapper('/gists/'.$gistId, 'DELETE');
			}

			goto reSearchPrivateGist;
		} else {
			$this->privateIsEmpty = true;
			print_r("All private gists deleted.");
		}
	}
}

$gistsRemover = new GistsRemover();
$gistsRemover->curlWrapper('/users/:userName/gists');




