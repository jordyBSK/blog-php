<?php

$dsn = "sqlite:../blog.db";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];
$pdo = new PDO($dsn, null, null, $options);

use Carbon\Carbon;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require '../vendor/autoload.php';

// Create App
$app = AppFactory::create();


// Create Twig
$twig = Twig::create(__DIR__ . '/../template', ['cache' => false]);

// Add Twig-View Middleware
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);
$app->add(TwigMiddleware::create($app, $twig));

// Define named route
$app->get('/', function ($request, $response) use ($pdo) {

    $query = $pdo->prepare('SELECT * FROM articles');
    $query->execute();
    $allArticles = $query->fetchAll();
    $view = Twig::fromRequest($request);
    return $view->render($response, 'home.twig', [
        'allArticles' => $allArticles,
        'dateNow'=> Carbon::now($allArticles['date_publication'])

    ]);
})->setName('articles');



$app->get('/article/{id}', function ($request, $response, $args) use ($pdo) {
    $query = $pdo->prepare('Select * from articles where id = :id');
    $query->execute(["id" => $args["id"]]);
    $data = $query->fetch();

    $view = Twig::fromRequest($request);


    $categoryQuery = $pdo->prepare('Select * FROM categories WHERE id = :id');
    $categoryQuery->execute(["id" => $args["id"]]);
    $category = $categoryQuery->fetch();


    return $view->render($response, 'article.twig', [
        'data' => $data,
        'category' => $category,
        'pubDate' =>  Carbon::parse($data['expiration'])->locale('fr_FR')->isoFormat('dddd D MMMM YYYY')
    ]);

})->setName('article');

$app->get('/add-article', function ($request, $response, $args) use ($pdo) {

    $view = Twig::fromRequest($request);



    return $view->render($response, 'form.twig', [

    ]);

})->setName('article');

// Run app
$app->run();