<?php

namespace ProductionBranchSource;

class SimpleProductionBranchSource implements IProductionBranchSource
{
    /**
     * @var string
     */
    private $branchName;

    public function __construct($branchName) {
        $this->branchName = $branchName;
    }

    public function getProductionBranchName() {
		return $this->branchName;
	}
}