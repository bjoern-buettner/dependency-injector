<?php
declare(strict_types=1);

namespace Me\BjoernBuettner\DependencyInjector\DTOs;

class IntersectionMap
{
    /**
     * @var string[]
     */
    public readonly array $intersectionMap;

    public function __construct(
        public readonly string $className,
        string ...$intersectionMap,
    ) {
        sort($intersectionMap, SORT_STRING);
        $this->intersectionMap = $intersectionMap;
    }
}