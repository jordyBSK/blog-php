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
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

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
$app->get('/', function (Request $request, Response $response) use ($pdo) {

    $query = $pdo->prepare('SELECT * FROM articles');
    $query->execute();
    $allArticles = $query->fetchAll();
    $view = Twig::fromRequest($request);
    return $view->render($response, 'home.twig', [
        'allArticles' => $allArticles,
        'dateNow'=> Carbon::now($allArticles['date_publication']),
 'pubDate' =>  Carbon::parse($allArticles['expiration¨'])->locale('fr_FR')->isoFormat('dddd D MMMM YYYY')
    ]);
})->setName('articles');



$app->get('/article/{id}', function (Request $request, Response $response, $args) use ($pdo) {
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
        'pubDate' =>  Carbon::parse($data['date_publication'])->locale('fr_FR')->isoFormat('dddd D MMMM YYYY')
    ]);

})->setName('article');

$app->get('/add-article', function (Request $request, Response $response, $args) use ($pdo) {

    $view = Twig::fromRequest($request);



    return $view->render($response, 'form.twig', [

    ]);

})->setName('article');
$app->post("/add-article", function (Request $request, Response $response, $args) use ($pdo) {
    $creationDate = Carbon::now();


$addQuery= $pdo->prepare("INSERT INTO articles (titre, texte, auteur, categorie_id, date_publication)VALUES (:titre, :texte, :auteur, :categorie, :creation)");

$addQuery->execute(["titre" =>  $_POST['article-title'], "texte" =>  $_POST["content"], "auteur" =>  $_POST["username"], "categorie" =>  $_POST["categorie"] ,'creation' => $creationDate]);


    return $response->withHeader('Location',"/")->withStatus(302);
});

// Run app
$app->run();