<?php

namespace ProductionBranchSource;

class SimpleProductionBranchSource implements IProductionBranchSource
{
	public function getProductionBranchName() {
		return 'master';
	}
}