<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Created = 'created';
    case Pending = 'pending';
    case RequiresAction = 'requires_action';
    case Processing = 'processing';
    case Successful = 'successful';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';

    public function isFinal(): bool
    {
        return in_array($this, [
            self::Successful,
            self::Failed,
            self::Cancelled,
            self::Expired,
            self::Refunded,
            self::PartiallyRefunded,
        ], true);
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Created => in_array($next, [self::Pending, self::RequiresAction, self::Processing, self::Failed, self::Cancelled, self::Expired], true),
            self::Pending => in_array($next, [self::Processing, self::Successful, self::Failed, self::Cancelled, self::Expired], true),
            self::RequiresAction => in_array($next, [self::Processing, self::Successful, self::Failed, self::Cancelled, self::Expired], true),
            self::Processing => in_array($next, [self::Successful, self::Failed, self::Cancelled, self::Expired], true),
            self::Successful => in_array($next, [self::Refunded, self::PartiallyRefunded], true),
            self::Failed, self::Cancelled, self::Expired, self::Refunded, self::PartiallyRefunded => false,
        };
    }
}
