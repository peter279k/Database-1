<?php /** @noinspection PhpIllegalPsrClassPathInspection */
namespace App\Database;

class Get extends \Gt\SqlBuilder\Query\SelectQuery {
	public function select():array {
		return [
			"id",
			"name",
		];
	}

	public function from():array {
		return [
			"test_table",
		];
	}
}