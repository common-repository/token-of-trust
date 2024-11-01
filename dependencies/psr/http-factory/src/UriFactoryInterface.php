<?php
/**
 * @license MIT
 *
 * Modified by tokenoftrust on 22-January-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace TOT\Dependencies\Psr\Http\Message;

interface UriFactoryInterface
{
    /**
     * Create a new URI.
     *
     * @param string $uri
     *
     * @return UriInterface
     *
     * @throws \InvalidArgumentException If the given URI cannot be parsed.
     */
    public function createUri(string $uri = ''): UriInterface;
}
