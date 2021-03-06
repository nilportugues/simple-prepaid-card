<?php

declare(strict_types=1);

namespace SimplePrepaidCard\CreditCard\Model;

use Money\Money;
use Ramsey\Uuid\UuidInterface;
use SimplePrepaidCard\Common\Model\DomainEvent;
use SimplePrepaidCard\Common\Model\MoneyDecimalFormatter;

final class FundsWereLoaded implements DomainEvent
{
    /** @var UuidInterface */
    private $creditCardId;

    /** @var UuidInterface */
    private $holderId;

    /** @var Money */
    private $amount;

    /** @var string */
    private $reason;

    /** @var Money */
    private $balance;

    /** @var Money */
    private $availableBalance;

    /** @var \DateTime */
    private $at;

    public function __construct(UuidInterface $creditCardId, UuidInterface $holderId, Money $amount, string $reason, Money $balance, Money $availableBalance, \DateTime $at)
    {
        $this->creditCardId     = $creditCardId;
        $this->amount           = $amount;
        $this->reason           = $reason;
        $this->balance          = $balance;
        $this->availableBalance = $availableBalance;
        $this->at               = $at;
        $this->holderId         = $holderId;
    }

    public function creditCardId(): UuidInterface
    {
        return $this->creditCardId;
    }

    public function holderId(): UuidInterface
    {
        return $this->holderId;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function balance(): Money
    {
        return $this->balance;
    }

    public function availableBalance(): Money
    {
        return $this->availableBalance;
    }

    public function at(): \DateTime
    {
        return $this->at;
    }

    public function __toString(): string
    {
        return sprintf(
            'Funds "%s" GBP were loaded onto a credit card with id "%s" at "%s".',
            MoneyDecimalFormatter::create()->format($this->amount()),
            $this->creditCardId(),
            $this->at()->format('Y:m:d h:i a')
        );
    }
}
