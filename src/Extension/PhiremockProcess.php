<?php
/**
 * This file is part of codeception-phiremock-extension.
 *
 * phiremock-codeception-extension is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * phiremock-codeception-extension is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with phiremock-codeception-extension.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Codeception\Extension;

use Symfony\Component\Process\Process;

/**
 * Manages the current running Phiremock process.
 */
class PhiremockProcess
{
    const LOG_FILE_NAME = 'phiremock.log';

    /** @var \Symfony\Component\Process\Process */
    private $process;

    public function start(
        string $ip,
        int $port,
        string $path,
        string $logsPath,
        bool $debug,
        ?string $expectationsPath,
        ?string $factoryClass
    ): void {
        $phiremockPath = is_file($path) ? $path : $path . DIRECTORY_SEPARATOR . 'phiremock';
        $expectationsPath = is_dir($expectationsPath) ? $expectationsPath : '';
        $logFile = $logsPath . DIRECTORY_SEPARATOR . self::LOG_FILE_NAME;
        $this->initProcess($ip, $port, $debug, $expectationsPath, $phiremockPath, $logFile, $factoryClass);
        $this->logPhiremockCommand($debug);
        $this->process->start();
    }

    public function stop(): void
    {
        $this->process->stop(3);
    }

    private function initProcess(
        string $ip,
        int $port,
        bool $debug,
        ?string $expectationsPath,
        string $phiremockPath,
        string $logFile,
        ?string $factoryClass
    ): void {
        $commandline = [
            $this->getCommandPrefix() . $phiremockPath,
            '-i',
            $ip,
            '-p',
            $port,
        ];
        if ($debug) {
            $commandline[] = '-d';
        }
        if ($expectationsPath) {
            $commandline[] = '-e';
            $commandline[] = $expectationsPath;
        }
        if ($factoryClass) {
            $commandline[] = '-f';
            $commandline[] = escapeshellarg($factoryClass);
        }
        $commandline[] = '>';
        $commandline[] = $logFile;
        $commandline[] = '2>&1';

        if (method_exists(Process::class, 'fromShellCommandline')) {
            $this->process = Process::fromShellCommandline(implode(' ', $commandline));
        } else {
            $this->process = new Process(implode(' ', $commandline));
        }
    }

    private function logPhiremockCommand(bool $debug): void
    {
        if ($debug) {
            echo 'Running ' . $this->process->getCommandLine() . PHP_EOL;
        }
    }

    private function getCommandPrefix(): string
    {
        return $this->isWindows() ? '' : 'exec ';
    }

    private function isWindows(): bool
    {
        return DIRECTORY_SEPARATOR === '\\';
    }
}
