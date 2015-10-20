<?php
namespace base;

abstract class Model
{
    /**
     * @var array validation errors (attribute name => array of errors)
     */
    private $_errors;
    /**
     * @var Array list of validators
     */
    private $_validators;

    public abstract function rules();

    /**
     * Map given array into model propelties
     * @param array $values
     * @param bool $filter
     */
    public function load(array $values,  $filter = true)
    {
        if ($filter){
            $hasErrors = $this->filter($values);
            if ($hasErrors) {
                return false;
            }
        }
        if (is_array($values)) {
            foreach ($values as $name => $value) {
                $this->$name = $value;
            }
        }
        return true;
    }

    public function filter(array &$values){
        $this->clearErrors();
        $fields = $this->getFields();
        $rules = $this->getRules();
        foreach ($values as $key=>$value){
            if (!in_array($key,$fields)){
                throw new \ErrorException($key . ' must be persist in filterRules()');
            }
            if(!is_array($rules[$key])){
                throw new \ErrorException($key . ' must be an array');
            }
            $filters = $rules[$key];
            foreach ($filters as $filter){
                //TODO: is_array check
                $values[$key] = filter_var($value, $filter);
                if ($values[$key] === false){
                    $this->_errors[$key][] = $filter;
                }
            }
        }
        return $this->hasErrors();
    }


    public function getFields(){
        $fields = array_keys($this->getRules());
        return $fields;
    }

    public function getRules(){
        $rules = $this->rules();
        return $rules;
    }

    /**
     * Returns a value indicating whether there is any validation error.
     * @param string|null $attribute attribute name. Use null to check all attributes.
     * @return boolean whether there is any error.
     */
    public function hasErrors($attribute = null)
    {
        return $attribute === null ? !empty($this->_errors) : isset($this->_errors[$attribute]);
    }

    /**
     * Returns the errors for all attribute or a single attribute.
     * @param string $attribute attribute name. Use null to retrieve errors for all attributes.
     * @property array An array of errors for all attributes. Empty array is returned if no error.
     * The result is a two-dimensional array. See [[getErrors()]] for detailed description.
     * @return array errors for all attributes or the specified attribute. Empty array is returned if no error.
     * Note that when returning errors for all attributes, the result is a two-dimensional array, like the following:
     *
     * ~~~
     * [
     *     'username' => [
     *         'Username is required.',
     *         'Username must contain only word characters.',
     *     ],
     *     'email' => [
     *         'Email address is invalid.',
     *     ]
     * ]
     * ~~~
     *
     * @see getFirstErrors()
     * @see getFirstError()
     */
    public function getErrors($attribute = null)
    {
        if ($attribute === null) {
            return $this->_errors === null ? [] : $this->_errors;
        } else {
            return isset($this->_errors[$attribute]) ? $this->_errors[$attribute] : [];
        }
    }

    /**
     * Removes errors for all attributes or a single attribute.
     * @param string $attribute attribute name. Use null to remove errors for all attribute.
     */
    public function clearErrors($attribute = null)
    {
        if ($attribute === null) {
            $this->_errors = [];
        } else {
            unset($this->_errors[$attribute]);
        }
    }

//    public static function tableName()
//    {
//        return strtolower(get_class());
//    }

}