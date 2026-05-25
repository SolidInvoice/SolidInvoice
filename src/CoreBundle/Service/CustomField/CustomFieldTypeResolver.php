<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Service\CustomField;

use const JSON_THROW_ON_ERROR;
use DateTimeImmutable;
use DateTimeInterface;
use LogicException;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\CoreBundle\Enum\CustomFieldType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use function array_column;
use function array_filter;
use function array_map;
use function array_values;
use function implode;
use function is_array;
use function json_decode;
use function json_encode;
use function str_contains;

/**
 * @see \SolidInvoice\CoreBundle\Tests\Service\CustomField\CustomFieldTypeResolverTest
 */
final class CustomFieldTypeResolver
{
    /**
     * @return array{0: class-string, 1: array<string, mixed>}
     */
    public function formTypeAndOptions(CustomField $field): array
    {
        return match ($field->getType()) {
            CustomFieldType::TEXT => [TextType::class, []],
            CustomFieldType::TEXTAREA => [TextareaType::class, []],
            CustomFieldType::NUMBER => [NumberType::class, ['html5' => true]],
            CustomFieldType::DATE => [DateType::class, [
                'widget' => 'single_text',
                'input' => 'string',
                'input_format' => 'Y-m-d',
            ]],
            CustomFieldType::EMAIL => [EmailType::class, []],
            CustomFieldType::URL => [UrlType::class, []],
            CustomFieldType::CHECKBOX => [CheckboxType::class, []],
            CustomFieldType::SELECT => [ChoiceType::class, [
                'choices' => $this->choices($field),
                'placeholder' => 'Choose...',
            ]],
            CustomFieldType::MULTI_SELECT => [ChoiceType::class, [
                'choices' => $this->choices($field),
                'multiple' => true,
                'expanded' => false,
            ]],
            null => throw new LogicException('CustomField type must not be null.'),
        };
    }

    /**
     * @return list<Constraint>
     */
    public function constraints(CustomField $field): array
    {
        /** @var list<Constraint|null> $constraints */
        $constraints = [];
        if ($field->isRequired()) {
            $constraints[] = match ($field->getType()) {
                CustomFieldType::CHECKBOX => new Assert\IsTrue(),
                CustomFieldType::MULTI_SELECT => new Assert\Count(min: 1),
                default => new Assert\NotBlank(),
            };
        }

        $constraints[] = match ($field->getType()) {
            CustomFieldType::EMAIL => new Assert\Email(),
            CustomFieldType::URL => new Assert\Url(),
            CustomFieldType::NUMBER => new Assert\Type('numeric'),
            CustomFieldType::DATE => new Assert\Date(),
            CustomFieldType::SELECT => new Assert\Choice(choices: array_column($field->getOptions() ?? [], 'value')),
            CustomFieldType::MULTI_SELECT => new Assert\Choice(choices: array_column($field->getOptions() ?? [], 'value'), multiple: true),
            default => null,
        };

        /** @var list<Constraint> */
        return array_values(array_filter($constraints));
    }

    public function serialize(CustomField $field, mixed $input): ?string
    {
        if (in_array($input, [null, '', []], true)) {
            // Empty array for MULTI_SELECT serializes to '[]' so a deliberately empty selection is distinguishable from "not set".
            if ($field->getType() === CustomFieldType::MULTI_SELECT && is_array($input)) {
                return '[]';
            }

            return null;
        }

        return match ($field->getType()) {
            CustomFieldType::TEXT,
            CustomFieldType::TEXTAREA,
            CustomFieldType::EMAIL,
            CustomFieldType::URL,
            CustomFieldType::SELECT
                => (string) $input,
            CustomFieldType::NUMBER => (string) $input,
            CustomFieldType::DATE => $input instanceof DateTimeInterface ? $input->format('Y-m-d') : (string) $input,
            CustomFieldType::CHECKBOX => $input ? '1' : '0',
            CustomFieldType::MULTI_SELECT => json_encode(array_values((array) $input), JSON_THROW_ON_ERROR),
            null => throw new LogicException('CustomField type must not be null.'),
        };
    }

    public function deserialize(CustomField $field, ?string $stored): mixed
    {
        if ($stored === null) {
            return null;
        }

        return match ($field->getType()) {
            CustomFieldType::TEXT,
            CustomFieldType::TEXTAREA,
            CustomFieldType::EMAIL,
            CustomFieldType::URL,
            CustomFieldType::SELECT
                => $stored,
            CustomFieldType::NUMBER => str_contains($stored, '.') ? (float) $stored : (int) $stored,
            CustomFieldType::DATE => new DateTimeImmutable($stored),
            CustomFieldType::CHECKBOX => $stored === '1',
            CustomFieldType::MULTI_SELECT => json_decode($stored, true, flags: JSON_THROW_ON_ERROR),
            null => throw new LogicException('CustomField type must not be null.'),
        };
    }

    public function formatForDisplay(CustomField $field, ?string $stored): string
    {
        if ($stored === null || $stored === '') {
            return '—';
        }

        return match ($field->getType()) {
            CustomFieldType::CHECKBOX => $stored === '1' ? '✓' : '—',
            CustomFieldType::SELECT => $this->labelFor($field, $stored) ?? $stored,
            CustomFieldType::MULTI_SELECT => implode(', ', array_map(
                fn (string $v): string => $this->labelFor($field, $v) ?? $v,
                json_decode($stored, true, flags: JSON_THROW_ON_ERROR)
            )),
            default => $stored,
        };
    }

    /**
     * @return array<string, string>
     */
    private function choices(CustomField $field): array
    {
        $out = [];
        foreach ($field->getOptions() ?? [] as $opt) {
            $out[$opt['label']] = $opt['value'];
        }

        return $out;
    }

    private function labelFor(CustomField $field, string $value): ?string
    {
        foreach ($field->getOptions() ?? [] as $opt) {
            if ($opt['value'] === $value) {
                return $opt['label'];
            }
        }

        return null;
    }
}
