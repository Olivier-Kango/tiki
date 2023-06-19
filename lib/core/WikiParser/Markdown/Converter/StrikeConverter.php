<?php

declare(strict_types=1);

namespace Tiki\WikiParser\Markdown\Converter;

use League\HTMLToMarkdown\ElementInterface;
use League\HTMLToMarkdown\Converter\ConverterInterface;

class StrikeConverter implements ConverterInterface
{
    public function convert(ElementInterface $element): string
    {
        $value = $element->getValue();

        return '~~' . $value . '~~';
    }

    /**
     * @return string[]
     */
    public function getSupportedTags(): array
    {
        return ['strike'];
    }
}
