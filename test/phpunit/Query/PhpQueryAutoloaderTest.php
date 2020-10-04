<?php
namespace Gt\Database\Test\Query;

use Gt\Database\PhpQueryAutoloader;

class PhpQueryAutoloaderTest extends \PHPUnit\Framework\TestCase {
	/** @dataProvider \Gt\Database\Test\Helper\Helper::queryCollectionPathExistsProvider */
	public function testAutoloaderFindsSingleQuery(
		string $queryName,
		string $queryCollectionPath
	) {
		$queryCollection = substr(
			$queryCollectionPath,
			strrpos($queryCollectionPath, "/") + 1
		);
		$queryRootDir = dirname($queryCollectionPath);
		$baseQueryPath = "$queryCollectionPath/getByName.php";
		$php = file_get_contents(__DIR__ . "/../Helper/PhpQueryExample/GetByName.php");
		$php = str_replace("App\\Database", "App\\Database\\$queryCollection", $php);
		file_put_contents($baseQueryPath, $php);

		$absoluteClassName = "App\\Database\\$queryCollection\\GetByName";

		$sut = new PhpQueryAutoloader("App", $queryRootDir);

		self::assertFalse(class_exists($absoluteClassName));
		$sut->autoload($absoluteClassName);
		self::assertTrue(class_exists($absoluteClassName));
	}
}