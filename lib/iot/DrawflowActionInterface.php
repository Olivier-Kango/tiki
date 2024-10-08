<?php

namespace Tiki\Lib\Iot;

use Tiki\Lib\Iot\DrawflowNodeType;

interface DrawflowActionInterface
{
    public function getName(): string;
    public function getDescription(): string;
    public function getType(): DrawflownodeType;
    public function getTemplate(array $config): string;
    public function execute(mixed $input, ?string $user_input): bool|array;
}
