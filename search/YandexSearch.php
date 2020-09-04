<?php
class YandexSearch
{
    public $result;

    protected $query;
    protected $host;
    protected $cat;
    protected $geo;
    protected $page = 0;
    protected $limit = 10;
    protected $sortby = 'rlv';
    protected $options = array(
        'maxpassages'           => null , // from 2 to 5
        'groupings'             => null , // http://help.yandex.ru/xml/?id=316625#group <d> <geo> <cat> <>
        'max-title-length'      => null , //
        'max-headline-length'   => null , //
        'max-passage-length'    => null , //
        'max-text-length'       => null , //

    );
    public $error = null;
    protected $errors = array(
        1 => '—интаксическа€ ошибка Ч ошибка в €зыке запросов',
        2 => '«адан пустой поисковый запрос Ч элемент query не содержит данных',
        8 => '«она не проиндексирована Ч обратите внимание на корректность параметров зонно-атрибутивного поиска',
        9 => 'јтрибут не проиндексирован Ч обратите внимание на корректность параметров зонно-атрибутивного поиска',
       10 => 'јтрибут и элемент не совместимы Ч обратите внимание на корректность параметров зонно-атрибутивного поиска',
       12 => '–езультат предыдущего запроса уже удален Ч задайте запрос повторно, не ссыла€сь на идентификатор предыдущего запроса',
       15 => '»скома€ комбинаци€ слов нигде не встречаетс€',
       18 => 'ќшибка в XML-запросе Ч проверьте валидность отправл€емого XML и корректность параметров',
       19 => '«аданы несовместимые параметры запроса Ч проверьте корректность группировочных атрибутов',
       20 => 'Ќеизвестна€ ошибка Ч при повтор€емости ошибки обратитесь к разработчикам с описанием проблемы',
    );
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
	 public function limit($limit)
	 {
	    $this->limit = $limit;
	 	return $this;
	 }
	//-----------------------------------------------------------------------------------------------
	 public function getLimit()
	 {
	    return $this->limit;
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
	 public function cat($cat)
	 {
	    $this->cat = $cat;
	 	return $this;
	 }
	//-----------------------------------------------------------------------------------------------
	 public function getCat()
	 {
	    return $this->cat;
	 }
	//-----------------------------------------------------------------------------------------------
	 public function geo($geo)
	 {
	    $this->geo = $geo;
	 	return $this;
	 }
	//-----------------------------------------------------------------------------------------------
	 public function getGeo()
	 {
	    return $this->geo;
	 }
	//-----------------------------------------------------------------------------------------------
	 public function sortby($sortby)
	 {
        if ($sortby == 'rlv' || $sortby == 'tm')
            $this->sortby = $sortby;
        return $this;
	 }
	//-----------------------------------------------------------------------------------------------
	 public function getSortby()
	 {
	    return $this->sortby;
	 }
	//-----------------------------------------------------------------------------------------------
	 public function set($option, $value = null)
	 {
	    $this->options[$option] = $value;
	 	return $this;
	 }
	//-----------------------------------------------------------------------------------------------
	 public function request()
	 {
	     if (empty($this->query)) {
	         $this->error = 'Query is empty';
	         return;
	     }

	 	$request  = '<?xml version="1.0" encoding="windows-1251"?>'."\n".
	 	             '<request>'."\n";
	 	// add query to request
	 	$query    = $this->query;

	 	// if isset "host"
	 	if ($this->host) {
	 	    $query .=  '<< host="'.$this->host.'"';
	 	}

	 	// if isset "cat"
	 	if ($this->cat) {
	 	    $query .=  '<< cat=('.($this->cat+9000000).')';
	 	}

	 	// if isset "geo"
	 	if ($this->geo) {
	 	    $query .=  '<< cat=('.($this->geo+11000000).')';
	 	}

	 	$request .= '<query><![CDATA['.$query.']]></query>'."\n";

	 	if ($this->page) {
	 	    $request .= '<page>'.$this->page.'</page>'."\n";
	 	}

	 	$request .= '<groupings>'."\n".
                    '<groupby  attr="" mode="flat" groups-on-page="'.$this->limit.'" docs-in-group="1" curcateg="-1" />'."\n".
                    '</groupings>';

	 	$request .= '<sortby order="descending" priority="yes">'.$this->sortby.'</sortby>'."\n";

	 	// TODO: add groupings and sortby realisation
	 	/*
	 	<sortby order="descending" priority="no">rlv</sortby>

	 	<groupings>
            <groupby attr="d" mode="deep" groups-on-page="10" docs-in-group="1" curcateg="-1"/>
        </groupings>

        <groupings>
            <groupby attr="" mode="flat" groups-on-page="10" docs-in-group="1" />
        </groupings>
	 	*/

	 	if ($this->options['maxpassages']) {
	 	    $request .= '<maxpassages>'.$this->options['maxpassages'].'</maxpassages>'."\n";
	 	}

	 	if ($this->options['max-title-length']) {
	 	    $request .= '<max-title-length>'.$this->options['max-title-length'].'</max-title-length>'."\n";
	 	}

	 	if ($this->options['max-headline-length']) {
	 	    $request .= '<max-headline-length>'.$this->options['max-headline-length'].'</max-headline-length>'."\n";
	 	}

	 	if ($this->options['max-passage-length']) {
	 	    $request .= '<max-passage-length>'.$this->options['max-passage-length'].'</max-passage-length>'."\n";
	 	}

	 	if ($this->options['max-text-length']) {
	 	    $request .= '<max-text-length>'.$this->options['max-text-length'].'</max-text-length>'."\n";
	 	}


	 	$request .= '</request>';

 	    $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://xmlsearch.yandex.ru/xmlsearch");
        curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: application/xml"));
        curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Accept: application/xml"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        $data = curl_exec($ch);

        $this->result = new SimpleXMLElement($data);
        $this->checkErrors();

        return $this;
	 }
	//-----------------------------------------------------------------------------------------------
	 protected function checkErrors()
	 {
	 	// switch statement for $this->result->response->error
	 	switch (true) {
	 		case isset($this->result->response->error):
	 		    // &&	($error = $this->result->response->error->attributes()->code[0] || $this->result->response->error->attributes()->code[0] === 0):
	 		    $error = (int)$this->result->response->error->attributes()->code[0];
	 		    if (isset($this->errors[$error])) {
	 		        $this->error = $this->errors[$error];
	 		    } else {
 		            $this->error = $this->result->response->error;
 		        }
	 			break;

	 		case isset($this->result->response->error) && !empty($this->result->response->error):
	 			$this->error = $this->result->response->error;
	 			break;

	 		default:
	 		    $this->error = null;
	 			break;
	 	}
	 }
	//-----------------------------------------------------------------------------------------------
	 public function total()
	 {
	     // FIXME: need fix?
	     if (empty($this->total)) {
	         $res = $this->result->xpath('response/found[attribute::priority="all"]');
	         $this->total = (int)$res[0];
	     }
	 	 return $this->total;
	 }
	//-----------------------------------------------------------------------------------------------
	 public function pages()
	 {
	     if (empty($this->pages))
	 	     $this->pages = ceil($this->total() / $this->limit);
 	     return $this->pages;
	 }
	//-----------------------------------------------------------------------------------------------
	 public function pageBar()
	 {
	     // FIXME: not good
	 	$pages = $this->pages();

	 	if ($pages < 10) {
	 	    $pagebar = array_fill(0, $pages, array('type'=>'link', 'text'=>'%d'));
	 	} elseif ($pages >= 10 && $this->page < 9) {
	 	    $pagebar = array_fill(0, 10, array('type'=>'link', 'text'=>'%d'));
	 	    $pagebar[$this->page] = array('type'=>'current', 'text'=>'<b>%d</b>');
	 	} elseif ($pages >= 10 && $this->page >= 9) {
	 	    $pagebar = array_fill(0, 2, array('type'=>'link', 'text'=>'%d'));
	 	    $pagebar[] = array('type'=>'text', 'text'=>'..');
	 	    $pagebar += array_fill($this->page-2, 2, array('type'=>'link', 'text'=>'%d'));
	 	    if ($pages > ($this->page+2))
	 	        $pagebar += array_fill($this->page, 2, array('type'=>'link', 'text'=>'%d'));
	 	    $pagebar[$this->page] = array('type'=>'current', 'text'=>'<b>%d</b>');
	 	}
	 	return $pagebar;
	 }
	//-----------------------------------------------------------------------------------------------
	 public function Init()
	 {
	 	 $this->error = null;
	 	 return $this;
	 }
	//-----------------------------------------------------------------------------------------------
	 static function highlight($text, $iLight=true)
	 {
	     if (is_object($text)) {
	     	$xml = $text->asXML();
	     }
	     else return '';

	     if ($iLight) {
    	     $xml = str_replace('<hlword priority="strict">', '<b>', $xml);
    	     $xml = str_replace('</hlword>', '</b>', $xml);
    	     $xml = strip_tags($xml, '<b>');
         } else
         {
    	     $xml = strip_tags($xml);
         }

         $xml = iconv("UTF-8", "Windows-1251", $xml);
	     return $xml;
	 }

}