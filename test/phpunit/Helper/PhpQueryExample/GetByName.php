<?php
namespace App\Database;

class GetByName extends \Gt\SqlBuilder\Query\SelectQuery {
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

	public function where():array {
		return [
			"name = ?",
		];
	}

	public function limit():array {
		return [1];
	}
}