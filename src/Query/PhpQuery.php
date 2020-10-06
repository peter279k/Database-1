<?php
namespace Gt\Database\Query;

use Gt\Database\Connection\Driver;
use Gt\Database\Result\ResultSet;

class PhpQuery extends SqlQuery {
	public function getSql(array $bindings = []):string {
		$className = $this->getClassName();
		$object = new $className;
		return (string)($object);
	}

	private function getClassName():string {
		$path = $this->getFilePath();

		$classBaseName = ucfirst(
			pathinfo(
				$path,
				PATHINFO_FILENAME
			)
		);

		$namespace = "\\" . $this->appNamespace . "\\Database";

		$namespaceDirectoryPath = substr(
			$path,
			strrpos($path, $this->basePath)
		);
		$namespaceDirectoryPath = substr(
			$namespaceDirectoryPath,
			strlen($this->basePath) + 1
		);
		$namespaceDirectoryPath = substr(
			$namespaceDirectoryPath,
			0,
			strrpos($namespaceDirectoryPath, "/")
		);
		foreach(explode("/", $namespaceDirectoryPath) as $namespacePart) {
			$namespace .= "\\" . ucfirst($namespacePart);
		}

		$className = "$namespace\\$classBaseName";

		if(!class_exists($className)) {
			throw new PhpQueryClassNotLoadedException($className);
		}

		return $className;
	}
}