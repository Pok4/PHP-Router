<?php
/**
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */
<?php
namespace PHPRouter;

use Fig\Http\Message\RequestMethodInterface;

class Route
{
    private $url;
    private $methods = array(
        RequestMethodInterface::METHOD_GET,
        RequestMethodInterface::METHOD_POST,
        RequestMethodInterface::METHOD_PUT,
        RequestMethodInterface::METHOD_DELETE,
    );
    private $target;
    private $name;
    private $filters = array();
    private $parameters = array();
    private $parametersByName;
    private $config;
    private $action;
    private $addonRegex = '';

    public function __construct($resource, array $config)
    {
        $this->url = $resource;
        $this->config = $config;
        $this->methods = isset($config['methods']) ? (array) $config['methods'] : array();
        $this->target = isset($config['target']) ? $config['target'] : null;
        $this->name = isset($config['name']) ? $config['name'] : null;
        $this->parameters = isset($config['parameters']) ? $config['parameters'] : array();

        if (isset($this->parameters['addon_regex'])) {
            $this->addonRegex = $this->parameters['addon_regex'];
        }
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($url)
    {
        $url = (string) $url;
        if (substr($url, -1) !== '/') {
            $url .= '/';
        }
        $this->url = $url;
    }

    public function getTarget()
    {
        return $this->target;
    }

    public function setTarget($target)
    {
        $this->target = $target;
    }

    public function getMethods()
    {
        return $this->methods;
    }

    public function setMethods(array $methods)
    {
        $this->methods = $methods;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = (string) $name;
    }

    public function setFilters(array $filters, $parametersByName = false)
    {
        $this->filters = $filters;
        $this->parametersByName = $parametersByName;
    }

    public function getRegex()
    {
        return preg_replace_callback('/(:\w+)/', array(&$this, 'substituteFilter'), $this->url) . $this->addonRegex;
    }

    private function substituteFilter($matches)
    {
        if (isset($matches[1], $this->filters[$matches[1]])) {
            return $this->filters[$matches[1]];
        }

        return '([\w\-\%]+)';
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters)
    {
        $this->parameters = array_merge($this->parameters, $parameters);

        if (isset($this->parameters['addon_regex'])) {
            $this->addonRegex = $this->parameters['addon_regex'];
        }
    }

    public function dispatch()
    {
        $action = explode('::', $this->config['_controller']);
        if ($this->parametersByName) {
            $this->parameters = array($this->parameters);
        }

        $this->action = !empty($action[1]) && trim($action[1]) !== '' ? $action[1] : null;

        if (!is_null($this->action)) {
            $instance = new $action[0];
            call_user_func_array(array($instance, $this->action), array_values($this->parameters));
        } else {
            $instance = new $action[0]($this->parameters);
        }
    }

    public function getAction()
    {
        return $this->action;
    }
}
