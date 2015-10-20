<?php
namespace base;
/**
 * View represents a view object in the MVC pattern.
 *
 * View provides a set of methods (e.g. [[render()]]) for rendering purpose.
 */
class View
{
    public $context;
    /**
     * Renders a view.
     *
     * The view to be rendered can be specified in one of the following formats:
     *
     * @param string $view the view name.
     * @param array $params the parameters (name-value pairs) that will be extracted and made available in the view file.
     * the view file corresponding to a relative view name.
     * @return string the rendering result
     */
    public function render($view, $params = [])
    {
        $viewFile = $this->findViewFile($view);
        return $this->renderFile($viewFile, $params);
    }

    /**
     * Finds the view file based on the given view name.
     */
    protected function findViewFile($view)
    {
        $path = __DIR__.'/../views/'.$view.'.php';
        if (!is_file($path))
        {
            throw new \ErrorException('The view file does not exist: '.$path);
        }
        return $path;
    }

    /**
     * Renders a view file.
     */
    public function renderFile($viewFile, $params = [])
    {
        ob_start();
        ob_implicit_flush(false);
        extract($params, EXTR_OVERWRITE);
        require($viewFile);

        return ob_get_clean();
    }
}