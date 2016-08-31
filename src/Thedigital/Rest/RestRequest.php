<?php
/**
 *
 * {$PROJECT_PATH}/src/Fli/Rest/Rest.php
 *
 * Rest Object
 *
 */
namespace Thedigital\Rest;

use \RecursiveArrayIterator;

class RestRequest
{

    /**
     *
     * The headers data.
     *
     * @var array
     *
     */
    protected $data = array();

    /**
     *
     * The request verb.
     *
     * @var string
     *
     */
    protected $verb = null;


    /**
     *
     * setVerb
     *
     * @param string $verb Http verb
     *
     * @return null
     *
     */
    public function setVerb($verb)
    {
        $this->verb = $verb;
    }



    public function set($key = null) {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[] = $key;
        }
    }


    /**
     *
     * Returns the value of a particular header, or an alternative value if
     * the header is not present.
     *
     * @param string $key The header value to return.
     *
     * @param string $alt The alternative value.
     *
     * @return mixed
     *
     */
    public function get($key = null, $alt = null)
    {
        if (! $key) {
            return $this->data;
        }
        $key = strtolower($key);
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }
        return $alt;
    }

    /**
     *
     * Get the information from a PATCH/PUT/... requests
     *
     * @return object
     *
     */
    public function getInputStream()
    {
        parse_str(file_get_contents("php://input"), $post_vars);
        if (count($post_vars) > 0) {
            foreach ($post_vars as $data_key => $data_value) {
                $this->data[$data_key] = $data_value;
            }
        }
    }
}
