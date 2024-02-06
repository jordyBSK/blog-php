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
$pubDate = [];

$app->get('/', function (Request $request, Response $response) use ($pdo) {

if (isset($_GET['categories'])){
    $query = $pdo->prepare('Select * from articles where categorie_id = :categorie');
    $query->execute(["categorie" => $_GET['categories']]);
}elseif (isset($_GET['search']) && isset($_GET['searchBtn']) ) {
$search = $_GET['search'];

    $query = $pdo->prepare("SELECT * FROM articles WHERE titre LIKE ? ");
    $query->setFetchMode(PDO::FETCH_ASSOC);
    $query->execute(["%$search%"]);
}

else{
    $query = $pdo->prepare('SELECT * FROM articles');
    $query->execute();
}
    $allArticles = $query->fetchAll();

    $pubDates = [];
    foreach ($allArticles as $article) {
        $pubDates[] = Carbon::parse($article['date_publication'])->locale('fr_FR')->isoFormat('dddd D MMMM YYYY');
    }


    $view = Twig::fromRequest($request);
    return $view->render($response, 'home.twig', [
        'allArticles' => $allArticles,
        'pubDate' => $pubDates
    ]);


});






//add all article at Home
$app->get('/article/{id}', function (Request $request, Response $response, $args) use ($pdo) {
    $query = $pdo->prepare('Select * from articles where id = :id');
    $query->execute(["id" => $args["id"]]);
    $data = $query->fetch();

    $view = Twig::fromRequest($request);


    $categoryQuery = $pdo->prepare('Select * FROM categories WHERE id = :id');
    $categoryQuery->execute(["id" => $data["categorie_id"]]);
    $category = $categoryQuery->fetch();


    return $view->render($response, 'article.twig', [
        'id' => $args['id'],
        'data' => $data,
        'category' => $category,
        'pubDate' => Carbon::parse($data['date_publication'])->locale('fr_FR')->isoFormat('dddd D MMMM YYYY')
    ]);

});





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
        $addQuery = $pdo->prepare("INSERT INTO articles (titre, texte, auteur, categorie_id, date_publication, description) VALUES (:titre, :texte, :auteur, :categories, :creation, :description)");
        $addQuery->execute(["titre" => $_POST['article-title'], "texte" => $_POST["content"], "auteur" => $_POST["username"], "categories" => $_POST["categories"], 'creation' => $creationDate, 'description' => $_POST["description"] ]);
    }

    return $response->withHeader('Location', "/")->withStatus(302);
});



//get article to edit
$app->get("/edit/article/{id}", function (Request $request, Response $response, $args) use ($pdo) {
    $view = Twig::fromRequest($request);

    $selectEdit = $pdo->prepare('Select * from articles where id = :id');
    $selectEdit->execute(["id" => $args["id"]]);
    $data = $selectEdit->fetch();

    return $view->render($response, 'form-edit.twig', [
        'data' => $data,
        'id' => $args['id']
    ]);
});

// query for edit an article
$app->post("/edit/article/{id}", function (Request $request, Response $response, $args) use ($pdo) {

    $updateData = $pdo->prepare('UPDATE articles SET titre = :titre, categorie_id = :categorie, texte = :texte WHERE id = :id');

    $updateData->execute(["titre" => $_POST['article-title'], "texte" => $_POST["content"], "categorie" => $_POST["categorie"], 'id' => $args['id']]);

    return $response->withHeader('Location', "/article/". $args['id'])->withStatus(302);
});



// Run app
$app->run();