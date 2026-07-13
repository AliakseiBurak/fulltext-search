<?php

use App\Controller\IndexController;
use App\Controller\SearchController;
use App\Controller\DataController;
use App\Service\ManticoreService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

// Autoload dependencies
require_once __DIR__.'/../vendor/autoload.php';

// Create dependency injection container
$container = new ContainerBuilder();

// Register services
$manticoreService = new ManticoreService();
$container->set('manticore_service', $manticoreService);

// Register controllers
$indexController = new IndexController($manticoreService);
$container->set('index_controller', $indexController);

$searchController = new SearchController($manticoreService);
$container->set('search_controller', $searchController);

$dataController = new DataController($manticoreService);
$container->set('data_controller', $dataController);

// Define routes
$routes = new RouteCollection();
$routes->add('create_index', new Route('/api/index/create', [
    '_controller' => 'index_controller',
    '_method' => 'createIndex'
], [], [], '', [], ['POST']));
$routes->add('delete_index', new Route('/api/index/delete', [
    '_controller' => 'index_controller',
    '_method' => 'deleteIndex'
], [], [], '', [], ['POST']));
$routes->add('get_indices', new Route('/api/index/list', [
    '_controller' => 'index_controller',
    '_method' => 'getIndices'
], [], [], '', [], ['GET']));

$routes->add('search', new Route('/api/search', [
    '_controller' => 'search_controller',
    '_method' => 'search'
], [], [], '', [], ['POST']));
$routes->add('switch_index', new Route('/api/index/switch', [
    '_controller' => 'search_controller',
    '_method' => 'switchIndex'
], [], [], '', [], ['POST']));

$routes->add('add_document', new Route('/api/data/add', [
    '_controller' => 'data_controller',
    '_method' => 'addDocument'
], [], [], '', [], ['POST']));
$routes->add('add_documents', new Route('/api/data/bulk', [
    '_controller' => 'data_controller',
    '_method' => 'addDocuments'
], [], [], '', [], ['POST']));

// Create request context
$request = Request::createFromGlobals();
$context = new RequestContext();
$context->fromRequest($request);

// Create URL matcher
$matcher = new UrlMatcher($routes, $context);

try {
    // Match the route
    $parameters = $matcher->match($request->getPathInfo());

    // Extract controller and method
    $controller = $container->get($parameters['_controller']);
    $method = $parameters['_method'];

    // Call the appropriate method
    $response = $controller->$method($request);
    
    // Send response
    $response->send();
} catch (ResourceNotFoundException $e) {
    $response = new JsonResponse(['error' => 'Route not found'], 404);
    $response->send();
} catch (Exception $e) {
    $response = new JsonResponse(['error' => 'Internal server error: ' . $e->getMessage()], 500);
    $response->send();
}