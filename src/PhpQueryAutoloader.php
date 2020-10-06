<?php
namespace Gt\Database;

use DirectoryIterator;

class PhpQueryAutoloader {
	private string $appNamespace;
	private string $queryRootDir;

	public function __construct(string $appNamespace, string $queryRootDir) {
		$this->appNamespace = "\\" . ltrim($appNamespace, "\\");
		$this->queryRootDir = rtrim($queryRootDir, "/");
	}

	public function autoload(string $absoluteClassName):?string {
		$absoluteClassName = "\\" . ltrim($absoluteClassName, "\\");
		$relativeClassName = substr(
			$absoluteClassName,
			strlen($this->appNamespace . "\\Database\\")
		);
		$path = $this->queryRootDir;

		foreach(explode("\\", $relativeClassName) as $classPart) {
			$path .= "/$classPart";
		}

		$path = "$path.php";
		if($path[0] !== "/") {
			$path = getcwd() . "/$path";
		}
		$path = $this->fixPath($path);

		/** @noinspection PhpIncludeInspection */
		require_once($path);
		return $path;
	}

// TODO: This function is taken from php.gt/webengine Gt\FileSystem\Path
// and needs refactoring to somewhere that can share the functionality.
	private function fixPath(string $path):?string {
		if(file_exists($path)) {
			return $path;
		}

		$fixed = "";
		if(DIRECTORY_SEPARATOR === "/") {
			$fixed .= DIRECTORY_SEPARATOR;
		}
		$path = str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $path);
		$pathParts = explode(DIRECTORY_SEPARATOR, $path);

		foreach($pathParts as $directory) {
			$currentSearchPath = $fixed;
			$currentSearchPath .= $directory;

// If the directory exists without its path being changed, use that and continue to next child.
			if(is_dir($currentSearchPath)) {
				$fixed = "$currentSearchPath";

				if(strlen($fixed) > 1) {
					$fixed .= DIRECTORY_SEPARATOR;
				}
				continue;
			}

			$iterator = new DirectoryIterator($fixed);
			$foundMatch = false;
			foreach($iterator as $fileInfo) {
				$fileName = $fileInfo->getFilename();
				if($fileName === "." || $fileName === "..") {
					continue;
				}

				if(strtolower($fileName) === strtolower($directory)) {
					$fixed .= $fileName . DIRECTORY_SEPARATOR;
					$foundMatch = true;
					break;
				}

				$directoryWithHyphensParts = preg_split(
					'/(?=[A-Z])/',
					$directory
				);
				$directoryWithHyphensParts = array_filter($directoryWithHyphensParts);

				$directoryWithHyphens = implode(
					"-",
					$directoryWithHyphensParts
				);

				$directoryWithHyphens = str_replace(
					"_-",
					"@",
					$directoryWithHyphens
				);

				if(strtolower($fileName) === strtolower($directoryWithHyphens)) {
					$fixed .= $fileName . DIRECTORY_SEPARATOR;
					$foundMatch = true;
					break;
				}
			}

			if(!$foundMatch) {
				return null;
			}
		}

		$fixed = rtrim($fixed, DIRECTORY_SEPARATOR);
		return $fixed;
	}
}