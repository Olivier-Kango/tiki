<?php

declare(strict_types=1);

namespace Tiki\WikiParser\Markdown\Converter;

use League\HTMLToMarkdown\ElementInterface;
use League\HTMLToMarkdown\Converter\ConverterInterface;

class DefinitionListConverter implements ConverterInterface
{
    public function convert(ElementInterface $element): string
    {
        $elementType = $element->getTagName();

        $value = $element->getValue();

        if ($elementType === 'dt') {
            $value = trim($value);
        } elseif ($elementType === 'dd') {
            $value = ': ' . trim($value);
        }

        $value .= "\n";

        return $value;
    }

    /**
     * @return string[]
     */
    public function getSupportedTags(): array
    {
        return ['dl', 'dt', 'dd'];
    }
}
