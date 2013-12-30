<?php
/**
 * @link      https://github.com/weierophinney/PhlyRestfully for the canonical source repository
 * @copyright Copyright (c) 2013 Matthew Weier O'Phinney
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 * @package   PhlyRestfully
 */

namespace PhlyRestfully;

use Traversable;
use Zend\Stdlib\ArrayUtils;
use Zend\Uri\Exception as UriException;
use Zend\Uri\UriFactory;

/**
 * Class which describe a HAL Link.
 *
 * <p>To be compliant with the HAL Specifications a HAL Link should be able to contain the following attributes:</p>
 * <ul>
 *     <li>href      : For indicating the target URI. href corresponds with the ’Target IRI’ as defined in Web Linking
 *                     (RFC 5988). This attribute MAY contain a URI Template (RFC6570) and in which case, SHOULD be
 *                     complemented by an additional templated attribtue on the link with a boolean value true.</li>
 *     <li>rel       : For identifying how the target URI relates to the 'Subject Resource'. The Subject Resource is the
 *                     closest parent Resource element. This attribute is not a requirement for the root element of a
 *                     HAL representation, as it has an implicit default value of 'self'. rel corresponds with the
 *                     'relation parameter' as defined in Web Linking (RFC 5988). rel attribute SHOULD be used for
 *                     identifying Resource and Link elements in a HAL representation.</li>
 *     <li>name      : For distinguishing between Resource and Link elements that share the same rel value. The name
 *                     attribute SHOULD NOT be used exclusively for identifying elements within a HAL representation, it
 *                     is intended only as a ‘secondary key’ to a given rel value.</li>
 *     <li>hreflang  : For indicating what the language of the result of dereferencing the link should be.</li>
 *     <li>title     : For labeling the destination of a link with a human-readable identifier.</li>
 *     <li>templated : This attribute SHOULD be present with a boolean value of true when the href of the link contains
 *                     a URI Template (RFC6570).</li>
 *
 * </ul>
 *
 * @author Baptiste GAILLARD (baptiste.gaillard@gomoob.com)
 * @see http://stateless.co/hal_specification.html
 * @see http://tools.ietf.org/html/rfc5988
 * @see http://tools.ietf.org/html/rfc6570
 */
class Link
{
    /**
     * The 'templated' HAL Link attribute.
     *
     * <p>This attribute SHOULD be present with a boolean value of true when the href of the link contains a URI
     *    Template (RFC6570).</p>
     *
     * @var string
     */
    private $templated = null;

    /**
     * The 'title' HAL Link attribute.
     *
     * <p>The title is used for labeling the destination of a link with a human-readable identifier.</p>
     *
     * @var string
     */
    private $title = null;

    /**
     * @var string
     */
    protected $relation;

    /**
     * TODO: DOCUMENTATION...
     *
     * @var string
     */
    protected $route;

    /**
     * TODO: DOCUMENTATION...
     *
     * @var array
     */
    protected $routeOptions = array();

    /**
     * TODO: DOCUMENTATION...
     *
     * @var array
     */
    protected $routeParams = array();

    /**
     * TODO: DOCUMENTATION...
     *
     * @var string
     */
    protected $url;

    /**
     * Create a link relation.
     *
     * @todo  filtering and/or validation of relation string
     * @param string $relation
     */
    public function __construct($relation)
    {
        $this->relation = (string) $relation;
    }

    /**
     * Gets the 'title' HAL Link attribute.
     *
     * <p>The title is used for labeling the destination of a link with a human-readable identifier.</p>
     *
     * @return string the 'title' HAL Link attribute.
     */
    public function getTitle() {

        return $this -> title;

    }

    /**
     * Indicates is the HAL Link is templated.
     *
     * <p>This attribute SHOULD be present with a boolean value of true when the href of the link contains a URI
     *    Template (RFC6570).</p>
     *
     * @return string <code>true</tt> if the link is templated, <code>false</tt> otherwise.
     */
    public function isTemplated() {

        return $this -> templated;;

    }

