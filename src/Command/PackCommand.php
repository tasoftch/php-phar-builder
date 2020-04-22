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


use DirectoryIterator;
use Exception;
use Generator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PackCommand extends Command
{
	protected function configure()
	{
		$this->setDescription("Pack a bunch of files and/or directories into a php archive.")
			->setName("pack")
			->addArgument("phar-name", InputArgument::REQUIRED, 'The archive\'s name.')
			->addOption("file-list", 'f', InputOption::VALUE_REQUIRED, 'A php readable file which returns a list of all files to pack')
			->addOption("prefix", 'p', InputOption::VALUE_OPTIONAL|InputOption::VALUE_IS_ARRAY, 'file prefixes to replace for the archive', ['/vendor'])
			->addOption("with-composer", 'w', InputOption::VALUE_NONE, 'Includes the vendor/composer directory')
			->addOption("file-list-stdin", 'i', InputOption::VALUE_NONE, "Reads the file list from stdin.")
			->addOption("filter", 'l', InputOption::VALUE_REQUIRED, 'Regex filter for directory file contents', "/^\.|^Tests/i");
	}

	protected function readFromFileList(InputInterface $input): Generator {
		$list = $input->getOption("file-list");
		if(is_file($list)) {
			if(fnmatch("*.php", $list)) {
				foreach(require $list as $file)
					yield $file;
			} else {
				echo $list, PHP_EOL;

				$contents = file_get_contents($list);
				echo $contents;

				foreach(explode(PHP_EOL, $contents) as $file) {
					var_dump($file);
					yield trim($file);
				}
			}
		}
	}

	protected function chooseGenerator(InputInterface $input): Generator {
		if($list = $input->getOption("file-list"))
			yield from $this->readFromFileList($input);
		elseif($input->getOption("file-list-stdin"))
			yield from $this->readFileListFromSTDIN();
		else
			throw new Exception("Can not read files to pack");
	}

	protected function readFileListFromSTDIN(): Generator {
		$read = fread(STDIN, 1000000);
		foreach(explode(PHP_EOL, $read) as $file) {
			if($file)
				yield trim($file);
		}
	}


	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$IO = new SymfonyStyle($input, $output);
		$app_name = $input->getArgument("phar-name");

		$phar = new \Phar($app_name);
		$phar->addFile(dirname(dirname(__DIR__)) . "/LICENSE", "LICENSE");

		foreach($this->chooseGenerator($input) as $file) {
			if(is_file($file)) {
				$phar->addFile($file);
				if($IO->isVerbose())
					$IO->text("Added File $file");
			}
			elseif(is_dir($file)) {
				$filter = $input->getOption("filter");

				$iterator = function($dir) use (&$iterator, $filter, $IO) {
					foreach(new DirectoryIterator($dir) as $file) {
						if(preg_match($filter, $file->getBasename())) {
							if($IO->isVeryVerbose())
								$IO->text("Skipped File ", $file->getPathname());
							continue;
						}
						if(is_file($file->getPathname()))
							yield $file;
						elseif(is_dir($file->getPathname()))
							yield from $iterator($file->getPathname());
					}
				};

				foreach($iterator($file) as $theFile) {
					$phar->addFile($theFile->getPathname());
					if($IO->isVerbose())
						$IO->text("Added File ". $theFile->getPathname());
				}
			} else {
				$IO->warning("File $file not found.");
			}
		}

		if($input->getOption("with-composer")) {
			foreach(new DirectoryIterator("vendor/composer") as $file) {
				$file = $file->getPathname();
				if(is_file($file)) {
					$phar->addFile($file);
				}
			}
			$phar->addFile('vendor/autoload.php');
		}

		$phar->setStub("<?php\nthrow new RuntimeException('Binary is not yet executable.'); __HALT_COMPILER();\n?>");

		return 0;
	}
}