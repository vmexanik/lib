<?
require_once(SERVER_PATH.'/class/core/Base.php');
class Emex extends Base {

	public $loginSOAP='QKIV';
	public $passSOAP='Sd9w74gp';
	public $customerId='4590';

	//
	// В пример не включен ряд сторонних функций, констант и переменных, не имеющих прямое
	// отношение к примеру.
	//

	//
	// Формирование нестандартного запроса к Microsoft SOAP службе.
	// Данная функция может вызывать любую SOAP функцию с любым набором аргументов.
	// Возврат - XML объект ответа в $xml.
	//
	function soap($conf, &$out, &$xml) {

		$postline='<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
<soap12:Body>
<'.$conf['function'].' xmlns="http://tempuri.org/">'."\r\n";

		// Параметры к SOAP запросу
		foreach ($conf['arg'] as $k=>$v) {
			$postline.="<$k>$v</$k>\r\n";
		}

		// Имя SOAP функции
		$postline.='</'.$conf['function'].'></soap12:Body></soap12:Envelope>';

		// Формирование HTTP запроса
		$request=
		"POST $conf[urlpath] HTTP/1.1\r\n".
		"Host: $conf[urlhost]\r\n".
		"Connection: Close\r\n".
		"Content-Type: text/xml; charset=utf-8\r\n".
		"Content-Length: ".strlen($postline)."\r\n".
		"\r\n".$postline;

		$conf['request']=&$request;
		$conf['postline']=$postline;

		if (!isset($conf['timeout'])) $conf['timeout']=20;
		$conf['url']="$conf[urlhost]$conf[urlpath]";

		// Вместо http_download используйте Curl с аналогичными параметрами.
		// Функция скачивает страницу и возвращает данные в $out.
		// В $out['body'] хранится текст скаченной страницы.
		$ret=$this->http_download($conf,$out);

		return;

		if ($ret!==true) return false;

		if (!preg_match("!<$conf[function]Result>(.*)</$conf[function]Result>!s",$out['body'],$ok)) {

			// Если ответ считан, но он пуст.
			if (preg_match("!<$conf[function]Result />!s",$out['body'])) {
				$xml=new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"windows-1251\"?><DocumentElement></DocumentElement>");
				return true;
			}

			$out['error']=1000;
			$out['errormsg']="Не удалось распаковать SOAP-структуру в ответе удаленного сервера";
			return false;
		}

		$buf=&$ok[1];
		$buf=str_replace("&lt;","<",$buf);
		$buf=str_replace("&gt;",">",$buf);
		$buf=str_replace("&quot;","\"",$buf);
		$buf=str_replace("&amp;","&",$buf);

		// Если ответ считан, но он пуст.
		if (!$conf['xml']) {
			return true;
		}

		// Emex может выдать текст ошибки вместо результата
		if (strstr(substr($buf, 0, 20), "Error")) {
			$out['error']=1001;
			$out['errormsg']=$buf;
			return false;
		}

		$buf='<?xml version="1.0" encoding="windows-1251"?>'.$buf;
		ob_start();
		$xml=new SimpleXMLElement($buf);
		ob_end_clean();

		if (!$conf['soap_var']) $var='Data'; else $var=$conf['soap_var'];

		// Если указан массив $conf['soap_utf8'] с именами полей SOAP ответа, то перекодируем их из UTF8 в Win1251
		if (is_array($conf['soap_utf8']) && count($conf['soap_utf8'])/* && is_array($xml->$var)*/) {
			for ($i=0; $i<count($xml->{$var}); $i++) {
				foreach ($conf['soap_utf8'] as $v) {
					if (isset($xml->{$var}[$i]->$v) && $xml->{$var}[$i]->$v && strlen($xml->{$var}[$i]->$v)) {
						@$xml->{$var}[$i]->$v=@utf8_win1251(@strval($xml->{$var}[$i]->$v));
					} else {
						unset($xml->{$var}[$i]->$v);
					}
				}
			}
		}

		// Аналогично массив с переменными, где хранится SOAP дата/время для конвертирования в unixtime
		if (is_array($conf['soap_data']) && count($conf['soap_data'])) {
			for ($i=0; $i<count($xml->{$var}); $i++) {
				foreach ($conf['soap_data'] as $v) {
					if (isset($xml->{$var}[$i]->$v) && $xml->{$var}[$i]->$v && strlen($xml->{$var}[$i]->$v)) {
						if (preg_match("!(\d{4})-(\d\d)-(\d\d)T(\d\d):(\d\d):(\d\d)!is",$xml->{$var}[$i]->$v,$ok) && $ok[1]>1970 && $ok[1]<2038) {
							$time=mktime((int)$ok[4],(int)$ok[5],(int)$ok[6],(int)$ok[2],(int)$ok[3],(int)$ok[1]);
							$xml->{$var}[$i]->$v=(int)$time;
							$xml->{$var}[$i]->{"$v-SQL"}="$ok[1]-$ok[2]-$ok[3] $ok[4]:$ok[5]:$ok[6]";
						} else {
							$xml->{$var}[$i]->$v=0;
						}
					} else {
						unset($xml->{$var}[$i]->$v);
					}
				}
			}
		}

