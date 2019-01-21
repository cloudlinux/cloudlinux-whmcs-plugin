<?php


namespace CloudLinuxLicenses\classes\components;


use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class Controller extends Component {
    const TYPE_ERROR = 'error';
    const TYPE_SUCCESS = 'success';

    /**
     * @var string
     */
    protected $baseUrl;
    /**
     * Template object
     *
     * @var TemplateManager
     */
    protected $template;

    /**
     * Controller constructor.
     * @param TemplateManager $template
     * @param string $url
     */
    public function __construct(TemplateManager $template, $url)
    {
        $this->template = $template;
        $this->baseUrl = $url;
    }

    /**
     * Render view file with layout
     *
     * @param string $view
     * @param array $values
     * @return string
     * @throws \Exception
     */
    public function render($view, $values = [])
    {
        $values['controller'] = $this;
        return $this->template->render($view, $values);
    }

    /**
     * Run application
     */
    public function run()
    {
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData);

        $className = static::class;
        if (!isset(self::$models[$className])) {
            $controller = new $className($this->template, $this->baseUrl);
        } else {
            $controller = self::$models[$className];
        }

        try {
            if (isset($data->command)) {
                $this->checkCsrfToken();

                $result = $this->executeAction($controller, $data->command,
                    isset($data->params) ? $data->params : null);
                header('HTTP/1.1 200 OK');
                header('Content-Type: application/json');

                $result['status'] = self::TYPE_SUCCESS;
                echo json_encode($result);
                exit(0);
            }

            $this->executeAction($controller, 'index');
        } catch (\Exception $e) {
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-Type: application/json');

            echo json_encode([
                'message' => $e->getMessage(),
                'status' => self::TYPE_ERROR,
            ]);
            exit(1);
        }
    }

    protected function executeAction(Controller $controller, $action, \stdClass $params = null) {
        $init = function($method) use ($controller) {
            return [$controller, $method];
        };
        $method = $init($action);
        $params = $params ?: new \stdClass();
        return $method($params);
    }

    /**
     * @param EloquentBuilder|QueryBuilder $query
     * @param array $attributes
     * @param \stdClass $params
     * @return EloquentBuilder|QueryBuilder
     */
    protected function setFilters($query, array $attributes, \stdClass $params)
    {
        if (empty($params->search)) {
            return $query;
        }

        foreach ($attributes as $attribute => $field) {
            $value = isset($params->search->{$attribute}) && $params->search->{$attribute}
                ? $params->search->{$attribute} : null;
            if (!$value) {
                continue;
            }
            if (is_callable($field)) {
                $query = $field($query, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query;
    }

    /**
     * @param EloquentBuilder|QueryBuilder $query
     * @param array $attributes
     * @param \stdClass $params
     * @param string $default
     * @return EloquentBuilder|QueryBuilder
     */
    protected function setOrder($query, $attributes, \stdClass $params, $default)
    {
        if (empty($params->sort)) {
            return $query->orderBy($default, 'asc');
        }

        if ($attributes && isset($attributes[$params->sort->name])) {
            return $query->orderBy($attributes[$params->sort->name], $params->sort->order);
        }

        return $query->orderBy($params->sort->name, $params->sort->order);
    }

    /**
     * @param EloquentBuilder|QueryBuilder $query
     * @param \stdClass $params
     * @return EloquentBuilder|QueryBuilder
     */
    protected function setPagination($query, \stdClass $params)
    {
        if (!empty($params->pagination)) {
            $query = $query
                ->limit($params->pagination->limit)
                ->offset($params->pagination->offset);
        } else {
            $query = $query->limit(25);
        }

        return $query;
    }

    /**
     * @throws \ErrorException | \InvalidArgumentException
     */
    protected function checkCsrfToken() {
        if (!empty($_SERVER['HTTP_CL_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_CL_CSRF_TOKEN'];
        } elseif (!empty($_SERVER['CL_CSRF_TOKEN'])) {
            $token = $_SERVER['CL_CSRF_TOKEN'];
        } else {
            throw new \ErrorException('Can\'t get CSRF token');
        }

        if ($token !== Csrf::get()) {
            throw new \InvalidArgumentException('Invalid token');
        }
    }
}