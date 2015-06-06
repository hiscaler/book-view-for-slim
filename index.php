<?php

require 'vendor/autoload.php';

$configs = require 'configs/web.php';
$app = new \Slim\Slim($configs);

// Check book.path value
$bookPath = $app->config('book.path');
if (empty($bookPath)) {
    die('You must config boo.path value in "' . dirname(__FILE__) . '/configs/web.php".');
}

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

// Book catalog
$catalog = array();
$filepath = $bookPath . '/catalog.md';
$lines = file($filepath);
if ($lines) {
    foreach ($lines as $line) {
        if (($line = trim($line)) === '') {
            continue;
        }
        if ($line[0] === '*' && preg_match('/\[(.*?)\]\((.*?)\)/', $line, $matches)) {
            $catalog[$matches[2]] = $matches[1];
        }
    }
}

$app->get('/', function () use ($app, $catalog) {
    $app->render('index.twig', [
        'catalog' => $catalog
    ]);
});

$app->get('/page/:name.html', function ($name) use ($app, $catalog, $bookPath) {
    $name = trim($name);
    $filename = $bookPath . '/' . $name . '.md';
    if (!is_file($filename)) {
        $app->notFound();
    }
    $parser = new \cebe\markdown\GithubMarkdown();
    $article = [
        'lastModifyTime' => filemtime($filename),
        'content' => $parser->parse(file_get_contents($filename)),
    ];

    $app->etag(md5($article['content']));

    $app->render('view.twig', [
        'article' => $article,
        'catalog' => $catalog
    ]);
})->name('page');

$app->run();