		return true;

	}



	//
	// Выполнить поиск по коду запчасти, его MakeLogo и наименования автопроизводителя.
	// Подготовка запроса, цикл попыток выполнить запрос (защита от флуда и перегрузки
	// SOAP-сервера), анализ XML ответа в $this->searchParse().
	//
	function search($code, $ml, $autoName) {

		$this->code=$code;
		$this->ml=$ml;
		$this->autoName=$autoName;

		$mtime=mtime();

//		$fn=SERVER_PATH."/emex_request_noflood.lock";
//		if (!file_exists($fn)) touch($fn);
//		if (!file_exists($fn)) err("ErrorN061027124723",__FILE__,__LINE__);
//
//		$repeat=0;
//		while (1) {
//			//			if (mtime()-$mtime>10 || $repeat>10) {
//			//				return array(-5000, "Большая нагрузка. Повторите, пожалуйста, ваш запрос через 30 секунд (s5000).");
//			//			}
//			$repeat++;
//			if (filemtime($fn)==time()) {
//				sleep(1);
//				continue;
//			}
//			$f=@fopen($fn, "r+");
//			if (!$f) err("ErrorN061027124749",__FILE__,__LINE__);
//			if (flock($f,6)) break;
//			sleep(1);
//			continue;
//		}
//
//		fputs($f,time()."   ");
//		fclose($f);

		$repeat=0;
		while (1) {

			// вызов SOAP функции, результат будет в $xml
			$ret=$this->soap(
			array(
			"urlhost"=>"http://emexonline.com:3000/delta/maxima/",
			"urlpath"=>"service.asmx",
//			"urlhost"=>"http://megapartonline.com/maximanew/",
//			"urlpath"=>"service.asmx",
			"function"=>"FindDetail",
			"arg"=>array(
			'_Login'=>$this->loginSOAP,
			'_Password'=>$this->passSOAP,
			'_MakeLogo'=>$this->ml,
			'_DetailNum'=>$this->code,
			'_Substs'=>'false',
			),
			'soap_utf8'=>explode(" ","DetailNameCust DestinationDesc PriceDesc DetailName DetailNameRus"),
			'soap_data'=>array("DateChange"),
			'xml'=>true,
			),
			$out,
			$xml
			);

			if (strstr($out['errormsg'], "Error_5")) {
				if (mtime()-$mtime>10 || $repeat>3) {
					return array(-5001, "Большая нагрузка. Повторите, пожалуйста, ваш запрос через 30 секунд (s5001).");
				}
				$repeat++;
				continue;
			}

			break;
		}

		if (!$ret) {
			return array(-4, $out['errormsg']);
		}

		// анализ ответа
		$ret=$this->searchParse($xml);

		return $ret;
	}


	//
	// Проверка данных XML документа
	//
	function searchParse($xml) {
		$code=su($this->code);
		$ml=su($this->ml);
		$autoName=su($this->autoName);

		$fullnal=0;
		foreach ($xml->Data as $k=>$v) {

			if (su($v->MakeLogo)!=$ml || su($v->DetailNum)!=$code) continue;

			$name=strval($v->DetailName);
			if (strlen($v->DetailNameRus)>2 && sl(trim($v->DetailName))!=sl(trim($v->DetailNameRus))) $name.=" (".strval($v->DetailNameRus).")";

			$vars=explode(" ","nal nalreal isnal opt detUSD detRUB vesUSD vesRUB period maxperiod evaluation1 evaluation2 evaluation3 delivery1 delivery2 delivery3 QuantityChangeDate");
			foreach ($vars as $kk=>$vv) $$vv=false;

			$ves=(double)sprintf("%.4f",(double)((int)$v->DetailWeight)/1000);
			if ($ves<0) {
				addlog("ErrorN061101131111 [$ves]",__FILE__,__LINE__);
				return array(-4,'Запчасть имеет отрицительный вес');
			}

			// наличие на складе, штук
			$nal=false;
			if (preg_match("!([=><]) *(\d+)!is",$v->CommonQuantity,$ok)) {
				if ($ok[2]=='0') { $nal='на заказ'; $nalreal=0; $isnal=false; }
				elseif ($ok[1]=='=') { $nal=intval($ok[2])." шт.";  $nalreal=intval($ok[2]); $isnal=true; }
				elseif ($ok[1]=='>') { $nal="&gt;".intval($ok[2])." шт."; $nalreal=intval($ok[2]); $isnal=true; }
			}
			if ($nal===false) { $nal='на заказ'; $nalreal=0; $isnal=false; }
			else $fullnal+=$nalreal;

			// опт
			$opt=(int)$v->LotQuantity;
			if ($opt==1) $opt=0;

			// цена
			$detUSD=(double)$v->PriceDetailCurr/$this->sigmaKurs;
			$detRUB=(double)$v->PriceDetailCurr;
			$vesUSD=0;
			$vesRUB=0;
			if ($detUSD<0 || $detRUB<0 || $vesUSD<0 || $vesRUB<0) {
				addlog("ErrorN061101131706 [$detUSD|$detRUB|$vesUSD|$vesRUB] [$v->ResultPrice|$this->sigmaKurs|$v->KgCost]",__FILE__,__LINE__);
				return array(-4,'Запчасть имеет отрицительную стоимость');
			}

			$period=(int)$v->CalcDays;
			$maxperiod=(int)$v->DeliverTimeGuaranteed;

			// оценка качества
			$evaluation1=(double)$v->DITPercent;
			$evaluation2=(double)$v->DDPercent;
			$evaluation3=(double)$v->MDPercent;
			$QuantityChangeDate=$v->QuantityChangeDate;

			// тип поставки
			$delivery1=strtoupper($v->PriceLogo);
			$delivery2=strtoupper($v->DestinationLogo);
			$delivery3=trim($v->PriceDesc." ".$v->DestinationDesc);

			if (!$isnal && $period>30 || $period>60 || $delivery2=='CNT' || $delivery2=='CNR') continue;

			if ($detUSD && $detRUB && $delivery1) {

				$new=array();
				foreach ($vars as $k=>$v) {
					$new[$v]=$$v;
				}

				$new['price']=money(($detRUB+$vesRUB*$ves)*$this->emexNewExtra);
				if ($vesRUB!==false) $new['vesPrice']=money((double)$vesRUB*$this->emexNewExtra);

				if ($new['price']<0 || $detUSD<0 || $detRUB<0 || $vesRUB<0 || $vesUSD<0 || $new['vesPrice']<0) {
					file_put_contents(TEMP."MinusPrice_Emex_{$code}_".time().".txt",get_defined_vars());
					file_put_contents(TEMP."MinusPrice_Emex_{$code}_".time().".html",$html);
					err("ErrorN061015153334 - ошибка интерпретации (см. в ".time().".txt)",__FILE__,__LINE__);
				}

				if (count($new)) {
					$info['origin'][$code]=$code;
					$info['info'][$code]['ml']=$ml;
					$info['info'][$code]['autoName']=$autoName;
					$info['info'][$code]['name']=$name;
					$info['info'][$code]['ves']=(double)$ves*1000;
					$info['info'][$code]['line'][]=$new;
				}
			}


		}

		if (!is_array($info['info'][$code]['line']) || !count($info['info'][$code]['line'])) {
			return array(-2,'Номер запчасти не найден');
		}

		return array( $fullnal?1:0 ,$info);
	}


	function http_download($conf,&$out) {

		//$conf['url']="http://83.110.215.180:3000/delta/maxima/service.asmx";
		$conf['url']="http://megapartonline.com/maximanew/service.asmx";
		//$conf['url']="http://yandex.ru/";
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $conf['url']);


		$conf['postline']="<?xml version=\"1.0\" encoding=\"utf-8\"?>
