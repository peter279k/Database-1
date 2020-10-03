<?php
namespace Gt\Database\Query;

use Gt\Database\Result\ResultSet;

class PhpQuery extends SqlQuery {
	private string $namespace = "App";

	public function setBaseNamespace(string $namespace):void {
		$this->namespace = $namespace;
	}

	public function getSql(array $bindings = []):string {
		require_once($this->getFilePath());
		$classBaseName = ucfirst(
			pathinfo(
				$this->getFilePath(),
				PATHINFO_FILENAME
			)
		);
		$className = "\\" . $this->namespace . "\\Database\\$classBaseName";
		if(!class_exists($className)) {
			throw new PhpQueryClassNotLoadedException($className);
		}

		$object = new $className();
		return (string)($object);
	}
}