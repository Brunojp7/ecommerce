<?php 

namespace Hcode;

use Rain\Tpl;

class Page {

	private $tpl;
	private $options = [];
	private $defaults = [
		"header"=>true,
		"footer"=>true,
		"data"=>[]
	];

	public function __construct($opts = array(), $tpl_dir = "/views/"){

		$this->options = array_merge($this->defaults, $opts);

		$config = array(
			"tpl_dir"       => $_SERVER["DOCUMENT_ROOT"] . $tpl_dir,
			"cache_dir"     => $_SERVER["DOCUMENT_ROOT"] . "/views-cache/",
			"debug"         => False // set to false to improve the speed
	   );

		Tpl::configure($config);//Passa as configurações do Tpl feitas acima.

		$this->tpl = new Tpl;

		$this->setData($this->options["data"]);

		if ($this->options["header"] === true) $this->tpl->draw("header");

	}

	private function setData($data = array())
	{

		foreach ($data as $key => $value) {
			$this->tpl->assign($key, $value);
		}

	}

	public function setTpl($name, $data = array(), $returnHTML = false)//Corpo do HTML
	{

		$this->setData($data);

		return $this->tpl->draw($name, $returnHTML);

	}

	public function __destruct(){//Chamado automaticamente ao fim da execução

		if ($this->options["footer"] === true) $this->tpl->draw("footer");//Constroi o footer da página.

	}
	
}

 ?>