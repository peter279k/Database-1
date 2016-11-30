<?php
namespace Gt\Database\Test;

use Gt\Database\Connection\Driver;
use Gt\Database\Connection\Settings;
use Gt\Database\Connection\SettingsInterface;
use Gt\Database\Query\SqlQuery;

class SqlQueryTest extends \PHPUnit_Framework_TestCase {

/** @var \Gt\Database\Connection\Driver */
private $driver;

public function setUp() {
	$driver = $this->driverSingleton();
	$connection = $driver->getConnection();
	$schemaBuilder = $connection->getSchemaBuilder();
	$schemaBuilder->create("test_table", function($table) {
		$table->increments("id");
		$table->string("name")->unique();
		$table->timestamps();
	});
	$insertStatement = $connection->getPdo()->prepare(
		"insert into test_table (name) values
		('one'),
		('two'),
		('three')"
	);
	$success = $insertStatement->execute();
	$this->assertTrue($success, "Success inserting fake data");
}

/**
 * @dataProvider \Gt\Database\Test\Helper::queryPathNotExistsProvider
 * @expectedException \Gt\Database\Query\QueryNotFoundException
 */
public function testQueryNotFound(
string $queryName, string $queryCollectionPath, string $queryPath) {
	$query = new SqlQuery($queryPath, $this->driverSingleton());
}

/**
 * @dataProvider \Gt\Database\Test\Helper::queryPathExistsProvider
 */
public function testQueryFound(
string $queryName, string $queryCollectionPath, string $queryPath) {
	$query = new SqlQuery($queryPath, $this->driverSingleton());
	$this->assertFileExists($query->getFilePath());
}

/**
 * @dataProvider \Gt\Database\Test\Helper::queryPathExistsProvider
 * @expectedException \Gt\Database\Query\PreparedStatementException
 */
public function testBadPreparedStatementThrowsException(
string $queryName, string $queryCollectionPath, string $queryPath) {
	file_put_contents($queryPath, "insert blahblah into nothing");
	$query = new SqlQuery($queryPath, $this->driverSingleton());
	$query->execute();
}

/**
 * @dataProvider \Gt\Database\Test\Helper::queryPathExistsProvider
 */
public function testPreparedStatement(
	string $queryName,
	string $queryCollectionPath,
	string $queryPath
) {
	file_put_contents($queryPath, "select * from test_table");
	$query = new SqlQuery($queryPath, $this->driverSingleton());
	$resultSet = $query->execute();

	foreach(["one", "two", "three"] as $i => $name) {
		$row = $resultSet->fetch();
		$this->assertEquals($i + 1, $row["id"]);
		$this->assertEquals($name, $row["name"]);
	}
}

/**
 * @dataProvider \Gt\Database\Test\Helper::queryPathExistsProvider
 */
public function testLastInsertId(
	string $queryName,
	string $queryCollectionPath,
	string $queryPath
) {
	$uuid = uniqid("test-");
	file_put_contents(
		$queryPath, "insert into test_table (name) values ('$uuid')");
	$query = new SqlQuery($queryPath, $this->driverSingleton());
	$resultSet = $query->execute();
	$id = $resultSet->lastInsertId;
	$this->assertNotEmpty($id);

	file_put_contents($queryPath, "select * from test_table where id = $id");
	$query = new SqlQuery($queryPath, $this->driverSingleton());
	$resultSet = $query->execute();

	$this->assertEquals($uuid, $resultSet["name"]);
}

public function testSubsequentCounts() {
	$testData = Helper::queryPathExistsProvider();
	$queryPath = $testData[0][2];
	file_put_contents($queryPath, "select * from test_table");
	$query = new SqlQuery($queryPath, $this->driverSingleton());
	$resultSet = $query->execute();
	$count = count($resultSet);
	$this->assertGreaterThan(0, $count);
	$this->assertCount($count, $resultSet);
}

private function driverSingleton():Driver {
	if(is_null($this->driver)) {
		$settings = new Settings(
			Helper::getTmpDir(),
			Settings::DRIVER_SQLITE,
			Settings::DATABASE_IN_MEMORY,
			"localhost",
			"root",
			""
		);
		$this->driver = new Driver($settings);
	}

	return $this->driver;
}

}#