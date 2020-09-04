<?
//-------------------------------------------------------------------------------------

class XmlParser  {
	var $parser;
	var $node;
	var $tree;
	var $tag_closed;
	var $node_id;
	var $lower;
	var $parent_level;
	var $parent;
	//-------------------------------------------------------------------------------------

	function XmlParser($data=array()) {
		$this->parser = xml_parser_create();
		xml_set_object($this->parser, $this);
		xml_parser_set_option($this->parser, XML_OPTION_SKIP_WHITE, 1);
		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
		xml_set_element_handler($this->parser, "tag_open", "tag_close");
		xml_set_character_data_handler($this->parser, "cdata");
		$this->level=0;
		$this->node_id=-1;
		$this->tree=new stdClass();
		$this->parent=&$this->tree;
		$this->tag_closed=false;
		$this->tag_opened=false;
		$this->parse_ebay_date=false;
	}
	//-------------------------------------------------------------------------------------

	function get_tree($data) {
		$data=preg_replace('/[\n|\r]+/ms',' ',$data);
		$data=preg_replace("/(<[^>]+>)\s+(<[^>]+>)/ms",'\1\2',$data);
		$data=preg_replace("/(<[^>]+>)\s+(<[^>]+>)/ms",'\1\2',$data);
		xml_parse($this->parser, $data);
		return $this->tree;
	}
	//-------------------------------------------------------------------------------------
	function tag_open($parser, $tag, $attributes) {
		//		echo "OPEN: ".$tag."<br>\n";
		$this->level++;
		if (!$this->tag_closed && isset($this->last_node)) {
			$this->parent=&$this->last_node;
		}
		$this->name=$tag;
		$this->parent_level[$this->level]=&$this->parent;

		if (!isset($this->parent->node)) $this->parent->node=new stdClass();
		if (!isset($this->parent->node->$tag)) {
			$this->parent->node->$tag=array();
		}
		$this->node_id=count($this->parent->node->$tag);
		$node=&$this->parent->node->$tag;
		if (!isset($node[$this->node_id])) $node[$this->node_id]=new stdClass();
		$node[$this->node_id]->attr=$attributes;
		$this->last_node=&$node[$this->node_id];
		$this->tag_closed=false;
		$this->tag_opened=true;
	}
	//-------------------------------------------------------------------------------------
	function cdata($parser, $cdata) {
		//		echo "CDATA: ".$tag."<br>\n";
		$this->last_node->cdata.=$cdata;
		if ($this->parse_ebay_date && preg_match("/^(\d{4})-(\d{2})-(\d{2}).*?(\d{2}):(\d{2}):(\d{2}).*$/",$this->last_node->cdata, $arr)) {
			$this->last_node->pdate=$arr[1]."-".$arr[2]."-".$arr[3]." ".$arr[4].":".$arr[5].":".$arr[6];
		}
	}
	//-------------------------------------------------------------------------------------
	function tag_close($parser, $tag) {
		//		$tag=strtolower($tag);
		//		echo "CLOSE: ".$tag."<br>\n";
		$this->tag_closed=true;
		if (!$this->tag_opened && isset($this->last_node)) {
			$this->parent=&$this->parent_level[$this->level];
		}
		$this->tag_opened=false;
		$this->level--;
	}
	//-------------------------------------------------------------------------------------
}

?>
