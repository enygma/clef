<?php

namespace Clef;

abstract class Model
{
    /**
     * Current property values
     * @var array
     */
    protected $values = array();

    /**
     * Current model properties
     * @var array
     */
    protected $properties = array();

    /**
     * Current request object
     * @var object
     */
    protected $request = null;

    /**
     * Init the object and set request if provided
     *
     * @param \MasheryApi\Request $request Request object
     */
    public function __construct(\MasheryApi\Request $request = null)
    {
        if ($request !== null) {
            $this->setRequest($request);
        }
    }

    /**
     * Set a property value on the object
     *
     * @param string $name Property name
     * @param mixed $value Property value
     */
    public function __set($name, $value)
    {
        if (array_key_exists($name, $this->properties) === true) {
            $this->values[$name] = $value;
        }
    }

    /**
     * Get the value for a property (if it exists)
     *
     * @param string $name Property name to find
     * @return mixed|null Value of property if it exists, otherwise null
     */
    public function __get($name)
    {
        return (array_key_exists($name, $this->properties) && array_key_exists($name, $this->values))
            ? $this->values[$name] : null;
    }

    /**
     * Set or get the current model's values
     *
     * @param array $data Data to populate to model properties
     * @return array Model property values
     */
    public function values($data = null)
    {
        if ($data !== null && is_array($data)) {
            foreach ($data as $name => $value) {
                if (array_key_exists($name, $this->properties)) {
                    $this->values[$name] = $value;
                }
            }
        } else {
            return $this->values;
        }
    }

    /**
     * Set the curent model's request object
     *
     * @param \MasheryApi\Request $request Request object
     */
    public function setRequest(\MasheryApi\Request $request)
    {
        $this->request = $request;
    }

    /**
     * Get the current request object
     *
     * @return \MasheryApi\Request object
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Check for an empty object
     *
     * @return boolean Empty/not empty
     */
    public function isEmpty()
    {
        $values = $this->values();
        return empty($values);
    }
}