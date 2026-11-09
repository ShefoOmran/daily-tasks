<?php

namespace App;

enum TransactionType: string
{
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
    case Subscription = 'subscription';
}
