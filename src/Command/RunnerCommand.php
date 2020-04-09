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


use Phar;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunnerCommand extends Command
{
	protected function configure()
	{
		$this->setDescription("Makes an executable php archive binary runnable");
		$this->setName("runable")
		->addArgument("input-phar", InputArgument::REQUIRED, 'The input phar php binary')
		->addArgument('output', InputArgument::REQUIRED, 'The executable output')
		->addOption("options", 'o', InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'PHP interpreter options as string.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$src = $input->getArgument("input-phar");
		$tg = $input->getArgument("output");
		$options = $input->getOption("options");

		$phar = new Phar($src);
		$stub = $phar->getStub();

		if($options)
			$options = implode(" ", array_map(function($b) {
				return "-d$b";
			}, $options));
		else
			$options = "";

		$stub = "#!/usr/bin/env php $options\n$stub";
		$phar->setStub($stub);

		copy($src, $tg);

		chmod($tg, 0777);
		return 0;
	}
}