<?php
/**
 * @license MIT
 *
 * Modified by Mohamed Elwany on 28-May-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace TOT\Dependencies\Sentry\HttpClient;

use TOT\Dependencies\Sentry\Options;

interface HttpClientInterface
{
    public function sendRequest(Request $request, Options $options): Response;
}
