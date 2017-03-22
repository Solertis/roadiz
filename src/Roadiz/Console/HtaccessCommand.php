<?php
/**
 * Copyright (c) 2016. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file ConfigurationCommand.php
 * @author ambroisemaupate
 */
namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command line utils for installing RZ-CMS v3 from terminal.
 */
class HtaccessCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('generate:htaccess')
            ->setDescription('Generate .htaccess files to protect critical directories');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $text = "";
        /** @var Kernel $kernel */
        $kernel = $this->getHelper('kernel')->getKernel();

        $text .= '<info>Generating .htaccess files…</info>' . PHP_EOL;

        // Simple deny access files
        $this->protectFolders([
            "/conf",
            "/src",
            "/samples",
            "/gen-src",
            "/files/fonts",
            "/files/private",
            "/bin",
            "/tests",
            "/cache",
            "/logs",
        ], $text);

        /*
         * Protect root
         */
        $filePath = $kernel->getRootDir() . "/.htaccess";
        if (file_exists($kernel->getRootDir()) &&
            !file_exists($filePath)) {
            file_put_contents($filePath, $this->getMainHtaccessContent() . PHP_EOL);
            $text .= '    — ' . $filePath . PHP_EOL;
        } else {
            $text .= '    — Can’t write ' . $filePath . ", file already exists or folder is absent." . PHP_EOL;
        }

        /*
         * Protect themes
         */
        $filePath = $kernel->getRootDir() . "/themes/.htaccess";
        if (file_exists($kernel->getRootDir() . "/themes") &&
            !file_exists($filePath)) {
            file_put_contents($filePath, $this->getThemesHtaccessContent() . PHP_EOL);
            $text .= '    — ' . $filePath . PHP_EOL;
        } else {
            $text .= '    — Can’t write ' . $filePath . ", file already exists or folder is absent." . PHP_EOL;
        }

        $output->writeln($text);
    }

    protected function protectFolders(array $paths, &$text)
    {
        /** @var Kernel $kernel */
        $kernel = $this->getHelper('kernel')->getKernel();

        foreach ($paths as $path) {
            $filePath = $kernel->getRootDir() . $path . "/.htaccess";
            if (file_exists($kernel->getRootDir() . $path) &&
                !file_exists($filePath)) {
                file_put_contents($filePath, "deny from all" . PHP_EOL);
                $text .= '    — ' . $filePath . PHP_EOL;
            } else {
                $text .= '    — Can’t write ' . $filePath . ", file already exists or folder is absent." . PHP_EOL;
            }
        }
    }

    protected function getMainHtaccessContent()
    {
        return '
# ------------------------------------
# Automatic .htaccess file
# Generated by Roadiz
# ------------------------------------
IndexIgnore *

# ------------------------------------
# EXPIRES CACHING
# ------------------------------------
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/pdf "access plus 1 month"
    ExpiresByType text/x-javascript "access plus 1 month"
    ExpiresByType text/javascript "access plus 1 month"
    ExpiresByType application/x-shockwave-flash "access plus 1 month"
    ExpiresByType image/x-icon "access plus 1 year"
    ExpiresDefault "access plus 2 days"
</IfModule>

<IfModule mod_rewrite.c>
    RewriteEngine On
    # Redirect to www
    #RewriteCond %{HTTP_HOST} !^www\.
    #RewriteRule ^(.*)$ http://www.%{HTTP_HOST}/$1 [R=301,L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.*)$ index.php?/$1 [QSA,L]
</IfModule>' . PHP_EOL;
    }

    protected function getThemesHtaccessContent()
    {
        return '
# ------------------------------------
# Automatic .htaccess file
# Generated by Roadiz
# ------------------------------------
<Files ~ "^\.php|yml|twig|xlf|rzn|rzt|rzg">
  Order allow,deny
  Deny from all
</Files>' . PHP_EOL;
    }
}
