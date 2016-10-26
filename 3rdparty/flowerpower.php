<?php
class fpParrotAPI
{
	 /**
     * @param string $consumer_key Application consumer key for Fitbit API
     * @param string $consumer_secret Application secret
     * @param int $debug Debug mode (0/1) enables OAuth internal debug
     * @param string $user_agent User-agent to use in API calls
     * @param string $response_format Response format (json or xml) to use in API calls
     */
    public function __construct($client_id, $client_secret, $username, $password, $access_token='', $refresh_token='', $expire_time=0)
    {
    	$this->api_url = 'https://api-flower-power-pot.parrot.com/';
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
		$this->username = $username;
		$this->password = $password;
		$this->access_token=$access_token;
		$this->refresh_token=$refresh_token;
		$this->expire_time=$expire_time;
		if (time() >= $expire_time) {
   			$this->access_token = "";
			//echo "--expired--";
		}
		if ($this->access_token == '') {
			//echo "--no access token--";
			if (strlen($this->refresh_token) > 1) {
				//echo "--refresh token ok--";
		   		// on peut juste rafraichir le token
		    	$grant_type = 'refresh_token';
		    	$postdata = 'grant_type='.$grant_type.'&refresh_token='.$this->refresh_token;
				$fields=2;
			}else{
			    // 1ère utilisation aprés obtention du code
			    //echo "--refresh token nok--";
			    $grant_type = 'password';
			    $postdata = 'grant_type='.$grant_type.'&username='.urlencode($this->username).'&password='.urlencode($this->password).'&client_id='.$this->client_id.'&client_secret='.$this->client_secret;
				$fields=5;
		  	}
			$ch = curl_init();
			curl_setopt($ch,CURLOPT_URL, $this->api_url.'user/v1/authenticate');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch,CURLOPT_POST, $fields);
			curl_setopt($ch,CURLOPT_POSTFIELDS, $postdata);
			$response = curl_exec($ch);
			curl_close($ch);
			//echo 'reponse:'.$response;
			$params = json_decode($response);
			/*if ($params->error != ''){
				die("Erreur lors de l'authentification: <b>".$params->error.'</b> (grant_type = '.$grant_type.')');
			}*/
			//var_dump($params);
			// on sauvegarde l'access_token et le refresh_token pour les authentifications suivantes
			if (isset($params->refresh_token)) {
				$this->access_token=$params->access_token;
				$this->refresh_token=$params->refresh_token;
				$this->expire_time=time()+$params->expires_in;
			}else if ($this->access_token == '') {
				die("Erreur lors de l'authentification");
			}
		}
	}
	/**
     * @param string $query Type de requete
     */
    public function query($request)
    {
		//open connection
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, $this->api_url.$request."?access_token=".$this->access_token);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Authorization: Bearer ' . $this->access_token ));
		$result = curl_exec($ch);
		curl_close($ch);
		return json_decode($result);
	}

	public function getPlants()
    {
		$result=$this->query('garden/v2/configuration');
		return $result->locations;
	}

  public function getSensors()
    {
		$result=$this->query('garden/v2/configuration');
		return $result->sensors;
	}

	public function getValues()
    {
		$result=$this->query('sensor_data/garden/v1/status');
		return $result->locations;
	}

  public function getSensorsValues()
    {
		$result=$this->query('sensor_data/v6/sample/location');
		return $result->sensors;
	}
}

?>
