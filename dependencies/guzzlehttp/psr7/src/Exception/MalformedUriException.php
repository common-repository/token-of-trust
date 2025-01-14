<?php
/**
 * @license MIT
 *
 * Modified by tokenoftrust on 22-January-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace TOT\Dependencies\GuzzleHttp\Psr7\Exception;

use InvalidArgumentException;

/**
 * Exception thrown if a URI cannot be parsed because it's malformed.
 */
class MalformedUriException extends InvalidArgumentException
{
}
