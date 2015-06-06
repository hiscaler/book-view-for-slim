<?php

require 'vendor/autoload.php';

$configs = require 'configs/web.php';
$app = new \Slim\Slim($configs);
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

// 文章分类
$catalog = [];
$filepath = dirname(__FILE__) . '/books/guide/catalog.md';
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

$app->get('/page/:name.html', function ($name) use ($app, $catalog) {
    $name = trim($name);
    $filepath = dirname(__FILE__) . '/books/guide/' . $name . '.md';
    if (!is_file($filepath)) {
        $app->notFound();
    }
    $parser = new \cebe\markdown\GithubMarkdown();
    $markdownContent = $parser->parse(file_get_contents($filepath));


    $app->render('view.twig', [
        'activeName' => $name,
        'data' => $markdownContent,
        'catalog' => $catalog
    ]);
})->name('page');

$app->run();
