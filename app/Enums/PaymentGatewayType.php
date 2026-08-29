<?php

namespace App\Enums;

enum PaymentGatewayType: string
{
    case Mpesa = 'mpesa';
    case PayPal = 'paypal';
    case Card = 'card';
    case Stripe = 'stripe';
}
