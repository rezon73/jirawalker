<?php

namespace ProductionBranchSource;

class RemoteProductionBranchSource implements IProductionBranchSource
{
	private $url;

	public function __construct($url) {
		$this->url = $url;
	}

	public function getProductionBranchName()
	{
		return strtolower(strip_tags(file_get_contents($this->url)));
	}
}