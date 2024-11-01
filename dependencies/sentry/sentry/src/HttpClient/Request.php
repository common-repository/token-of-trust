<?php
/**
 * @license MIT
 *
 * Modified by Mohamed Elwany on 28-May-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace TOT\Dependencies\Sentry\HttpClient;

/**
 * @internal
 */
final class Request
{
    /**
     * @var string
     */
    private $stringBody;

    public function hasStringBody(): bool
    {
        return $this->stringBody !== null;
    }

    public function getStringBody(): ?string
    {
        return $this->stringBody;
    }

    public function setStringBody(string $stringBody): void
    {
        $this->stringBody = $stringBody;
    }
}
