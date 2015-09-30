<?php
/**
 *
 * {$PROJECT_PATH}/src/Fli/Rest/Rest.php
 *
 * Rest Object
 *
 */
namespace Thedigital\Rest;

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
     * Construct
     *
     * @param Request $request Aura.Request object
     *
     * @return null
     *
     */
    /*public function __construct()
    {
        ;
    }*/


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

}
