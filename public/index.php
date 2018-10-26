<?php

use App\Http\Action;
use App\Http\Middleware\Credentials;
use App\Http\Middleware\ErrorHandler;
use App\Http\Middleware\PageNotFound;
use App\Http\Middleware\Profiler;
use App\Http\Middleware\BasicAuth;
use Framework\Container\Container;
use Framework\Http\Application;
use Framework\Http\Middleware\Dispatch;
use Framework\Http\Middleware\Route;
use Framework\Http\Pipeline\Resolver;
use Framework\Http\Router\AuraRouterAdapter;
use Framework\Http\Router\Router;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response\SapiEmitter;

require __DIR__."/../vendor/autoload.php";

### Configuration
$container = new Container();

$container->set("config", [
	"debug" => true,
	"users" => ["admin" => "password"]
]);

$container->set(Application::class, function (Container $container) {
	return new Application(
		$container->get(Resolver::class),
		$container->get(Router::class),
		new PageNotFound()
	);
});
$container->set(Resolver::class, function () {
	return new Resolver();
});
$container->set(BasicAuth::class, function (Container $container) {
	return new BasicAuth($container->get("config")["users"]);
});
$container->set(ErrorHandler::class, function (Container $container) {
	return new ErrorHandler($container->get("config")["debug"]);
});
$container->set(Route::class, function (Container $container) {
	return new Route($container->get(Router::class));
});
$container->set(Dispatch::class, function (Container $container) {
	return new Dispatch($container->get(Resolver::class));
});
$container->set(Router::class, function () {
	return new AuraRouterAdapter(new Aura\Router\RouterContainer());
});

### Initialization
/** @var Application $app */
$app = $container->get(Application::class);

$app->addGetRoute("home", "/", Action\Hello::class);
$app->addGetRoute("about", "/about", Action\About::class);
$app->addGetRoute("cabinet", "/cabinet", [
	$container->get(BasicAuth::class),
	Action\Cabinet::class
]);
$app->addGetRoute("blog", "/blog", Action\Blog\Index::class);
$app->addGetRoute("blog_show", "/blog/{id}", Action\Blog\Show::class, ["tokens" => ["id" => "\d+"]]);

$app->pipe($container->get(ErrorHandler::class));
$app->pipe(Credentials::class);
$app->pipe(Profiler::class);
$app->pipe($container->get(Route::class));
$app->pipe($container->get(Dispatch::class));

### Runnig
$request = ServerRequestFactory::fromGlobals();
$response = $app->run($request, new Response());

### Postprocessing

### Sending
$emitter = new SapiEmitter();
$emitter->emit($response);