<?php
/**
 *
 * {$PROJECT_PATH}/src/Fli/Rest/RestController.php
 *
 * Rest Controller
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
     * How old can be a request ?
     *
     * @var integer
     */
    private $RequestTTL = 900; // in seconds

    /**
     *
     * Force authentication ?
     *
     * @var boolean
     */
    private $forceAuthentication = true;

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
        $this->rest             = new Rest($this->request); // Rest Object

        $this->params           = $this->request->params;


        // $this->rest->setMimeContentType('application/json');


            if (in_array(strtolower($this->request->method->get()), $this->rest->getVerbs())) {

                // TODO ce bloc detecte une methode qui n'existe pas mais a cause du invoke method declenche 2 executions : trouver autre chose
                try {
                    // we invoke no method (null) the normal action is triggered and so we get the Exception
                    $this->invokeMethod($this, null, $this->params);
                } catch (\Aura\Dispatcher\Exception\MethodNotDefined $exception) {
                    // in case the method is not implemented, we invoke the missingMethod() failover method
                    $this->invokeMethod($this, 'missingMethod', []);
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
    final protected function sendBack(array $content, $status_code = 200, $content_type = 'application/json')
    {
        $this->response->status->setCode($status_code);
        $this->rest->setMimeContentType($content_type);
        $this->response->content->setType($this->rest->getMimeContentType());


        if ($content_type == 'application/json') {
            // JSON format
            $content = json_encode($content);
            $this->response->headers->set('Content-Length', strlen($content));
            $this->response->content->set($content);
        } elseif ($content_type == 'application/pdf' || preg_match('image/', $content_type)) {
            $this->response->headers->set('Content-Length', $content['data']['size']);
            $this->response->content->set($content['data']['file']);
        } else {
            // default case
            $this->response->content->set(
                'Unknown format'
            );
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
     * Récupère toutes les routes
     *
     *
     * @param null
     *
     * @return routes
     *
     */
    final public function getListingRoute()
    {
        $routes = array();

        // get Router.routes
        foreach ($this->router->getIterator() as $route) {

            if ($route->routable == 1 && $route->path != '/documentation' ) {
                $routes[] = array(
                    'name' => $route->name,
                    'path' => $route->path,
                    'tokens' => $route->tokens,
                    'verb' => $route->method,
                    'values' => $route->values,
                    'name' => $route->name,
                );
            }
        }
        // we send the description back
        return $routes;
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


    /**
     *
     * Verify integrity of a request (+ authorization)
     *
     *
     */
    final public function verifyRequest($keys)
    {

        $error = true;
        $error_message = 'Unauthorized';

        $public_key = $this->request->headers->get('x-FLI-Key');
        $hmac = $this->request->headers->get('x-FLI-Hmac');
        $date = $this->request->headers->get('x-FLI-Date');

        if ($this->forceAuthentication) {
            if ($public_key && $hmac && $this->isValidTimeStamp($date)) {
                //check if request is too old, no need to continue
                //args fournis
                if (abs(time() - $date) > $this->RequestTTL) {
                    $error_message = 'Request too old';
                    $this->response->headers->set('x-FLI-authorized', '0');
                } else {
                    if (isset($keys[$public_key])) {
                        // recuperation de l'url appelee
                        $url = str_replace(
                            $this->request->url->get(PHP_URL_HOST) . ':' . $this->request->url->get(PHP_URL_PORT),
                            $this->request->url->get(PHP_URL_HOST),
                            $this->request->url->get()
                        );

                        //on reconstruit le hmac
                        $string = strtoupper($this->rest->getVerb())."\n"
                                    .$url."\n"
                                    .$date."\n"
                                    .$keys[$public_key]['private_key'];
                        $hashed_string = $this->FLIhash($string);

                        if ($hashed_string == $hmac) {
                            //ok, proceed
                            $error = false;
                            $this->response->headers->set('x-FLI-authorized', '1');
                        } else {
                            $error_message = 'Authentication failed';
                            $this->response->headers->set('x-FLI-authorized', '0');
                        }
                    }
                }
            } else {
                $error_message = 'Missing authentication headers';
                $this->response->headers->set('x-FLI-authorized', '0');
                //envoi de mail d'erreur aux admins
                $data = print_r($_SERVER, true);
                mail(
                    'alertes@flinteractive.fr',
                    "Erreur d'appel API",
                    "Erreur d'authentification lors de l'appel a l'API ".strtolower((new \ReflectionClass($this))->getNamespaceName())."<br /><br />\$_SERVER :<br />"
                    .nl2br($data),
                    "MIME-Version: 1.0\r\nContent-type: text/html;\r\nFrom: alertes@flinteractive.fr\r\n",
                    '-f alertes@flinteractive.fr'
                );
            }

            if ($error) {
                header('HTTP/1.1 401 Unauthorized', true, 401);

                // send non-cookie headers
                foreach ($this->response->headers->get() as $label => $value) {
                    header("{$label}: {$value}");
                }

                // send cookies
                foreach ($this->response->cookies->get() as $name => $cookie) {
                    setcookie(
                        $name,
                        $cookie['value'],
                        $cookie['expire'],
                        $cookie['path'],
                        $cookie['domain'],
                        $cookie['secure'],
                        $cookie['httponly']
                    );
                }

                // send content
                echo json_encode($error_message);
                die();
            }

        } else {
            $this->response->headers->set('x-FLI-authorized', '0');
            if ($public_key && $hmac && $this->isValidTimeStamp($date) && isset($keys[$public_key]) && isset($keys[$public_key]['private_key'])) {
                // recuperation de l'url appelee
                $url = str_replace(
                    $this->request->url->get(PHP_URL_HOST) . ':' . $this->request->url->get(PHP_URL_PORT),
                    $this->request->url->get(PHP_URL_HOST),
                    $this->request->url->get()
                );
                $string = strtoupper($this->rest->getVerb())."\n"
                            .$url."\n"
                            .$date."\n"
                            .$keys[$public_key]['private_key'];
                $hashed_string = $this->FLIhash($string);
                if ($hashed_string == $hmac) {
                    $this->response->headers->set('x-FLI-authorized', '1');
                }
            }
        }

    }

    /**
     *
     * Verify validity of a timestamp
     *
     * @return boolean
     *
     */
    private function isValidTimeStamp($timestamp)
    {
        return ((string) (int) $timestamp === $timestamp)
            && ($timestamp <= PHP_INT_MAX)
            && ($timestamp >= ~PHP_INT_MAX);
    }

    /**
     *
     * Hash with SHA256
     *
     * @return string
     *
     */
    private function FLIhash($input)
    {
        return hash('sha256', utf8_encode($input));
    }
}
