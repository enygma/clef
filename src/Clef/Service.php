<?php

namespace Clef;

class Service
{
    /**
     * Request object
     */
    private $request = null;

    public function setRequest(\Clef\Request $request)
    {
        $this->request = $request;
    }

    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Magic method to catch our action methods
     *
     * @param string $func Function name
     * @param array $args Function arguments
     * @return object|null Object if found, null if not
     */
    public function __call($func, $args)
    {
        // find the position of the first capital letter
        preg_match('/([a-z]+)(.+)/', $func, $match);

        if (!empty($match)) {
            $type = $match[1];
            $class = $match[2];

            $modelClass = '\\Clef\\'.$match[2];
            if (class_exists($modelClass)) {
                $model = new $modelClass($this->getRequest());
                $type = strtolower($type);

                if (method_exists($model, '_preMethod')) {
                    $model->_preMethod($args);
                }

                if (method_exists($model, $type) === true) {
                    $result = $model->$type($args);
                } else {
                    $result = $model->find($args);
                }

                return ($result !== null) ? $model : null;
            }
        }
        return null;
    }
}