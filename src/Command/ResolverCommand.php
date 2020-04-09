<?php
/**
 * Copyright (c) 2019 TASoft Applications, Th. Abplanalp <info@tasoft.ch>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace TASoft\Util\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TASoft\Util\Code\FileSourceCode;
use TASoft\Util\Code\SourceCodeInterface;

class ResolverCommand extends Command
{
	private $_PSR4;
	private $classMap;

	protected function configure()
	{
		$this->setName("resolve")
			->setDescription("Tries to resolve all used php files beginning from a given main file.")
		->addArgument("input-file", InputArgument::REQUIRED, 'The main file starting from')
		->addOption("target-file", 't', InputOption::VALUE_REQUIRED, 'The file where to put the collected files')
		->addOption("zero", 'z', InputOption::VALUE_NONE, 'Specifies, if the filenames should be stored as relative paths or zero (absolute) paths');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$inputFile = $input->getArgument("input-file");

		if(!file_exists($inputFile))
			throw new RuntimeException("File $inputFile does not exist.");

		$this->_PSR4 = require "vendor/composer/autoload_psr4.php";
		$this->classMap = require "vendor/composer/autoload_classmap.php";

		$FILES = array_values(require 'vendor/composer/autoload_files.php');
		$fls = $FILES;

		foreach($fls as $fl) {
			$this->recursiveResolveImportRules($fl, $FILES);
		}

		$this->recursiveResolveImportRules($inputFile, $FILES);

		if(!$input->getOption("zero")) {
			$relativeFiles = function($files) {
				foreach($files as $file) {
					$f = explode(getcwd(), $file, 2)[1] ?? "";
					if(!$f)
						$f = $file;
					elseif($f[0] == '/')
						$f = substr($f, 1);

					yield $f;
				}
			};
			$FILES = iterator_to_array( $relativeFiles($FILES) );
		}


		// Make vendor/xx/xx packages
		$fl = $FILES;
		$FILES = [];
		foreach($fl as $file) {
			if(preg_match("%/?vendor/[^/]+/[^/]+%i", $file, $ms)) {
				if(!in_array($ms[0], $FILES))
					$FILES[] = $ms[0];
			} else {
				if(!in_array(dirname($file), $FILES))

					$FILES[] = dirname($file);
			}
		}


		$fn = $input->getOption("target-file");
		if($fn && is_dir(dirname($fn))) {
			file_put_contents($fn, "<?php\nreturn " . var_export($FILES, true) . ";");
		} else {
			foreach($FILES as $FILE)
				echo $FILE, PHP_EOL;
		}

		return 0;
	}

	protected function recursiveResolveImportRules($filename, &$FILES) {
		$sc = new FileSourceCode($filename);
		$rules = $this->readImportRules($sc);
		foreach($rules as $rule) {
			$f = $this->resolveRuleIntoFile($rule);
			if($f && !in_array($f, $FILES)) {
				$FILES[]=$f;
				$this->recursiveResolveImportRules($f, $FILES);
			}
		}
	}



	protected function resolveRuleIntoFile($rule) {
		if(isset($this->classMap[$rule]))
			return $this->classMap[$rule];

		foreach($this->_PSR4 as $prefix => $files) {
			if("$rule\\" == $prefix || strpos($rule, $prefix) === 0) {
				$tgRule = substr($rule, strlen($prefix));
				if(!$tgRule) {
					$tgRule = explode("\\", $rule);
					$tgRule = end($tgRule);
				}
				$targetFile = str_replace("\\", '/', $tgRule) . ".php";

				foreach($files as $file) {
					if(is_file( "$file/$targetFile" ))
						return "$file/$targetFile";
				}
			}
		}
		return NULL;
	}

	protected function readImportRules(SourceCodeInterface $sourceCode) {
		if(!$sourceCode->hasAttribute('TOKENS'))
			$sourceCode->addAttribute("TOKENS", token_get_all( $sourceCode->getContents() ));

		$importRules = [];
		$state = 0;
		$currentRuleName = "";

		$canIgnore = function($token) {
			switch ($token[0]) {
				case T_WHITESPACE:
				case T_COMMENT:
				case T_DOC_COMMENT:
					return true;
				default:
					return false;
			}
		};

		foreach($sourceCode->getAttribute("TOKENS") as $token) {
			if(is_array($token)) {
				if($state == 0 && $token[0] == T_USE) {
					$state = 1;
					continue;
				}

				if($state == 1) {
					if($canIgnore($token)) {
						continue;
					}

					if($token[0] == T_STRING || $token[0] == T_NS_SEPARATOR) {
						$currentRuleName .= $token[1];
						continue;
					} else {
						$importRules[] = $currentRuleName;
					}
				}

				$currentRuleName = "";
				$state = 0;
			} else {
				if($currentRuleName)
					$importRules[] = $currentRuleName;
				$currentRuleName = "";
				$state = 0;
			}
		}

		$sourceCode->addAttribute("USE", $importRules);
		return $importRules;
	}
}