<?php

/**
 * Composable execution pipeline with immutable steps.
 *
 * @package FAWpmcp\Abilities
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities;

use FAWpmcp\ValueObjects\Result;

/**
 * Immutable execution pipeline for ability operations.
 *
 * Implements a functional pipeline pattern where:
 * - Each step is a callable that takes input and returns a Result
 * - Steps execute in registration order
 * - Pipeline stops on first failure (short-circuit)
 * - Data flows between steps via Result::value
 * - Pipeline is immutable (pipe() returns new instance)
 *
 * @package FAWpmcp\Abilities
 */
final class ExecutionPipeline
{
    /**
     * Pipeline steps.
     *
     * @var array<callable>
     */
    private array $steps;

    /**
     * Constructor.
     *
     * @param array<callable> $steps Pipeline steps.
     */
    private function __construct(array $steps = array())
    {
        $this->steps = $steps;
    }

    /**
     * Create a new empty pipeline.
     *
     * Factory method for pipeline instantiation.
     *
     * @return self New empty pipeline.
     */
    public static function create(): self
    {
        return new self(array());
    }

    /**
     * Add a step to the pipeline.
     *
     * Returns a new pipeline instance with the step added.
     * Original pipeline is not modified (immutability).
     *
     * @param callable $step Step function: (mixed) => Result.
     * @return self New pipeline with step added.
     */
    public function pipe(callable $step): self
    {
        return new self(array_merge($this->steps, array( $step )));
    }

    /**
     * Execute the pipeline with the given input.
     *
     * Runs each step in order, passing the Result::value from each
     * step to the next. Stops on first failure.
     *
     * @param mixed $input Initial input value.
     * @return Result Final result (success with last value, or first failure).
     */
    public function execute(mixed $input): Result
    {
        if (empty($this->steps)) {
            return Result::success($input);
        }

        return array_reduce(
            $this->steps,
            function (Result $result, callable $step): Result {
                if (! $result->is_success) {
                    return $result;
                }
                return $step($result->value);
            },
            Result::success($input)
        );
    }
}
