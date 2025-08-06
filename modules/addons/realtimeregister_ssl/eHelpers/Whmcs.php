<?php

namespace AddonModule\RealtimeRegisterSsl\eHelpers;

use WHMCS\View\Formatter\Price;

class Whmcs
{
    public static function savelogActivityRealtimeRegisterSsl($msg)
    {
        $apiConf = (new \AddonModule\RealtimeRegisterSsl\models\apiConfiguration\Repository())->get();
        if ($apiConf->save_activity_logs) {
            logActivity($msg);
        }
    }

    public static function getPricingInfo($pid, $discount = 0, $inclconfigops = false, $upgrade = false)
    {
        global $CONFIG;
        global $_LANG;
        global $currency;
        $result = select_query("tblproducts", "", ["id" => $pid]);
        $data = mysql_fetch_array($result);
        $paytype = $data["paytype"];
        $freedomain = $data["freedomain"];
        $freedomainpaymentterms = $data["freedomainpaymentterms"];
        if (!isset($currency["id"])) {
            $currency = getCurrency();
        }

        $result = select_query(
            "tblpricing",
            "",
            ["type" => "product", "currency" => $currency["id"], "relid" => $pid]
        );
        $data = mysql_fetch_array($result);
        $multiplier = (100 - $discount) / 100;
        $msetupfee = 0;
        $asetupfee = 0;
        $bsetupfee = 0;
        $tsetupfee = 0;
        $monthly = 0;
        $annually = 0;
        $biennially = 0;
        $triennially = 0;
        $configoptions = new \WHMCS\Product\ConfigOptions();
        $freedomainpaymentterms = explode(",", $freedomainpaymentterms);
        $monthlypricingbreakdown = $CONFIG["ProductMonthlyPricingBreakdown"];
        $minprice = 0;
        $setupFee = 0;
        $mincycle = "";
        if ($paytype == "free") {
            $pricing["type"] = $mincycle = "free";
        } else {
            if ($paytype == "onetime") {
                if ($inclconfigops) {
                    $msetupfee += $configoptions->getBasePrice($pid, "msetupfee") * $multiplier;
                    $monthly += $configoptions->getBasePrice($pid, "monthly") * $multiplier;
                }

                $minprice = $monthly;
                $setupFee = $msetupfee;
                $pricing["type"] = $mincycle = "onetime";
                $pricing["onetime"] = new Price($monthly, $currency);
                if ($msetupfee != "0.00") {
                    $pricing["onetime"] .= " + " . new Price($msetupfee, $currency)
                        . " " . $_LANG["ordersetupfee"];
                }

                if (in_array("onetime", $freedomainpaymentterms) && $freedomain && !$upgrade) {
                    $pricing["onetime"] .= " (" . $_LANG["orderfreedomainonly"] . ")";
                }
            } else {
                if ($paytype == "recurring") {
                    $pricing["type"] = "recurring";
                    if (0 <= $monthly) {
                        if ($inclconfigops) {
                            $msetupfee += $configoptions->getBasePrice($pid, "msetupfee") * $multiplier;
                            $monthly += $configoptions->getBasePrice($pid, "monthly") * $multiplier;
                        }

                        if (!$mincycle) {
                            $minprice = $monthly;
                            $setupFee = $msetupfee;
                            $mincycle = "monthly";
                            $minMonths = 1;
                        }

                        if ($monthlypricingbreakdown) {
                            $pricing["monthly"] = $_LANG["orderpaymentterm1month"] . " - " . new Price(
                                    $monthly,
                                    $currency
                                );
                        } else {
                            $pricing["monthly"] = new Price(
                                    $monthly, $currency
                                ) . " " . $_LANG["orderpaymenttermmonthly"];
                        }

                        if ($msetupfee != "0.00") {
                            $pricing["monthly"] .= " + " . new Price(
                                    $msetupfee, $currency
                                ) . " " . $_LANG["ordersetupfee"];
                        }

                        if (in_array("monthly", $freedomainpaymentterms) && $freedomain && !$upgrade) {
                            $pricing["monthly"] .= " (" . $_LANG["orderfreedomainonly"] . ")";
                        }
                    }

                    if (0 <= $annually) {
                        if ($inclconfigops) {
                            $asetupfee += $configoptions->getBasePrice($pid, "asetupfee") * $multiplier;
                            $annually += $configoptions->getBasePrice($pid, "annually") * $multiplier;
                        }

                        if (!$mincycle) {
                            $minprice = ($monthlypricingbreakdown ? $annually / 12 : $annually);
                            $setupFee = $asetupfee;
                            $mincycle = "annually";
                            $minMonths = 12;
                        }

                        if ($monthlypricingbreakdown) {
                            $pricing["annually"] = $_LANG["orderpaymentterm12month"] . " - " . new Price(
                                    $annually / 12, $currency
                                );
                        } else {
                            $pricing["annually"] = new Price(
                                    $annually, $currency
                                ) . " " . $_LANG["orderpaymenttermannually"];
                        }

                        if ($asetupfee != "0.00") {
                            $pricing["annually"] .= " + " . new Price(
                                    $asetupfee, $currency
                                ) . " " . $_LANG["ordersetupfee"];
                        }

                        if (in_array("annually", $freedomainpaymentterms) && $freedomain && !$upgrade) {
                            $pricing["annually"] .= " (" . $_LANG["orderfreedomainonly"] . ")";
                        }
                    }

                    if (0 <= $biennially) {
                        if ($inclconfigops) {
                            $bsetupfee += $configoptions->getBasePrice($pid, "bsetupfee") * $multiplier;
                            $biennially += $configoptions->getBasePrice($pid, "biennially") * $multiplier;
                        }

                        if (!$mincycle) {
                            $minprice = ($monthlypricingbreakdown ? $biennially / 24 : $biennially);
                            $setupFee = $bsetupfee;
                            $mincycle = "biennially";
                            $minMonths = 24;
                        }

                        if ($monthlypricingbreakdown) {
                            $pricing["biennially"] = $_LANG["orderpaymentterm24month"] . " - " . new Price(
                                    $biennially / 24, $currency
                                );
                        } else {
                            $pricing["biennially"] = new Price(
                                    $biennially, $currency
                                ) . " " . $_LANG["orderpaymenttermbiennially"];
                        }

                        if ($bsetupfee != "0.00") {
                            $pricing["biennially"] .= " + " . new Price(
                                    $bsetupfee, $currency
                                ) . " " . $_LANG["ordersetupfee"];
                        }

                        if (in_array("biennially", $freedomainpaymentterms) && $freedomain && !$upgrade) {
                            $pricing["biennially"] .= " (" . $_LANG["orderfreedomainonly"] . ")";
                        }
                    }

                    if (0 <= $triennially) {
                        if ($inclconfigops) {
                            $tsetupfee += $configoptions->getBasePrice($pid, "tsetupfee") * $multiplier;
                            $triennially += $configoptions->getBasePrice($pid, "triennially") * $multiplier;
                        }

                        if (!$mincycle) {
                            $minprice = ($monthlypricingbreakdown ? $triennially / 36 : $triennially);
                            $setupFee = $tsetupfee;
                            $mincycle = "triennially";
                            $minMonths = 36;
                        }

                        if ($monthlypricingbreakdown) {
                            $pricing["triennially"] = $_LANG["orderpaymentterm36month"] . " - " . new Price(
                                    $triennially / 36, $currency
                                );
                        } else {
                            $pricing["triennially"] = new Price(
                                    $triennially, $currency
                                ) . " " . $_LANG["orderpaymenttermtriennially"];
                        }

                        if ($tsetupfee != "0.00") {
                            $pricing["triennially"] .= " + " . new Price(
                                    $tsetupfee, $currency
                                ) . " " . $_LANG["ordersetupfee"];
                        }

                        if (in_array("triennially", $freedomainpaymentterms) && $freedomain && !$upgrade) {
                            $pricing["triennially"] .= " (" . $_LANG["orderfreedomainonly"] . ")";
                        }
                    }
                }
            }
        }

        $pricing["hasconfigoptions"] = $configoptions->hasConfigOptions($pid);
        if (isset($pricing["onetime"])) {
            $pricing["cycles"]["onetime"] = $pricing["onetime"];
        }

        if (isset($pricing["monthly"])) {
            $pricing["cycles"]["monthly"] = $pricing["monthly"];
        }

        if (isset($pricing["annually"])) {
            $pricing["cycles"]["annually"] = $pricing["annually"];
        }

        if (isset($pricing["biennially"])) {
            $pricing["cycles"]["biennially"] = $pricing["biennially"];
        }

        if (isset($pricing["triennially"])) {
            $pricing["cycles"]["triennially"] = $pricing["triennially"];
        }

        $pricing["rawpricing"] = [
            "msetupfee" => format_as_currency($msetupfee),
            "asetupfee" => format_as_currency($asetupfee),
            "bsetupfee" => format_as_currency($bsetupfee),
            "tsetupfee" => format_as_currency($tsetupfee),
            "monthly" => format_as_currency($monthly),
            "annually" => format_as_currency($annually),
            "biennially" => format_as_currency($biennially),
            "triennially" => format_as_currency($triennially)
        ];
        $pricing["minprice"] = [
            "price" => new Price($minprice, $currency),
            "setupFee" => (0 < $setupFee ? new Price($setupFee, $currency) : 0),
            "cycle" => ($monthlypricingbreakdown && $paytype == "recurring" ? "monthly" : $mincycle),
            "simple" => (new Price($minprice, $currency))->toPrefixed()
        ];
        if (isset($minMonths)) {
            switch ($minMonths) {
                case 12:
                    $langVar =
                        ($monthlypricingbreakdown ? "shoppingCartProductPerMonth" : "shoppingCartProductPerYear");
                    $count = "";
                    break;
                case 24:
                    $langVar =
                        ($monthlypricingbreakdown ? "shoppingCartProductPerMonth" : "shoppingCartProductPerYear");
                    $count = "2 ";
                    break;
                case 36:
                    $langVar =
                        ($monthlypricingbreakdown ? "shoppingCartProductPerMonth" : "shoppingCartProductPerYear");
                    $count = "3 ";
                    break;
                default:
                    $langVar = "shoppingCartProductPerMonth";
                    $count = "";
            }
            $pricing["minprice"]["cycleText"] = \Lang::trans(
                $langVar,
                [":count" => $count, ":price" => $pricing["minprice"]["simple"]]
            );
            $pricing["minprice"]["cycleTextWithCurrency"] = \Lang::trans(
                $langVar,
                [":count" => $count, ":price" => $pricing["minprice"]["price"]]
            );
        }

        return $pricing;
    }
}
