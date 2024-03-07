<?php

use App\Models\Wallet;
use App\Models\WalletTrading;
function walletBalance($id)
{
    $userCryptoIn = Wallet::where('user_id', $id)->where('type', 'In')->sum('amount');
    $userCryptoOut = Wallet::where('user_id', $id)->where('type', 'Out')->sum('amount');
    $balance = $userCryptoIn - $userCryptoOut;
    return $balance;
}
function WalletTradingBalance($id)
{
    $userTradingIn = WalletTrading::where('user_id', $id)->where('type', 'In')->sum('amount');
    $userTradingOut = WalletTrading::where('user_id', $id)->where('type', 'Out')->sum('amount');
    $WalletTradingBalance = $userTradingIn - $userTradingOut;
    return $WalletTradingBalance;
}
