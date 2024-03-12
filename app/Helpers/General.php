<?php

use App\Models\Wallet;
use App\Models\UserStreak;
use App\Models\WalletTrading;
use App\Models\StreakChallenge;
use App\Models\WalletTradingDemo;
function walletBalance($id)
{
    $userCryptoIn  = Wallet::where('user_id', $id)->where('type', 'In')->sum('amount');
    $userCryptoOut = Wallet::where('user_id', $id)->where('type', 'Out')->sum('amount');
    $balance       = $userCryptoIn - $userCryptoOut;
    return $balance;
}
function WalletTradingBalance($id)
{
    $userTradingIn        = WalletTrading::where('user_id', $id)->where('type', 'In')->sum('amount');
    $userTradingOut       = WalletTrading::where('user_id', $id)->where('type', 'Out')->sum('amount');
    $WalletTradingBalance = $userTradingIn - $userTradingOut;
    return $WalletTradingBalance;
}
function WalletTradingBalanceDemo($id)
{
    $userTradingIn        = WalletTradingDemo::where('user_id', $id)->where('type', 'In')->sum('amount');
    $userTradingOut       = WalletTradingDemo::where('user_id', $id)->where('type', 'Out')->sum('amount');
    $WalletTradingBalance = $userTradingIn - $userTradingOut;
    return $WalletTradingBalance;
}
function getMegaAmount()
{   
    $streak               = StreakChallenge::sum('amount');
    $userStreak           = UserStreak::sum('amount');
    $streakAmount         = $streak - $userStreak;
    $megaAmount           = $streakAmount * (0.1 / 100) * 5;
    return $megaAmount;
}
function getStreakAmount()
{   
    $streak               = StreakChallenge::sum('amount');
    $userStreak           = UserStreak::sum('amount');
    $streakAmount         = $streak - $userStreak;
    return $streakAmount;
}