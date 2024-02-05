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
        'dateNow' => Carbon::now($allArticles['date_publication']),
        'pubDate' => Carbon::parse($allArticles['expiration¨'])->locale('fr_FR')->isoFormat('dddd D MMMM YYYY')
    ]);
})->setName('articles');







//add all article at Home
$app->get('/article/{id}', function (Request $request, Response $response, $args) use ($pdo) {
    $query = $pdo->prepare('Select * from articles where id = :id');
    $query->execute(["id" => $args["id"]]);
    $data = $query->fetch();

    $view = Twig::fromRequest($request);


    $categoryQuery = $pdo->prepare('Select * FROM categories WHERE id = :id');
    $categoryQuery->execute(["id" => $args["id"]]);
    $category = $categoryQuery->fetch();


    return $view->render($response, 'article.twig', [
        'id' => $args['id'],
        'data' => $data,
        'category' => $category,
        'pubDate' => Carbon::parse($data['date_publication'])->locale('fr_FR')->isoFormat('dddd D MMMM YYYY')
    ]);

})->setName('article');





$app->post("/article/{id}", function (Request $request, Response $response, $args) use ($pdo) {

    $id = $args['id'];

    $deleteQuery = $pdo->prepare("DELETE FROM articles WHERE id = :id");
    $deleteQuery->execute(["id" => $id]);

    return $response->withHeader('Location', "/")->withStatus(302);
});





//Formulaire add an article
$app->get('/add-article', function (Request $request, Response $response, $args) use ($pdo) {
    $view = Twig::fromRequest($request);
    return $view->render($response, 'form.twig', [
    ]);
});





//Query add an article
$app->post("/add-article", function (Request $request, Response $response, $args) use ($pdo) {
    $creationDate = Carbon::now();

    if (mb_strlen($_POST['article-title']) | mb_strlen($_POST["content"]) | mb_strlen($_POST["username"]) != 0) {
        $addQuery = $pdo->prepare("INSERT INTO articles (titre, texte, auteur, categorie_id, date_publication, description)VALUES (:titre, :texte, :auteur, :categorie, :creation, :description)");
        $addQuery->execute(["titre" => $_POST['article-title'], "texte" => $_POST["content"], "auteur" => $_POST["username"], "categorie" => $_POST["categorie"], 'creation' => $creationDate, 'description' => $_POST["description"] ]);
    }

    return $response->withHeader('Location', "/")->withStatus(302);
});












$app->get("/edit/article/{id}", function (Request $request, Response $response, $args) use ($pdo) {
    $view = Twig::fromRequest($request);

    $taquery = $pdo->prepare('Select * from articles where id = :id');
    $taquery->execute(["id" => $args["id"]]);
    $data = $taquery->fetch();

    return $view->render($response, 'form-edit.twig', [
        'data' => $data,
        'id' => $args['id']
    ]);

});

$app->post("/edit/article/{id}", function (Request $request, Response $response, $args) use ($pdo) {

    $updateData = $pdo->prepare('UPDATE articles SET titre = :titre, categorie_id = :categorie, texte = :texte WHERE id = :id');

    $updateData->execute(["titre" => $_POST['article-title'], "texte" => $_POST["content"], "categorie" => $_POST["categorie"], 'id' => $args['id']]);

    return $response->withHeader('Location', "/")->withStatus(302);
});


// Run app
$app->run();