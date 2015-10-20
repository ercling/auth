<?php
namespace base;
use base\exceptions\InvalidRouteException;
use base\exceptions\NotFoundHttpException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use app\controllers;

class Application {
    public static $app;
    public $base_uri;
    private $_db;
    private static $_logger;
    private $_runtimePath;
    private $_request;
    private $_response;
    private $_urlManager;
    private $_userManager;
    private $_session;

    /**
     * @var string the default route of this application. Defaults to 'site'.
     */
    public $defaultRoute = 'site';

    /**
     * @var Controller the currently active controller instance
     */
    protected $controller;

    /**
     * @var View the currently active View instance
     */
    protected $view;

    /**
     * @var string|boolean the layout that should be applied for views in this application. Defaults to 'main'.
     * If this is false, layout will be disabled.
     */
    public $layout = 'main';

    /**
     * @var string the requested route
     */
    public $requestedRoute;

    /**
     * @var Action the requested Action. If null, it means the request cannot be resolved into an action.
     */
    public $requestedAction;

    /**
     * @var array the parameters supplied to the requested action.
     */
    public $requestedParams;

    /**
     * Constructor.
     * @param array $config name-value pairs that will be used to initialize the object properties.
     * Note that the configuration must contain both [[id]] and [[basePath]].
     * @throws InvalidConfigException if either [[id]] or [[basePath]] configuration is missing.
     */
    public function __construct($config = [])
    {
        self::$app = $this;
        $this->registerErrorHandler();
        $this->base_uri = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
    }

    /**
     * Registers error handler
     */
    protected function registerErrorHandler()
    {
        $handler = new ErrorHandler();
        $handler->register();
    }

    /**
     * @return Logger message logger
     */
    public static function getLogger()
    {
        if (self::$_logger !== null) {
            return self::$_logger;
        } else {
            $logger = new Logger('Application');
            $logger->pushHandler(new StreamHandler(__DIR__.'/../logs/application.log', Logger::DEBUG));
            self::$_logger = $logger;
            return self::$_logger;
        }
    }

    /**
     * Logs a trace message.
     * Trace messages are logged mainly for development purpose to see
     * the execution work flow of some code.
     * @param string $message the message to be logged.
     * @param string $category the category of the message.
     */
    public static function trace($message, $category = 'application')
    {
        if (APPMODE_DEBUG) {
            static::getLogger()->addNotice($message, [$category]);
        }
    }

    /**
     * Logs an error message.
     * An error message is typically logged when an unrecoverable error occurs
     * during the execution of an application.
     * @param string $message the message to be logged.
     * @param string $category the category of the message.
     */
    public static function error($message, $category = 'application')
    {
        static::getLogger()->addError($message, [$category]);
    }

    /**
     * Logs a warning message.
     * A warning message is typically logged when an error occurs while the execution
     * can still continue.
     * @param string $message the message to be logged.
     * @param string $category the category of the message.
     */
    public static function warning($message, $category = 'application')
    {
        static::getLogger()->addWarning($message, [$category]);
    }

    /**
     * Logs an informative message.
     * An informative message is typically logged by an application to keep record of
     * something important (e.g. an administrator logs in).
     * @param string $message the message to be logged.
     * @param string $category the category of the message.
     */
    public static function info($message, $category = 'application')
    {
        static::getLogger()->addInfo($message, [$category]);
    }

    /**
     * Returns the directory that stores runtime files.
     * @return string the directory that stores runtime files.
     * Defaults to the "runtime" subdirectory under [[basePath]].
     */
    public function getRuntimePath()
    {
        if ($this->_runtimePath === null) {
            $this->setRuntimePath();
        }

        return $this->_runtimePath;
    }

    /**
     * Sets the directory that stores runtime files.
     * @param string $path the directory that stores runtime files.
     */
    public function setRuntimePath($path = null)
    {
        if(!$path){
            $this->_runtimePath = __DIR__ . '/../runtime';
        }else{
            $this->_runtimePath = $path;
        }
    }

    /**
     * Runs the application.
     * This is the main entrance of an application.
     * @return integer the exit status (0 means normal, non-zero values mean abnormal)
     */
    public function run()
    {
        $response = $this->handleRequest($this->getRequest());
        $response->send();
        return $response->exitStatus;
    }

    /**
     * Returns the request component.
     * @return \base\Request the request component.
     */
    public function getRequest()
    {
        if (!$this->_request){
            $this->_request = new Request();
        }
        return $this->_request;
    }

    /**
     * Returns the response component.
     * @return \base\Response the response component.
     */
    public function getResponse()
    {
        if (!$this->_response){
            $this->_response = new Response();
        }
        return $this->_response;
    }

    /**
     * Returns the URL manager for this application.
     * @return \base\UrlManager the URL manager for this application.
     */
    public function getUrlManager()
    {
        if (!$this->_urlManager){
            $this->_urlManager = new UrlManager();
        }
        return $this->_urlManager;
    }

