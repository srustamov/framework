<?php

namespace TT\Engine\Http\Routing;

/**
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link    https://github.com/srustamov/TT
 */

use Exception;


trait Parser
{

    protected $globalRegex = '[a-zA-Z0-9_=\-\?]+';

    protected $globalOptionalRegex = '?(\/[a-zA-Z0-9_=\-\?]+)?';

    /**
     * @param $uri
     * @param $arguments
     * @throws Exception
     */
    public function parseRouteParams($uri, $arguments)
    {
        preg_match_all('/{(.+?)}/', $uri, $keys);

        $keys = array_map(static function ($item) {
            return rtrim($item, '?');
        }, $keys[1]);

        $this->app['request']->setRouteParams(
            array_combine(
                array_slice($keys, 0, count($arguments)),
                $arguments
            )
        );
    }



    /**
     * @param $uri
     * @param $path
     * @param $patterns
     * @return array
     */
    public function parseRoute($uri, $path, $patterns): array
    {
        $route  = preg_replace_callback(
            '/{(.+?)}/',
            function ($matches) use ($patterns) {
                $match = array_pop($matches);
                $key   = rtrim($match, '?');
                $isOptional = $match !== $key;

                if (array_key_exists($key, $patterns)) {
                    if ($isOptional) {
                        return '?(\/' . $patterns[$key] . ')?';
                    }
                    return $patterns[$key];
                }

                return $isOptional ? $this->globalOptionalRegex : $this->globalRegex;
            },
            $path
        );

        $parts = explode('/', str_replace('?}', '}', $path));

        $arguments   = array_diff(
            array_replace(
                $parts,
                explode('/', $uri)
            ),
            $parts
        );

        return [array_values($arguments), $path, $route];
    }
}