    /**
     * Set the route to use when generating the relation URI
     *
     * If any params or options are passed, those will be passed to route assembly.
     *
     * @param  string $route
     * @param  null|array|Traversable $params
     * @param  null|array|Traversable $options
     * @return self
     */
    public function setRoute($route, $params = null, $options = null)
    {
        if ($this->hasUrl()) {
            throw new Exception\DomainException(sprintf(
                '%s already has a URL set; cannot set route',
                __CLASS__
            ));
        }

        $this->route = (string) $route;
        if ($params) {
            $this->setRouteParams($params);
        }
        if ($options) {
            $this->setRouteOptions($options);
        }
        return $this;
    }

    /**
     * Set route assembly options
     *
     * @param  array|Traversable $options
     * @return self
     */
    public function setRouteOptions($options)
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }

        if (!is_array($options)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects an array or Traversable; received "%s"',
                __METHOD__,
                (is_object($options) ? get_class($options) : gettype($options))
            ));
        }

        $this->routeOptions = $options;
        return $this;
    }

    /**
     * Set route assembly parameters/substitutions
     *
     * @param  array|Traversable $params
     * @return self
     */
    public function setRouteParams($params)
    {
        if ($params instanceof Traversable) {
            $params = ArrayUtils::iteratorToArray($params);
        }

        if (!is_array($params)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects an array or Traversable; received "%s"',
                __METHOD__,
                (is_object($params) ? get_class($params) : gettype($params))
            ));
        }

        $this->routeParams = $params;
        return $this;
    }

    /**
     * Sets the 'templated' HAL Link attribute.
     *
     * <p>This attribute SHOULD be present with a boolean value of true when the href of the link contains a URI
     *    Template (RFC6570).</p>
     *
     * @param unknown $templated the 'templated' HAL Link attribute to set.
     */
    public function setTemplated($templated) {

        $this -> templated = $templated;

    }

    /**
     * Sets the 'title' HAL Link attribute.
     *
     * <p>The title is used for labeling the destination of a link with a human-readable identifier.</p>
     *
     * @param unknown $title the 'title' HAL Link attribute to set.
     */
    public function setTitle($title) {

        $this -> title = $title;

    }

    /**
     * Set an explicit URL for the link relation
     *
     * @param  string $url
     * @return self
     */
    public function setUrl($url)
    {
        if ($this->hasRoute()) {
            throw new Exception\DomainException(sprintf(
                '%s already has a route set; cannot set URL',
                __CLASS__
            ));
        }

        try {
            $uri = UriFactory::factory($url);
        } catch (UriException\ExceptionInterface $e) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Received invalid URL: %s',
                $e->getMessage()
            ), $e->getCode(), $e);
        }

        if (!$uri->isValid()) {
            throw new Exception\InvalidArgumentException(
                'Received invalid URL'
            );
        }

        $this -> url = $uri -> toString();
        return $this;
    }

    /**
     * Retrieve the link relation
     *
     * @return string
     */
    public function getRelation()
    {
        return $this->relation;
    }

    /**
     * Return the route to be used to generate the link URL, if any
     *
     * @return null|string
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * Retrieve route assembly options, if any
     *
     * @return array
     */
    public function getRouteOptions()
    {
        return $this->routeOptions;
    }

    /**
     * Retrieve route assembly parameters/substitutions, if any
     *
     * @return array
     */
    public function getRouteParams()
    {
        return $this->routeParams;
    }

    /**
     * Retrieve the link URL, if set
     *
     * @return null|string
     */
    public function getUrl()
    {
        return $this -> url;
    }

    /**
     * Is the link relation complete -- do we have either a URL or a route set?
     *
     * @return bool
     */
    public function isComplete()
    {
        return (!empty($this->url) || !empty($this->route));
    }

    /**
     * Does the link have a route set?
     *
     * @return bool
     */
    public function hasRoute()
    {
        return !empty($this->route);
    }

    /**
     * Does the link have a URL set?
     *
     * @return bool
     */
    public function hasUrl()
    {
        return !empty($this->url);
    }
}