    /**
     * Runs a controller action specified by a route.
     */
    public function runAction($route, $params = [])
    {
        $parts = $this->createController($route);
        if (is_array($parts)) {
            /* @var $controller Controller */
            list($controller, $actionID) = $parts;
            Application::$app->controller = $controller;
            $result = $controller->runAction($actionID, $params);
            return $result;
        } else {
            throw new InvalidRouteException('Unable to resolve the request "' . $route . '".');
        }
    }

    public function getDb()
    {
        if (!$this->_db){
            $dbmanager = new DbManager();
            $this->_db = $dbmanager->db;
        }
        return $this->_db;
    }

    /**
     * Creates a controller instance based on the given route.
     *
     * The route should be relative to this module. The method implements the following algorithm
     * to resolve the given route:
     *
     * 1. If the route is empty, use [[defaultRoute]];
     * 2. If the first segment of the route is a valid module ID as declared in [[modules]],
     *    call the module's `createController()` with the rest part of the route;
     * 3. If the first segment of the route is found in [[controllerMap]], create a controller
     *    based on the corresponding configuration found in [[controllerMap]];
     * 4. The given route is in the format of `abc/def/xyz`. Try either `abc\DefController`
     *    or `abc\def\XyzController` class within the [[controllerNamespace|controller namespace]].
     *
     * If any of the above steps resolves into a controller, it is returned together with the rest
     * part of the route which will be treated as the action ID. Otherwise, false will be returned.
     *
     * @param string $route the route consisting of module, controller and action IDs.
     * @return array|boolean If the controller is created successfully, it will be returned together
     * with the requested action ID. Otherwise false will be returned.
     * @throws InvalidConfigException if the controller class and its file do not match.
     */
    public function createController($route)
    {
        if ($route === '') {
            $route = $this->defaultRoute;
        }

        // double slashes or leading/ending slashes may cause substr problem
        $route = trim($route, '/');
        if (strpos($route, '//') !== false) {
            return false;
        }

        if (strpos($route, '/') !== false) {
            list ($id, $route) = explode('/', $route, 2);
        } else {
            $id = $route;
            $route = '';
        }

        $controller = $this->createControllerByID($id);
        // module and controller map take precedence
//        if (isset($this->controllerMap[$id])) {
//            $controller = Yii::createObject($this->controllerMap[$id], [$id, $this]);
//            return [$controller, $route];
//        }
//        $module = $this->getModule($id);
//        if ($module !== null) {
//            return $module->createController($route);
//        }
//
//        if (($pos = strrpos($route, '/')) !== false) {
//            $id .= '/' . substr($route, 0, $pos);
//            $route = substr($route, $pos + 1);
//        }
//
//        $controller = $this->createControllerByID($id);
//        if ($controller === null && $route !== '') {
//            $controller = $this->createControllerByID($id . '/' . $route);
//            $route = '';
//        }

        return $controller === null ? false : [$controller, $route];
    }

    /**
     * Creates a controller based on the given controller ID.
     *
     * The controller ID is relative to this module. The controller class
     * should be namespaced under [[controllerNamespace]].
     *
     * Note that this method does not check [[modules]] or [[controllerMap]].
     *
     * @param string $id the controller ID
     * @return Controller the newly created controller instance, or null if the controller ID is invalid.
     */
    public function createControllerByID($id)
    {
        $className = "\\app\\controllers\\" . ucfirst($id).'Controller';
        if (is_subclass_of($className, 'base\Controller')) {
            $controller = new $className;
            return $controller;
        } elseif (APPMODE_DEBUG) {
            throw new \ErrorException("Controller class must extend from \\base\\Controller.");
        } else {
            return null;
        }
    }

    /**
     * Handles the specified request.
     * @param Request $request the request to be handled
     * @return Response the resulting response
     * @throws NotFoundHttpException if the requested route is invalid
     */
    public function handleRequest($request)
    {
        list ($route, $params) = $request->resolve();
        try {
            Application::trace("Route requested: '$route'", __METHOD__);
            $this->requestedRoute = $route;
            $result = $this->runAction($route, $params);
            if ($result instanceof Response) {
                return $result;
            } else {
                $response = $this->getResponse();
                if ($result !== null) {
                    $response->data = $result;
                }

                return $response;
            }
        } catch (InvalidRouteException $e) {
            throw new NotFoundHttpException('Page not found.', $e->getCode(), $e);
        }
    }

    /**
     * Returns the view object.
     * @return View the view application component that is used to render various view files.
     */
    public function getView()
    {
        if (!$this->view)
        {
            $this->view = new View();
        }
        return $this->view;
    }

    public function getUser()
    {
        if (!$this->_userManager){
            $this->_userManager = new UserManager();
        }
        return $this->_userManager;
    }

    public function getSession()
    {
        if (!$this->_session){
            $this->_session = new Session();
        }
        return $this->_session;
    }
}