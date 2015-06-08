<?php

date_default_timezone_set('PRC');
require 'vendor/autoload.php';
$configs = require 'configs/web.php';
$app = new \Slim\Slim($configs);

// Check book.path value
$bookName = $app->config('book.name');
if (empty($bookName)) {
    die('You must config book.name value in "' . dirname(__FILE__) . '/configs/web.php".');
}
$bookPath = dirname(__FILE__) . '/books/' . $bookName;

$view = $app->view();
$view->setTemplatesDirectory('./templates');
$view->parserOptions = array(
    'debug' => true,
    'cache' => dirname(__FILE__) . '/caches'
);
$view->parserExtensions = [new \Slim\Views\TwigExtension()];

$app->error(function (\Slim\Exception $e) use ($app) {
    $app->render('error.twig', [
        'error' => $e
    ]);
});
$app->notFound(function() use ($app) {
    $app->render('404.twig');
});

// Book catalog( Support chapter categoly )
$catalog = array();
$filepath = $bookPath . '/catalog.md';
$lines = file($filepath);
if ($lines) {
    $chapter = '';
    foreach ($lines as $line) {
        if (($line = trim($line)) === '') {
            continue;
        }
        if ($line[0] === '*') {
            $chapter = trim($line, '*');
        } elseif ($line[0] === '-' && preg_match('/\[(.*?)\]\((.*?)\)/', $line, $matches)) {
            $catalog[$chapter][$matches[2]] = $matches[1];
        }
    }
}

$app->get('/', function () use ($app, $catalog) {
    $app->render('index.twig', [
        'catalog' => $catalog
    ]);
});

$app->get('/page/:name.html', function ($name) use ($app, $catalog, $bookName, $bookPath) {
    $name = trim($name);
    $filename = $bookPath . '/' . $name . '.md';
    if (!is_file($filename)) {
        $app->notFound();
    }

    $parser = new \cebe\markdown\GithubMarkdown();
    $article = [
        'lastModifyTime' => filemtime($filename),
        'content' => str_replace('src="assets', "src=\"/books/{$bookName}/assets", $parser->parse(file_get_contents($filename))),
    ];

    $app->etag(md5($article['content'] . serialize($catalog)));

    $app->render('view.twig', [
        'article' => $article,
        'catalog' => $catalog
    ]);
})->name('page');

$app->run();
