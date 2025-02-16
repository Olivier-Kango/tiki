<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;
use TikiLib;

// @param numeric $id: id of the payment
// @params url $returnurl: optional return url
class Payment extends Base
{
    public function handle($params, Template $template)
    {
        global $prefs, $user, $globalperms;
        $userlib = TikiLib::lib('user');
        $tikilib = TikiLib::lib('tiki');
        $paymentlib = TikiLib::lib('payment');
        $smarty = TikiLib::lib('smarty');
        $invoice = (int) $params['id'];

        $objectperms = \Perms::get('payment', $invoice);
        $info = $paymentlib->get_payment($invoice);
        if ($user && isset($info['userId']) && $info['userId'] == $userlib->get_user_id($user)) {
            $theguy = true;
        } else {
            $theguy = false;
        }
        $smarty->assign('ccresult_ok', false);

        // Unpaid payments can be seen by anyone as long as they know the number
        // Just like your bank account, anyone can drop money in it.
        if (
            $info &&
            $objectperms->payment_view &&
            (
                (
                    (
                        $info['state'] == 'outstanding' ||
                        $info['state'] == 'overdue'
                    ) &&
                    $prefs['payment_user_only_his_own'] != 'y'
                ) ||
                (
                    $info['state'] == 'past' &&
                    $prefs['payment_user_only_his_own_past'] != 'y'
                ) ||
                $theguy ||
                $objectperms->payment_admin ||
                (
                    (
                        $info['state'] == 'outstanding' ||
                        $info['state'] == 'overdue'
                    ) &&
                    $info['userId'] == '-1' &&
                    $prefs['payment_anonymous_allowed'] == 'y'
                )
            )
        ) {
            if ($prefs['payment_system'] == 'cclite' && isset($_POST['cclite_payment_amount']) && $_POST['cclite_payment_amount'] == $info['amount_remaining']) {
                global $cclitelib;
                require_once 'lib/payment/cclitelib.php';
                $access = TikiLib::lib('access');
                $cartlib = TikiLib::lib('cart');

                //$access->check_authenticity( tr('Transfer currency? %0 %1?', $info['amount'], $info['currency'] ));

                // check currency matches
                if (empty($params['registry'])) {
                    $params['registry'] = $cclitelib->get_registry();
                }

                if (empty($info['currency'])) {
                    $info['currency'] = $cclitelib->get_currency($params['registry']);
                } else {
                    if ($info['currency'] != substr($cclitelib->get_currency($params['registry']), 0, 3)) {
                        return tr(
                            'Currency in payment (%0) does not match the currency for that registry (%1).',
                            $info['currency'],
                            $cclitelib->get_currency($params['registry'])
                        );
                    }
                }

                // no notification callback in cclite yet, so have to assume true for now (pending checking in perform_trade)
                $result = $cclitelib->pay_invoice($invoice, $info['amount'], $info['currency'], $params['registry']);
                if ($result) {
                    // ccresults are set in smarty by the perform_trade behaviour
                    $smarty->assign('ccresult', $result);
                    $smarty->assign('ccresult_ok', $result);
                } else {
                    $smarty->assign('ccresult', tr('Payment was sent but verification is not currently available (this feature is a work in progress)'));
                }
            } elseif ($prefs['payment_system'] == 'tikicredits') {
                require_once 'lib/payment/creditspaylib.php';
                $userpaycredits = new \UserPayCredits();
                $userpaycredits->setPrice($info['amount_remaining']);
                $smarty->assign('userpaycredits', $userpaycredits->credits);
            }

            $ilpinvoicepayment = TikiLib::lib('ilpinvoicepayment');
            if ($prefs['payment_system'] == 'ilp' && $ilpinvoicepayment->isEnabled()) {
                $info['ilp_invoice_url'] = $ilpinvoicepayment->getPointer($invoice);
            }

            $info['fullview'] = $objectperms->payment_view || $theguy;

            if (! empty($smarty->tpl_vars['returnurl']->value)) {
                $returl = $smarty->tpl_vars['returnurl'];
                $info['returnurl'] = TikiLib::tikiUrl($returl);
            }

            if (! empty($params['returnurl']) && empty($result)) {
                $info['returnurl'] = TikiLib::tikiUrl($params['returnurl']);
                $info['returnurl'] .= (strstr($params['returnurl'], '?') ? '&' : '?') . "invoice=$invoice";
            }
            $smarty->assign('payment_info', $info);
            $smarty->assign('payment_detail', TikiLib::lib('parser')->parse_data(htmlspecialchars($info['detail'] ?? "")));

            $smarty_cache_id = $smarty_compile_id = $prefs['language'] . md5('tiki-payment-single.tpl');
            return $smarty->fetch('tiki-payment-single.tpl', $smarty_cache_id, $smarty_compile_id);
        } else {
            $repeat = false;

            return smarty_block_remarksbox(
                [
                    'type' => 'warning',
                    'title' => tra('Payment error'),
                ],
                tra('This invoice does not exist or access to it is restricted.'),
                $smarty->getEmptyInternalTemplate(),
                $repeat
            );
        }
    }
}
