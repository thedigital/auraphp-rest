<?php
/**
 *
 * {$PROJECT_PATH}/src/Fli/Rest/RestController.php
 *
 * Rest Controller
 *
 * version : 2014-11-28
 *
 */
namespace Thedigital\Rest;

use Aura\Dispatcher\InvokeMethodTrait;
use Aura\Web\Request;
use Aura\Web\Response;
use Aura\Router\Router;

abstract class RestController
{
    /**
     *
     * We use the Invoke Method Trait once we have constructed the action to be called
     *
     */
    use InvokeMethodTrait;

    /**
     *
     * The Request object.
     *
     * @var Aura\Web\Request
     *
     */
    protected $request;

    /**
     *
     * The Response object.
     *
     * @var Aura\Web\Response
     *
     */
    protected $response;

    /**
     *
     * The Router object.
     *
     * @var Aura\Web\Router
     *
     */
    protected $router;

    /**
     *
     * The Rest object.
     *
     * @var stdClass
     *
     */
    protected $rest;

    /**
     *
     * Construct
     *
     * @param Request $request Aura.Request object
     *
     * @param Response $response Aura.Response object
     *
     * @param Router $router Aura.Router object
     *
     * @return null
     *
     */
    public function __construct(Request $request, Response $response, Router $router)
    {
        $this->request          = $request; // Request object injected by Aura\Di
        $this->response         = $response; // Response object injected by Aura\Di
        $this->router           = $router; // Router object injected by Aura\Di
        $this->rest             = new Rest(); // Rest Object

        $this->params           = $this->request->params;

        // detection en fonction du meilleur accept-type ou bien autodetection si extention dans url => exemple : action.json
        //--------------------
        // old system
        // $media = $this->request->accept->media->negotiate($this->rest->getFormats());
        // $this->rest->setMimeContentType($media->available->getValue());
        //--------------------
        $this->rest->setMimeContentType('application/json');

        // automatic detection of HTTP verb used
        foreach ($this->rest->getVerbs() as $verb) {
            $func = 'is' . $verb;

            // is it that verb ?
            if ($this->request->method->$func()) {
                // we store the verb
                $this->rest->setVerb($verb);

                // ce bloc detecte une methode qui n'existe pas mais a cause du invoke method declenche 2 executions : trouver autre chose
                try {
                    // we invoke no method (null) the normal action is triggered and so we get the Exception
                    $this->invokeMethod($this, null, $this->params);
                } catch (\Aura\Dispatcher\Exception\MethodNotDefined $exception) {
                    // in case the method is not implemented, we invoke the missingMethod() failover method
                    $this->invokeMethod($this, 'missingMethod', []);
                }
                break;
            }
        }
    }


    /**
     *
     * Failover method in case a method is not implemented
     *
     * @param null
     *
     * @return null
     *
     */
    protected function missingMethod()
    {
        // on recupere la route qui s'est declenchee
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $match_route = $this->router->match($path, $_SERVER);

        $method = $this->params['action'];

        $message = array(
            'warning' => $method . ' method not implemented in ' . $this->params['controller'] .' controller',
            'class' => get_class($this),
            'controller' => $this->params['controller'],
            'method' => $method,
            'verb' => $this->rest->getVerb(),
            'route' => array(
                'name' => $match_route->name,
                'path' => $match_route->path,
                'params' => $match_route->params,
            ),
        );
        $this->sendBack($message, 501);
    }

    /**
     *
     * Sends back the response according to the required format
     *
     * @param array $content The array containing the datas to be sent
     *
     * @return null
     *
     */
    final protected function sendBack(array $content, $status_code = 200)
    {
        $this->response->status->setCode($status_code);
        $this->response->content->setType($this->rest->getMimeContentType());

        switch ($this->rest->getMimeContentType()){
            case 'application/json':
                // JSON format
                $content = json_encode($content);
                $this->response->headers->set('Content-Length', strlen($content));
                $this->response->content->set($content);
                break;
            default:
                // default case
                $this->response->content->set(
                    'Unknown format'
                );
                break;
        }
    }

    /**
     *
     * Autoreflexive methods for Rest Service description.
     * Analyzes the Router.routes available and gives a description for each route.
     *
     * @param null
     *
     * @return null
     *
     */
    final public function describe()
    {
        $routes = array();

        // get Router.routes
        foreach ($this->router->getIterator() as $route) {

            // is this route routable ?
            if ($route->routable == 1) {
                $verbose_mode = false;

                // is verbose mode on ?
                if ($this->rest->getVerb() == 'options' && $this->params['verbose'] == '/all') {
                    $verbose_mode = true;
                }

                // has the current method been implemented ?
                try {
                    $currentClass = new \ReflectionClass(get_class($this));
                    $method = $currentClass->getMethod($route->values['action']);
                } catch (\ReflectionException $e) {
                    $method = null;
                }

                // should we describe this route ?
                if (
                    $verbose_mode === true || // if verbose mode is on for OPTIONS verb
                    (is_object($method) && $method->class == get_class($this)) || // has the current method a match route ?
                    is_object($method) && $method->isFinal() // is it a final method ?
                ) {
                    $routes[] = array(
                        'name' => $route->name,
                        'path' => $route->path,
                        'tokens' => $route->tokens,
                        'verb' => $route->server['REQUEST_METHOD'],
                        'values' => $route->values,
                        'name' => $route->name,
                        'class' => $method != null ? $method : 'Method not implemented',
                    );
                }
            }
        }
        // we send the description back
        $this->sendBack($routes);
    }

    /**
     *
     * Get the information from a PATCH request
     *
     * @return parsed data
     *
     */
    final protected function getPHPInputData()
    {
        parse_str(file_get_contents("php://input"), $post_vars);
        return $post_vars;
    }
}
