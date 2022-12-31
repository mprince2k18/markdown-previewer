<?php

    declare(strict_types=1);

    use Mprince\MarkdownViewer\DocsManager;
    use Mprince\MarkdownViewer\DocsViewer;

    if(!file_exists('vendor/autoload.php')) {
        die('Please run <code>composer install</code> first.');
    }

    require_once 'vendor/autoload.php';

    $manager = (new DocsManager())
        ->addFile('Package readme', 'README.md');

    (new DocsViewer($manager, 'vendor'))
        ->setTitle('Markdown viewer')
        ->setPackageURL('./')
        ->display();

