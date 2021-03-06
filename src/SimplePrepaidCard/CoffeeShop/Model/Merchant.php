<?php

declare(strict_types=1);

namespace SimplePrepaidCard\CoffeeShop\Model;

use Money\Money;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use SimpleBus\Message\Recorder\ContainsRecordedMessages;
use SimpleBus\Message\Recorder\PrivateMessageRecorderCapabilities;
use SimplePrepaidCard\CoffeeShop\Model\CreditCardProvider\CreditCardProvider;
use SimplePrepaidCard\Common\Model\Aggregate;

final class Merchant implements ContainsRecordedMessages, Aggregate
{
    use PrivateMessageRecorderCapabilities;

    const MERCHANT_ID = '49ce95dc-bb15-4c45-9df4-7b8c0a9f8896';

    /** @var int */
    private $id;

    /** @var string */
    private $merchantId;

    /** @var Money */
    private $authorized;

    /** @var string */
    private $authorizedBy;

    /** @var Money */
    private $captured;

    public function __construct(UuidInterface $merchantId)
    {
        $this->merchantId = $merchantId;
        $this->authorized = Money::GBP(0);
        $this->captured   = Money::GBP(0);
    }

    public static function create(): self
    {
        return new self(Uuid::fromString(self::MERCHANT_ID));
    }

    public function authorize(Money $amount, UuidInterface $authorizedBy)
    {
        $this->guardNegativeAmount($amount);

        $this->authorized   = $this->authorized()->add($amount);
        $this->authorizedBy = $authorizedBy;

        $this->record(
            new MerchantWasAuthorized(
                $this->merchantId(),
                $this->authorizedBy(),
                $amount,
                $this->authorized(),
                new \DateTime()
            )
        );
    }

    public function capture(Money $amount, CreditCardProvider $creditCardProvider)
    {
        $this->guardNegativeAmount($amount);
        $this->guardAgainstCaptureMoreThanAuthorizedTo($amount);

        $creditCardProvider->capture($amount, $this->authorizedBy());

        $this->authorized = $this->authorized()->subtract($amount);
        $this->captured   = $this->captured()->add($amount);

        $this->record(
            new AuthorizationWasCaptured(
                $this->merchantId(),
                $this->authorizedBy(),
                $amount,
                $this->authorized(),
                $this->captured(),
                new \DateTime()
            )
        );
    }

    public function reverse(Money $amount, CreditCardProvider $creditCardProvider)
    {
        $this->guardNegativeAmount($amount);
        $this->guardAgainstReverseMoreThanAuthorizedTo($amount);

        $creditCardProvider->reverse($amount, $this->authorizedBy());

        $this->authorized = $this->authorized()->subtract($amount);

        $this->record(
            new AuthorizationWasReversed(
                $this->merchantId(),
                $this->authorizedBy(),
                $amount,
                $this->authorized(),
                $this->captured(),
                new \DateTime()
            )
        );
    }

    public function refund(Money $amount, CreditCardProvider $creditCardProvider)
    {
        $this->guardNegativeAmount($amount);
        $this->guardAgainstRefundMoreThanCaptured($amount);

        $creditCardProvider->refund($amount, $this->authorizedBy());

        $this->captured = $this->captured()->subtract($amount);

        $this->record(
            new CapturedWasRefunded(
                $this->merchantId(),
                $this->authorizedBy(),
                $amount,
                $this->authorized(),
                $this->captured(),
                new \DateTime()
            )
        );
    }

    public function authorized(): Money
    {
        return $this->authorized;
    }

    public function merchantId(): UuidInterface
    {
        return $this->merchantId;
    }

    public function captured(): Money
    {
        return $this->captured;
    }

    public function authorizedBy()
    {
        if (null === $this->authorizedBy) {
            return;
        }

        return $this->authorizedBy;
    }

    private function guardNegativeAmount(Money $amount)
    {
        if ($amount->isNegative()) {
            throw CannotUseNegativeAmount::with($this->merchantId());
        }
    }

    private function guardAgainstCaptureMoreThanAuthorizedTo(Money $amount)
    {
        if (null === $this->authorizedBy() || $this->authorized()->subtract($amount)->isNegative()) {
            throw CannotCaptureMoreThanAuthorized::with($this->merchantId());
        }
    }

    private function guardAgainstReverseMoreThanAuthorizedTo(Money $amount)
    {
        if ($this->authorized()->subtract($amount)->isNegative()) {
            throw CannotReverseMoreThanAuthorized::with($this->merchantId());
        }
    }

    private function guardAgainstRefundMoreThanCaptured(Money $amount)
    {
        if ($this->captured()->subtract($amount)->isNegative()) {
            throw CannotRefundMoreThanCaptured::with($this->merchantId());
        }
    }
}
