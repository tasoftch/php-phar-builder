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


use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LinkerCommand extends Command
{
	protected function configure()
	{
		$this->setName("link")
			->setDescription("Links a created php phar archive binary to a given machine.")
			->addArgument("binary", InputArgument::REQUIRED, 'A runnable binary')
			->addArgument('app-name', InputArgument::REQUIRED, 'The applcation name');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$binary = $input->getArgument("binary");
		if(!is_executable($binary))
			throw new Exception("Binary must be executable on the current machine.");

		$appName = $input->getArgument("app-name");

		if(!is_dir("$appName.app/Contents/Resources/")) {
			mkdir("$appName.app/Contents/Resources/", 0755, true);
			mkdir("$appName.app/Contents/MacOS", 0777, true);
		}
		$executable = basename($binary);
		copy($binary, "$appName.app/Contents/MacOS/$executable");
		chmod("$appName.app/Contents/MacOS/$executable", 0777);

		file_put_contents("$appName.app/Contents/Info.plist", "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<!DOCTYPE plist PUBLIC \"-//Apple//DTD PLIST 1.0//EN\" \"http://www.apple.com/DTDs/PropertyList-1.0.dtd\">
<plist version=\"1.0\">
	<dict>
		<key>CFBundleExecutable</key>
		<string>$executable</string>
	</dict>
</plist>");


		return 0;
	}
}