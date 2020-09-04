<?php
/**
 * Class ParcerUrlContent
 *
 * ParcerUrlContent class
 *
 * @author   Anatoly Udod <AnatolyUd@gmail.com>
 * @created  2009
 */
class ParcerUrlContent
{
    public $sError = null;

    protected $iTimeout;
    protected $iLineLimit=99999;
    protected $iMaxLineLength=1024;
    protected $sDefaultCharset='Windows-1251';
    protected $sCharset;

    protected $sUrl;
    protected $sTitle;
    protected $sDescription;
    protected $sKeywords;
    protected $sText;
    protected $sContent;

	//-----------------------------------------------------------------------------------------------
    public function __construct()
    {
    }
	//-----------------------------------------------------------------------------------------------
    public function Init()
    {
	    $this->sCharset = '';
	    $this->sError = '';
	    $this->sUrl = '';
	    $this->sTitle = '';
	    $this->sDescription = '';
	    $this->sKeywords = '';
	    $this->sText = '';
	    $this->sContent = '';
	   	return $this;
    }
	//-----------------------------------------------------------------------------------------------
	public function DefaultCharset($sCharset)
	{
	   $this->sDefaultCharset = $sCharset;
	   return $this;
	}
	//-----------------------------------------------------------------------------------------------
	public function Charset($sCharset)
	{
	   $this->sCharset = $sCharset;
	   return $this;
	}
	//-----------------------------------------------------------------------------------------------
	public function Timeout($iTimeout)
	{
	   $this->iTimeout = $iTimeout;
	   return $this;
	}
	//-----------------------------------------------------------------------------------------------
	public function Url($sUrl)
	{
	   $this->sUrl = $sUrl;
	   return $this;
	}
	//-----------------------------------------------------------------------------------------------
	public function Content($sContent)
	{
	   $this->sContent = $sContent;
	   return $this;
	}
	//-----------------------------------------------------------------------------------------------
	public function LineLimit($iLineLimit)
	{
	   $this->iLineLimit = $iLineLimit;
	   return $this;
	}
	//-----------------------------------------------------------------------------------------------
	public function GetText()
	{
		if (empty($this->sText)) {
			$this->sText = addslashes(trim(strip_tags($this->sContent)));
			if ($this->sCharset && (strcasecmp($this->sCharset,$this->sDefaultCharset)!=0)) {
				$this->sText = iconv($this->sCharset, $this->sDefaultCharset, $this->sText);
			}
		}
		return $this->sText;
	}
	//-----------------------------------------------------------------------------------------------
	public function GetTitle()
	{
		if (empty($this->sTitle)) {
            if (preg_match('{<TITLE>(.*?)</TITLE>}is', $this->sContent, $sPattern)) {
                $this->sTitle = $sPattern[1];
				if ($this->sCharset && (strcasecmp($this->sCharset,$this->sDefaultCharset)!=0)) {
					$this->sTitle = trim(iconv($this->sCharset, $this->sDefaultCharset, $this->sTitle));
				}
            }
		}
		return $this->sTitle;
	}
	//-----------------------------------------------------------------------------------------------
	public function GetCharset()
	{
		$sCharsetText = '';
		if (empty($this->sCharset)) {
            if (preg_match('{<META +(?:(?:http-equiv|name)=(?:"content-type"|\'content-type\'|content-type) +content|description)="(.*?)" */?>}is', $this->sContent, $sPattern)) {
                $sCharsetText = $sPattern[1];
            }
            else
            if (preg_match('{<META +content="([^".]*?)" +name=(?:"content-type"|\'content-type\'|content-type) */?>}is', $this->sContent, $sPattern)) {
                $sCharsetText = $sPattern[1];
            }
            if (stripos($sCharsetText,'1251')!==false) {
            	$this->sCharset = 'windows-1251';
            }
            else
            if (stripos($sCharsetText,'utf-8')!==false) {
            	$this->sCharset = 'utf-8';
            }
		}
		return $this->sCharset;
	}
	//-----------------------------------------------------------------------------------------------
	public function GetDescription()
	{
		if (empty($this->sDescription)) {
            if (preg_match('{<META +(?:(?:http-equiv|name)=(?:"description"|\'description\'|description) +content|description)="(.*?)" */?>}is', $this->sContent, $sPattern)) {
                $this->sDescription = $sPattern[1];
            }
            else
            if (preg_match('{<META +content="([^".]*?)" +name=(?:"description"|\'description\'|description) */?>}is', $this->sContent, $sPattern)) {
                $this->sDescription = $sPattern[1];
            }
            if (!empty($this->sDescription)) {
				if ($this->sCharset && (strcasecmp($this->sCharset,$this->sDefaultCharset)!=0)) {
					$this->sDescription = iconv($this->sCharset, $this->sDefaultCharset, $this->sDescription);
				}
            }
		}
		return $this->sDescription;
	}
	//-----------------------------------------------------------------------------------------------
	public function GetKeywords()
	{
		if (empty($this->sKeywords)) {
            if (preg_match('{<META +(?:(?:http-equiv|name)=(?:"keywords"|\'keywords\'|keywords) +content|keywords)="(.*?)" */?>}is', $this->sContent, $sPattern)) {
                $this->sKeywords = $sPattern[1];
            }
            else
            if (preg_match('{<META +content="([^".]*?)" +name=(?:"keywords"|\'keywords\'|keywords) */?>}is', $this->sContent, $sPattern)) {
                $this->sKeywords = $sPattern[1];
            }
            if (!empty($this->sKeywords)) {
            	if ($this->sCharset && (strcasecmp($this->sCharset,$this->sDefaultCharset)!=0)) {
					$this->sKeywords = iconv($this->sCharset, $this->sDefaultCharset, $this->sKeywords);
				}
            }
		}
		return $this->sKeywords;
	}
	//-----------------------------------------------------------------------------------------------
	 protected function RequestSocket()
	 { //$this->sUrl вида "www.example.com"
        $fp = fsockopen($this->sUrl, 80, $iErrno, $sError, 30);

        if ($fp) {
			stream_set_blocking($fp, 0);
	        fputs($fp, "GET / HTTP/1.1\r\n");
	        fputs($fp, "Host: $this->sUrl\r\n");
	        fputs($fp, "Connection: close\r\n");
	        fputs($fp, "\r\n");

	        $iLine=0;
	        while (!feof($fp)&&($iLine < $this->iLineLimit)) {
	            $this->sContent .= fgets($fp, $this->iMaxLineLength);
	            $iLine += 1;
	        }
	        fclose($fp);
        }
	    else {
	    	$this->sError = "$sError ($iErrno)";
        }
	}
	//-----------------------------------------------------------------------------------------------
	protected function RequestFopen()
	{
		if ($this->iTimeout) {
	 	   $old = ini_set('default_socket_timeout', $this->iTimeout);
  		}
		$fp = @fopen($this->sUrl, 'r');
		if ($this->iTimeout) {
			ini_set('default_socket_timeout', $old);
		}

		if ($fp) {
    		if ($this->iTimeout) {
    			stream_set_timeout($fp, $this->iTimeout);
    		}

			stream_set_blocking($fp, 1);
	        $iLine=0;
	        while (!feof($fp)&&($iLine < $this->iLineLimit)) {
	        	$sStr = fgets($fp, $this->iMaxLineLength);
	            $this->sContent .= $sStr;
	            if (stripos($sStr,'</head>')!==false) break;
            	$iLine += 1;
	        }

	        fclose($fp);
        }
	    else {
	    	$this->sError = 'Error in fopen("'.$this->sUrl.'","r")';
        }
	}
	//-----------------------------------------------------------------------------------------------
	public function request($bUseSocket=false)
	{
		if (empty($this->sUrl)) {
	         $this->sError = 'Url is empty';
	         return;
	    }
	 	if ($bUseSocket) {
	 		$this->RequestSocket();
	 	}
	 	else {
	 		$this->RequestFopen();
	 	}
        return $this;
	}

	//-----------------------------------------------------------------------------------------------

}