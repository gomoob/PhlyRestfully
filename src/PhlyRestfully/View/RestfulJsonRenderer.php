<?php
/**
 * @link      https://github.com/weierophinney/PhlyRestfully for the canonical source repository
 * @copyright Copyright (c) 2013 Matthew Weier O'Phinney
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 * @package   PhlyRestfully
 */

namespace PhlyRestfully\View;

use PhlyRestfully\ApiProblem;
use PhlyRestfully\HalCollection;
use PhlyRestfully\HalResource;
use PhlyRestfully\Link;
use PhlyRestfully\LinkCollection;
use PhlyRestfully\Plugin\HalLinks;
use Zend\Paginator\Paginator;
use Zend\Stdlib\Hydrator\HydratorInterface;
use Zend\View\HelperPluginManager;
use Zend\View\Renderer\JsonRenderer;

/**
 * Handles rendering of the following:
 *
 * - API-Problem
 * - HAL collections
 * - HAL resources
 *
 * <p>Please note that this JSON Renderer is not the same as the Phly/PlyRestfully one and adds the following
 *    functionnalities : </p>
 * <ul>
 *    <li>Replace escaped slashes '\/' by normal slashes '/'. </li>
 * </ul>
 */
class RestfulJsonRenderer extends JsonRenderer
{
    /**
     * A reference to a custom GoMoob <tt>HalLinks</tt> Plugin which extends the <tt>PhlyRestfully</tt> Plugin. This
     * GoMoob <tt>HalLinks</tt> Plugin allow to generate more HAL properties than the <tt>PhlyRestfully</tt> Plugin,
     * like the <tt>title</tt> of the link and a <tt>templated</tt> parameter.
     *
     *  <p>A <tt>HalLinks</tt> Plugin is simply a Zend View Helper.</p>
     *
     *  <p>If this attribute is <code>null</code> then the default <tt>PhlyRestfully</tt> <tt>HalLink</tt> Plugin is used.</p>
     *
     * @var \Gomoob\PhlyRestfully\Plugin\HalLinks
     */
    private $halLinks = null;

    /**
     * Sets a reference to a custom GoMoob <tt>HalLinks</tt> Plugin which extends the <tt>PhlyRestfully</tt> Plugin. This
     * GoMoob <tt>HalLinks</tt> Plugin allow to generate more HAL properties than the <tt>PhlyRestfully</tt> Plugin,
     * like the <tt>title</tt> of the link and a <tt>templated</tt> parameter.
     *
     * <p>A <tt>HalLinks</tt> Plugin is simply a Zend View Helper.</p>
     *
     * <p>If no custom <tt>HalLinks</tt> Plugin is provided then the default <tt>PhlyRestfully</tt> <tt>HalLink</tt>
     * Plugin is used.</p>
     *
     *
     * @param \Gomoob\PhlyRestfully\Plugin\HalLinks $halLinks The custom <tt>HalLinks</tt> Plugin to use.
     */
    public function setHalLink(HalLinks $halLinks) {

        $this -> halLinks = $halLinks;

    }

    /**
     * @var ApiProblem
     */
    protected $apiProblem;

    /**
     * Whether or not to render exception stack traces in API-Problem payloads
     *
     * @var bool
     */
    protected $displayExceptions = false;

    /**
     * @var HelperPluginManager
     */
    protected $helpers;

    /**
     * Set helper plugin manager instance.
     *
     * Also ensures that the 'HalLinks' helper is present.
     *
     * @param  HelperPluginManager $helpers
     */
    public function setHelperPluginManager(HelperPluginManager $helpers)
    {
        if (!$helpers->has('HalLinks')) {
            $this->injectHalLinksHelper($helpers);
        }
        $this->helpers = $helpers;
    }

    /**
     * Lazy-loads a helper plugin manager if none available.
     *
     * @return HelperPluginManager
     */
    public function getHelperPluginManager()
    {
        if (!$this->helpers instanceof HelperPluginManager) {
            $this->setHelperPluginManager(new HelperPluginManager());
        }
        return $this->helpers;
    }

    /**
     * Set display_exceptions flag
     *
     * @param  bool $flag
     * @return RestfulJsonRenderer
     */
    public function setDisplayExceptions($flag)
    {
        $this->displayExceptions = (bool) $flag;
        return $this;
    }

