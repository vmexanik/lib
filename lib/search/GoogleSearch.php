<?php
class GoogleSearch
{
    public $result;
    protected $query;
    protected $host;
    protected $page = 0;

    public $error = null;
	//-----------------------------------------------------------------------------------------------
	public function Init()
	{
	 	$this->error = null;
	 	return $this;
	}
	//-----------------------------------------------------------------------------------------------
	public function query($query)
	{
	    $this->query = $query;
	 	return $this;
	}
	//-----------------------------------------------------------------------------------------------
	public function getQuery()
	{
	    return $this->query;
	}
	//-----------------------------------------------------------------------------------------------
	public function page($page)
	{
	    $this->page = $page;
	 	return $this;
	}
	//-----------------------------------------------------------------------------------------------
	public function getPage()
	{
	    return $this->page;
	}
	//-----------------------------------------------------------------------------------------------
	public function host($host)
	{
	    $this->host = $host;
	 	return $this;
	}
	//-----------------------------------------------------------------------------------------------
	public function getHost()
	{
	    return $this->host;
	}
	//-----------------------------------------------------------------------------------------------
	public function request()
	{
		if (empty($this->query)) {
			$this->error = 'Query is empty';
			return;
		}

		$query = iconv("Windows-1251", "UTF-8", $this->query);
		$url = "http://ajax.googleapis.com/ajax/services/search/web?v=1.0&hl=ru&q=".urlencode($query)."&rsz=large&start=".$this->page;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_NOBODY, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		//curl_setopt($ch, CURLOPT_REFERER, "http://www.mysite.com/index.html");
		curl_setopt($ch, CURLOPT_URL, $url);
		$body=curl_exec($ch);
		curl_close($ch);

		//$this->result = json_decode($body); for php version > 5.2
		require_once(SERVER_PATH.'/lib/Pear/JSON.php');
		$oJson  = new Services_JSON();
		$this->result = $oJson->decode($body);
		unset($oJson);

        $this->checkErrors();

        return $this;
	}
	//-----------------------------------------------------------------------------------------------
	protected function checkErrors()
	{
	}
	//-----------------------------------------------------------------------------------------------

}