<?php /** @noinspection PhpIllegalPsrClassPathInspection */
namespace App\Database;

class GetLatest extends Get {
	public function orderBy():array {
		return [
			"timestamp desc",
		];
	}

	public function limit():array {
		return [1];
	}
}