    /**
     * Whether or not what was rendered represents an API problem
     *
     * @return bool
     */
    public function isApiProblem()
    {
        return (null !== $this->apiProblem);
    }

    /**
     * @return null|ApiProblem
     */
    public function getApiProblem()
    {
        return $this->apiProblem;
    }

    /**
     * Render a view model
     *
     * If the view model is a RestfulJsonRenderer, determines if it represents
     * an ApiProblem, HalCollection, or HalResource, and, if so, creates a custom
     * representation appropriate to the type.
     *
     * If not, it passes control to the parent to render.
     *
     * @param  mixed $nameOrModel
     * @param  mixed $values
     * @return string
     */
    public function render($nameOrModel, $values = null)
    {
        $this->apiProblem = null;

        if (!$nameOrModel instanceof RestfulJsonModel) {
            return parent::render($nameOrModel, $values);
        }

        if ($nameOrModel->isApiProblem()) {
            return $this->renderApiProblem($nameOrModel->getPayload());
        }

        if ($nameOrModel->isHalResource()) {
            $helper  = $this->helpers->get('HalLinks');
            $payload = $helper->renderResource($nameOrModel->getPayload());
            return parent::render($payload);
        }

        if ($nameOrModel->isHalCollection()) {
            $helper  = $this->helpers->get('HalLinks');
            $payload = $helper->renderCollection($nameOrModel->getPayload());
            if ($payload instanceof ApiProblem) {
                return $this->renderApiProblem($payload);
            }
            return parent::render($payload);
        }

        // Render a JSON string using the Zend Framework 2 JSON Renderer
        $json = parent::render($nameOrModel, $values);

        // Replace the escaped slashes (please note that this is only necessary with PHP 5.3 because PHP 5.4 has a
        // JSON_UNESCAPED_SLASHES option)
        $formattedJson = str_replace('\\/', '/', $json);

        return $formattedJson;

    }

    /**
     * Render an API Problem representation
     *
     * Also sets the $apiProblem member to the passed object.
     *
     * @param  ApiProblem $apiProblem
     * @return string
     */
    protected function renderApiProblem(ApiProblem $apiProblem)
    {
        $this->apiProblem   = $apiProblem;
        if ($this->displayExceptions) {
            $apiProblem->setDetailIncludesStackTrace(true);
        }
        return parent::render($apiProblem->toArray());
    }

    /**
	 * Initialize a <tt>HalLinks</tt> View Helper and injects the instance inside the Zend <tt>HelperPluginManager</tt>.
	 *
	 * @param  HelperPluginManager $helperPluginManager The Zend Helper Plugin Manager which stores references to Zend
	 *                                                  View Helpers.
	 */
	protected function injectHalLinksHelper(HelperPluginManager $helperPluginManager) {

	    // -- Gets the URL View Helper (used to generate links from route name and arguments) and the Serveur URL View
	    // -- Helper (used to gets the current host's URL like http://site.com)
	    $urlViewHelper = $helperPluginManager -> get('Url');
	    $serverUrlViewHelper = $helperPluginManager -> get('ServerUrl');

		$helper = null;

		// -- If a custom HalLinks Plugin / View Helper is set in the RESTFUL JSON Renderer we define it as the
		// -- HalLinks View Helper to register inside the Zend Framework 2 HelperPluginManager.
		if ($this -> halLinks !== null) {

			$helper = $this -> halLinks;

		}

		// -- Otherwise the default PhlyRestfully HalLinks / View Helper is registered in the HelperPluginManager.
		else {

			$helper = new \PhlyRestfully\Plugin\HalLinks();

		}

		// -- The Renderer / Zend View (in Zend Renderers and views are the same) to generate HAL Links to
		$helper -> setView($this);

		// -- Sets the Zend View Helpers which are used by the HalLinks View Helper
		$helper -> setServerUrlHelper($serverUrlViewHelper);
		$helper -> setUrlHelper($urlViewHelper);

		// -- Registers the HalLinks View Helper inside the Helper Plugin Manager
		$helperPluginManager -> setService('HalLinks', $helper);

	}
}
