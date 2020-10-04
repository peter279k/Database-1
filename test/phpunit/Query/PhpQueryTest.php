<?php
namespace Gt\Database\Test\Query;

use Gt\Database\Connection\Driver;
use Gt\Database\Connection\Settings;
use Gt\Database\Query\PhpQuery;
use Gt\Database\Query\PhpQueryClassNotLoadedException;
use Gt\Database\Query\QueryNotFoundException;
use Gt\Database\Test\Helper\Helper;
use PHPUnit\Framework\TestCase;

class PhpQueryTest extends TestCase {
	private Driver $driver;

	public function setUp():void {
		$driver = $this->driverSingleton();
		$connection = $driver->getConnection();
		$output = $connection->exec("CREATE TABLE test_table ( id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(32), timestamp DATETIME DEFAULT current_timestamp); CREATE UNIQUE INDEX test_table_name_uindex ON test_table (name);");
		static::assertNotFalse($output);

		$insertStatement = $connection->prepare(
			"INSERT INTO test_table (name) VALUES
			('one'),
			('two'),
			('three')"
		);
		$success = $insertStatement->execute();
		static::assertTrue($success, "Success inserting fake data");
	}

	/** @dataProvider \Gt\Database\Test\Helper\Helper::queryPathPhpNotExistsProvider */
	public function testQueryNotFound(
		string $queryName,
		string $queryCollectionPath,
		string $queryPath
	) {
		self::expectException(QueryNotFoundException::class);
		new PhpQuery($queryPath, $this->driverSingleton(), "App");
	}

	/** @dataProvider \Gt\Database\Test\Helper\Helper::queryPathPhpExistsProvider */
	public function testQueryFound(
		string $queryName,
		string $queryCollectionPath,
		string $queryPath
	) {
		$query = new PhpQuery($queryPath, $this->driverSingleton(), "App");
		static::assertFileExists($query->getFilePath());
	}

	/**
	 * @dataProvider \Gt\Database\Test\Helper\Helper::queryPathPhpExistsProvider
	 */
	public function testBadPhpNamespace(
		string $queryName,
		string $queryCollectionPath,
		string $queryPath
	) {
		$query = new PhpQuery($queryPath, $this->driverSingleton(), "App");
		self::expectException(PhpQueryClassNotLoadedException::class);
		$query->getSql(["two"]);
	}

	/**
	 * @dataProvider \Gt\Database\Test\Helper\Helper::queryCollectionPathExistsProvider
	 */
	public function testSqlGenerated(
		string $queryName,
		string $queryCollectionPath
	) {
		$queryCollection = substr(
			$queryCollectionPath,
			strrpos($queryCollectionPath, "/") + 1
		);

		$queryPath = "$queryCollectionPath/getByName.php";
		$php = file_get_contents(__DIR__ . "/../Helper/PhpQueryExample/GetByName.php");
		$php = str_replace(
			"namespace App\\Database",
			"namespace App\\Database\\$queryCollection",
			$php
		);

		file_put_contents($queryPath, $php);
		require_once($queryPath);
		$query = new PhpQuery($queryPath, $this->driverSingleton(), "App");
		$sql = $query->getSql(["two"]);

		self::assertStringContainsStringIgnoreWhitespace(
			"select id, name from test_table where name = ? limit 1",
			$sql
		);
	}

	/**
	 * @dataProvider \Gt\Database\Test\Helper\Helper::queryCollectionPathExistsProvider
	 */
	public function testNamespaceMismatch(
		string $queryName,
		string $queryCollectionPath
	) {
		$queryCollection = substr(
			$queryCollectionPath,
			strrpos($queryCollectionPath, "/") + 1
		);
		$queryPath = "$queryCollectionPath/getByName.php";
		$php = file_get_contents(__DIR__ . "/../Helper/PhpQueryExample/GetByName.php");
		$php = str_replace(
			"namespace App\\Database",
			"namespace MyTestApp\\Database\\$queryCollection",
			$php
		);
		file_put_contents($queryPath, $php);
		require_once($queryPath);
		$query = new PhpQuery($queryPath, $this->driverSingleton(), "App");
		self::expectException(PhpQueryClassNotLoadedException::class);
		$query->getSql(["two"]);
	}

	/**
	 * @dataProvider \Gt\Database\Test\Helper\Helper::queryCollectionPathExistsProvider
	 */
	public function testSetBaseNamespace(
		string $queryName,
		string $queryCollectionPath
	) {
		$queryCollection = substr(
			$queryCollectionPath,
			strrpos($queryCollectionPath, "/") + 1
		);
		$queryPath = "$queryCollectionPath/getByName.php";
		$php = file_get_contents(__DIR__ . "/../Helper/PhpQueryExample/GetByName.php");
		$php = str_replace(
			"namespace App\\Database",
			"namespace MyTestApp\\Database\\$queryCollection",
			$php
		);
		file_put_contents($queryPath, $php);
		require_once($queryPath);
		$query = new PhpQuery($queryPath, $this->driverSingleton(), "MyTestApp");
		$sql = $query->getSql(["two"]);

		self::assertStringContainsStringIgnoreWhitespace(
			"select id, name from test_table where name = ? limit 1",
			$sql
		);
	}

	/**
	 * @dataProvider \Gt\Database\Test\Helper\Helper::queryCollectionPathExistsProvider
	 */
	public function testExtendedClass(
		string $queryName,
		string $queryCollectionPath
	) {
		$queryCollection = substr(
			$queryCollectionPath,
			strrpos($queryCollectionPath, "/") + 1
		);
		$baseQueryPath = "$queryCollectionPath/get.php";
		$phpGet = file_get_contents(__DIR__ . "/../Helper/PhpQueryExample/Get.php");
		$extendedQueryPath = "$queryCollectionPath/getLatest.php";
		$phpGetLatest = file_get_contents(__DIR__ . "/../Helper/PhpQueryExample/GetLatest.php");

		$phpGet = str_replace(
			"namespace App\\Database",
			"namespace App\\Database\\$queryCollection",
			$phpGet
		);
		$phpGetLatest = str_replace(
			"namespace App\\Database",
			"namespace App\\Database\\$queryCollection",
			$phpGetLatest
		);

		file_put_contents($baseQueryPath, $phpGet);
		file_put_contents($extendedQueryPath, $phpGetLatest);

		require_once($baseQueryPath);
		require_once($extendedQueryPath);

		$query = new PhpQuery($extendedQueryPath, $this->driverSingleton(), "App");
		$sql = $query->getSql();

		self::assertStringContainsStringIgnoreWhitespace(
			"select id, name from test_table order by timestamp desc limit 1",
			$sql
		);
	}

	private function driverSingleton():Driver {
		if(!isset($this->driver)) {
			$settings = new Settings(
				Helper::getTmpDir(),
				Settings::DRIVER_SQLITE,
				Settings::SCHEMA_IN_MEMORY
			);
			$this->driver = new Driver($settings);
		}

		return $this->driver;
	}

	private static function assertStringContainsStringIgnoreWhitespace(
		string $expected,
		string $actual
	):void {
		$expected = str_replace(["\t", "\n"], " ", $expected);
		$actual = str_replace(["\t", "\n"], " ", $actual);
		while(strstr($expected, "  ")) {
			$expected = str_replace("  ", " ", $expected);
		}
		while(strstr($actual, "  ")) {
			$actual = str_replace("  ", " ", $actual);
		}
		self::assertEquals(trim($expected), trim($actual));
	}
}