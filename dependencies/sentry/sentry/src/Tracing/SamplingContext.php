<?php
/**
 * @license MIT
 *
 * Modified by Mohamed Elwany on 28-May-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace TOT\Dependencies\Sentry\Tracing;

final class SamplingContext
{
    /**
     * @var TransactionContext|null The context of the transaction
     */
    private $transactionContext;

    /**
     * @var bool|null Sampling decision from the parent transaction, if any
     */
    private $parentSampled;

    /**
     * @var array<string, mixed>|null Additional context, depending on where the SDK runs
     */
    private $additionalContext;

    /**
     * Returns an instance populated with the data of the transaction context.
     */
    public static function getDefault(TransactionContext $transactionContext): self
    {
        $context = new self();
        $context->transactionContext = $transactionContext;
        $context->parentSampled = $transactionContext->getParentSampled();

        return $context;
    }

    public function getTransactionContext(): ?TransactionContext
    {
        return $this->transactionContext;
    }

    /**
     * Gets the sampling decision from the parent transaction, if any.
     */
    public function getParentSampled(): ?bool
    {
        return $this->parentSampled;
    }

    /**
     * Sets the sampling decision from the parent transaction, if any.
     */
    public function setParentSampled(?bool $parentSampled): self
    {
        $this->parentSampled = $parentSampled;

        return $this;
    }

    /**
     * Sets additional data that will be provided as a second argument to {@link \Sentry\startTransaction()}.
     *
     * @param array<string, mixed>|null $additionalContext
     */
    public function setAdditionalContext(?array $additionalContext): self
    {
        $this->additionalContext = $additionalContext;

        return $this;
    }

    /**
     * Gets the additional data that will be provided as a second argument to {@link \Sentry\startTransaction()}.
     *
     * @return array<string, mixed>|null
     */
    public function getAdditionalContext(): ?array
    {
        return $this->additionalContext;
    }
}
