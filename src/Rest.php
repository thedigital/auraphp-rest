<?php
/**
 *
 * {$PROJECT_PATH}/src/Fli/Rest/Rest.php
 *
 * Rest Object
 *
 */
namespace Fli\Rest;

class Rest
{
    /**
     *
     * The available output formats.
     *
     * @var array
     *
     */
    protected $available_formats = array(
        'application/json',
    );

    /**
     *
     * The available verbs for REST controller.
     *
     * @var array
     *
     */
    protected $available_verbs = array(
        'get',
        'post',
        'put',
        'delete',
        'patch',
        'options',
    );

    /**
     *
     * Used verb
     *
     * @var string
     *
     */
    protected $verb = null;

    /**
     *
     * Used Mime Content Type
     *
     * @var string
     *
     */
    protected $mime_content_type = null;


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
    /*public function __construct()
    {
        ;
    }*/


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

    /**
     *
     * getVerb
     *
     * @param null
     *
     * @return string $verb Http verb
     *
     */
    public function getVerb()
    {
        return $this->verb;
    }

    /**
     *
     * setVerb
     *
     * @param string $verb Http verb
     *
     * @return null
     *
     */
    public function setMimeContentType($mime_content_type)
    {
        $this->mime_content_type = $mime_content_type;
    }

    /**
     *
     * getVerb
     *
     * @param null
     *
     * @return string $verb Http verb
     *
     */
    public function getMimeContentType()
    {
        return $this->mime_content_type;
    }

    /**
     *
     * getVerbs
     *
     * @param null
     *
     * @return array $verb Http available verbs
     *
     */
    public function getVerbs()
    {
        return $this->available_verbs;
    }

    /**
     *
     * getFormats
     *
     * @param null
     *
     * @return array $verb Http available formats
     *
     */
    public function getFormats()
    {
        return $this->available_formats;
    }

}
