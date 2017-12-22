<?php

namespace AS2;

class Headers extends \Zend\Mail\Headers
{
    const EOL = "\n";
    const FOLDING = " ";

    /**
     * Headers constructor.
     * @param array $headers
     */
    public function __construct($headers = null)
    {
        if ($headers) {
            $this->addHeaders($headers);
        }
    }

    /**
     * @param string $string
     * @param string $EOL
     * @return Headers
     */
    public static function fromString($string, $EOL = self::EOL)
    {
        return parent::fromString($string, $EOL);
    }

    /**
     * Return an instance of a PluginClassLocator, lazyload and inject map if necessary
     *
     * @return \Zend\Loader\PluginClassLocator
     */
    public function getPluginClassLoader()
    {
        if ($this->pluginClassLoader === null) {
            $this->pluginClassLoader = new Header\HeaderLoader();
        }
        return $this->pluginClassLoader;
    }

    /**
     * @return string
     */
    public function toString()
    {
        $headers = '';
        foreach ($this as $header) {
            if ($str = $header->toString()) {
                $headers .= $str . self::EOL;
            }
        }
        return $headers;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
}