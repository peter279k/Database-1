<?php
namespace Gt\Database;

use Gt\Database\Connection\DefaultSettings;
use Gt\Database\Query\QueryCollection;
use Gt\Database\Query\QueryCollectionFactory;

class DatabaseClientTest extends \PHPUnit_Framework_TestCase {

public function testInterface() {
	$db = new DatabaseClient();
	$this->assertInstanceOf("\Gt\Database\DatabaseClientInterface", $db);
}

public function testQueryCollectionMethod() {
	$queryCollection = $this->createMock(QueryCollection::class);

	$queryCollectionFactory = $this->createMock(QueryCollectionFactory::class);
	$queryCollectionFactory->method("create")
	->willReturn($queryCollection);

	// $settings = new DefaultSettings();
	$db = new DatabaseClient($queryCollectionFactory);

	$this->assertSame(
		$db->queryCollection("example"),
		$queryCollection
	);
}

/**
 * @dataProvider \Gt\Database\Test\Helper::queryCollectionPathExistsProvider
 */
public function testQueryCollectionPathExists(string $name, string $path) {
	$queryCollectionFactory = new QueryCollectionFactory(dirname($path));
	$db = new DatabaseClient($queryCollectionFactory);

	$this->assertTrue(isset($db[$name]));
	$queryCollection = $db->queryCollection($name);

	$this->assertInstanceOf("\\Gt\\Database\\Query\\QueryCollectionInterface",
		$queryCollection
	);
}

/**
 * @dataProvider \Gt\Database\Test\Helper::queryPathNotExistsProvider
 * @expectedException \Gt\Database\Query\QueryCollectionNotFoundException
 */
public function testQueryCollectionPathNotExists(string $name, string $path) {
	$queryCollectionFactory = new QueryCollectionFactory(dirname($path));
	$db = new DatabaseClient($queryCollectionFactory);

	$this->assertFalse(isset($db[$name]));
	$queryCollection = $db->queryCollection($name);
}

/**
 * @expectedException \Gt\Database\ReadOnlyArrayAccessException
 */
public function testOffsetSet() {
	$db = new DatabaseClient();
	$db["test"] = "qwerty";
}

/**
 * @expectedException \Gt\Database\ReadOnlyArrayAccessException
 */
public function testOffsetUnset() {
	$db = new DatabaseClient();
	unset($db["test"]);
}

}#