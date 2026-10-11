<?php

declare(strict_types=1);

namespace Patterns\Tests\Fakes;

use InvalidArgumentException;
use Patterns\IOperator;

/**
 * The smallest thing that is an operator: three methods, no base class, no
 * constructor arguments. If the contract ever needs more than this, the contract
 * has grown a dependency it should not have.
 */
final class TestOperator implements IOperator
{
    /** @var array<string, array{call: callable}> */
    private array $catalogue = [];

    /** @param array<string, callable> $tools */
    public function __construct(array $tools = [])
    {
        foreach ($tools as $name => $call) {
            $this->catalogue[$name] = ['call' => $call];
        }
    }

    public function tools(): array
    {
        $catalogue = [];

        foreach ($this->catalogue as $name => $entry) {
            $catalogue[$name] = [
                'name'        => $name,
                'description' => 'does ' . $name,
                'parameters'  => ['type' => 'object', 'properties' => [], 'required' => []],
            ];
        }

        return $catalogue;
    }

    public function execute(string $tool, array $arguments = []): mixed
    {
        if (!isset($this->catalogue[$tool])) {
            throw new InvalidArgumentException('unknown tool: ' . $tool);
        }

        return ($this->catalogue[$tool]['call'])($arguments);
    }

    public function help(): string
    {
        return sprintf('I can do %d things.', count($this->catalogue));
    }
}
