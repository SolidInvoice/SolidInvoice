<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Entity;

use Carbon\CarbonImmutable;
use Carbon\Unit;
use Carbon\WeekDay;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Illuminate\Support\Arr;
use NumberFormatter;
use SolidInvoice\CronBundle\Enum\ScheduleEndType;
use SolidInvoice\CronBundle\Enum\ScheduleRecurringType;
use SolidInvoice\InvoiceBundle\Repository\RecurringOptionsRepository;
use Stringable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use function array_map;
use function in_array;
use function sprintf;

#[ORM\Entity(repositoryClass: RecurringOptionsRepository::class)]
#[ORM\Table(name: RecurringOptions::TABLE_NAME)]
#[Assert\Callback(callback: 'validateDays')]
class RecurringOptions implements Stringable
{
    public const TABLE_NAME = 'recurring_options';

    #[ORM\Column(name: 'id', type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    protected ?Ulid $id = null;

    #[ORM\Column(length: 15, enumType: ScheduleRecurringType::class)]
    #[Assert\NotBlank]
    private ScheduleRecurringType $type;

    /**
     * @var list<int>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $days = [];

    #[ORM\Column(length: 15, enumType: ScheduleEndType::class)]
    private ScheduleEndType $endType;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Assert\GreaterThan(value: 'today', message: 'End date must be in the future')]
    private ?DateTimeImmutable $endDate = null;

    #[ORM\Column(nullable: true)]
    private ?int $endOccurrence = null;

    #[ORM\OneToOne(inversedBy: 'recurringOptions', targetEntity: RecurringInvoice::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private RecurringInvoice $recurringInvoice;

    public function __construct()
    {
        $this->id = new Ulid();
    }

    public function getId(): Ulid
    {
        return $this->id;
    }

    public function getType(): ScheduleRecurringType
    {
        return $this->type;
    }

    public function setType(ScheduleRecurringType $type): static
    {
        $this->type = $type;

        if ($this->type->isDaily()) {
            $this->days = [];
        }

        return $this;
    }

    /**
     * @return list<WeekDay|int>
     */
    public function getDays(): array
    {
        //return array_map(WeekDay::from(...), $this->days);
        return $this->days;
    }

    /**
     * @param list<int|WeekDay> $days
     */
    public function setDays(array $days): static
    {
        if ($this->type->isDaily()) {
            $this->days = [];
        } else {
            $this->days = array_map(static function (int|WeekDay $day) {
                if ($day instanceof WeekDay) {
                    return $day->value;
                }

                return $day;
            }, $days);
        }

        return $this;
    }

    public function getEndType(): ScheduleEndType
    {
        return $this->endType;
    }

    public function setEndType(ScheduleEndType $endType): static
    {
        $this->endType = $endType;

        return $this;
    }

    public function getEndDate(): ?CarbonImmutable
    {
        if ($this->endDate instanceof DateTimeInterface) {
            return CarbonImmutable::instance($this->endDate);
        }

        if ($this->endType->isAfter()) {
            $totalOccurrence = 0;
            $start = CarbonImmutable::instance($this->recurringInvoice->getDateStart());

            $dates = $start->range($this->endOccurrence * 7, Unit::Day->interval());
            $endDate = null;

            foreach ($dates->getIterator() as $date) {
                /** @var CarbonImmutable $date */
                if (in_array($date->dayOfWeek, $this->days, true)) {
                    $totalOccurrence++;

                    if ($totalOccurrence === $this->endOccurrence) {
                        $endDate = $date;
                        break;
                    }
                }
            }

            return $endDate;
        }

        return null;
    }

    public function setEndDate(?DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getEndOccurrence(): ?int
    {
        return $this->endOccurrence;
    }

    public function setEndOccurrence(?int $endOccurrence): static
    {
        $this->endOccurrence = $endOccurrence;

        return $this;
    }

    public function getRecurringInvoice(): RecurringInvoice
    {
        return $this->recurringInvoice;
    }

    public function setRecurringInvoice(RecurringInvoice $recurringInvoice): void
    {
        $this->recurringInvoice = $recurringInvoice;
    }

    public function validateDays(ExecutionContextInterface $context): void
    {
        if (! isset($this->type)) {
            $context->buildViolation('You must select a recurrence type')
                ->atPath('type')
                ->addViolation();
            return;
        }

        if ([] === $this->days && $this->type->isWeekly()) {
            $context->buildViolation('You must select at least one day for weekly recurrence')
                ->atPath('days')
                ->addViolation();
        }

        if ([] === $this->days && $this->type->isMonthly()) {
            $context->buildViolation('You must select at least one day for monthly recurrence')
                ->atPath('days')
                ->addViolation();
        }

        if ([] === $this->days && $this->type->isYearly()) {
            $context->buildViolation('You must select at least one month for yearly recurrence')
                ->atPath('days')
                ->addViolation();
        }

        if (! isset($this->endType)) {
            $context->buildViolation('You must select an end type')
                ->atPath('endType')
                ->addViolation();
            return;
        }

        if ((0 === $this->endOccurrence || null === $this->endOccurrence) && $this->endType->isAfter()) {
            $context->buildViolation('You must specify the number of occurrences')
                ->atPath('endOccurrence')
                ->addViolation();
        }

        if (! $this->endDate instanceof DateTimeInterface && $this->endType->isOn()) {
            $context->buildViolation('You must specify an end date')
                ->atPath('endDate')
                ->addViolation();
        }
    }

    public function getFrequency(): string
    {
        $formatter = new NumberFormatter('en', NumberFormatter::ORDINAL);

        return 'Every ' . match ($this->type) {
            ScheduleRecurringType::DAILY => 'day',
            ScheduleRecurringType::WEEKLY => Arr::join(array_map(static fn (WeekDay $day) => $day->name, array_map(WeekDay::from(...), $this->days)), ', ', ' and '),
            ScheduleRecurringType::MONTHLY => sprintf('%s of the month', Arr::join(array_map(static fn ($day) => $formatter->format((int) $day), $this->days), ', ', ' and ')),
            ScheduleRecurringType::YEARLY => Arr::join(array_map(static fn ($month) => CarbonImmutable::create(null, $month)?->format('F'), $this->days), ', ', ' and '),
        };
    }

    public function __toString(): string
    {
        $string = $this->getFrequency();

        return $string . match ($this->endType) {
            ScheduleEndType::ON => sprintf(' from %s to %s', $this->recurringInvoice->getDateStart()?->format('d F Y'), $this->endDate?->format('d F Y')),
            ScheduleEndType::AFTER => sprintf(' from %s to %s (%d occurrences)', $this->recurringInvoice->getDateStart()?->format('d F Y'), $this->getEndDate()?->format('d F Y'), $this->endOccurrence),
            ScheduleEndType::NEVER => sprintf(' from %s', $this->recurringInvoice->getDateStart()?->format('d F Y')),
        };
    }
}