<soap:Envelope xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.
   w3.org/2001/XMLSchema\" xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\">
  <soap:Body>
    <findnumberPHP xmlns=\"http://megapartonline.com/maximanew\">
      <Customer>
        <UserName>QKIV</UserName>
        <Password>Sd9w74lp</Password>
        <MainCustomerId>0</MainCustomerId>
        <CustomerId>4590</CustomerId>
        <lastVisit></lastVisit>
      </Customer>
      <DetailNum>8531505110</DetailNum>

  </findnumberPHP>
  </soap:Body>
</soap:Envelope>";

		Base::$sText.="<br><br><b>Request:</b> ".$conf['url']." <br>".htmlspecialchars($conf['postline']);


		curl_setopt ($curl, CURLOPT_HEADER, 0);
		curl_setopt ($curl, CURLOPT_POST, 1);
		curl_setopt ($curl, CURLOPT_POSTFIELDS, $conf['postline']);
		curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);

		curl_setopt($curl, CURLOPT_HTTPHEADER,array(
			"POST /maximanew/service.asmx HTTP/1.1",
			"Host: megapartonline.com",
			"Content-Type: text/xml; charset=utf-8",
			"SOAPAction: \"http://megapartonline.com/maximanew/findnumberPHP\"",
			"Content-Length: ".strlen($conf['postline'])
			)
		);

//		curl_setopt ($curl, CURLOPT_SSL_VERIFYHOST, 1);
		$out['body'] = curl_exec ($curl);
		$curl_info=curl_getinfo($curl);

		Base::$sText.="<br><br><b>Response:</b> <br>".htmlspecialchars($out['body']);

		curl_close ($curl);
	}

}


function mtime() {
	return time();
}


?>
