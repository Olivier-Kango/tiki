<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_cart_info()
{
    return [
        'name' => tra('Cart'),
        'description' => tra('Displays the content of the cart, allows quantities to be modified and proceeds to payment.'),
        'prefs' => ['payment_feature'],
        'params' => [
            'ajax' => [
                'name' => tra('Use AJAX'),
                'description' => tra('Use AJAX services for managing the cart') . ' (y/n)',
                'filter' => 'alpha',
                'default' => 'n',
            ],
            'showItems' => [
                'name' => tra('Show Items'),
                'description' => tra('Shows the items in the cart as they are added') . ' (y/n)',
                'filter' => 'alpha',
                'default' => 'y',
            ],
            'showCount' => [
                'name' => tra('Show Item Count'),
                'description' => tra('Shows the number of items in the cart') . ' (y/n)',
                'filter' => 'alpha',
                'default' => 'n',
            ],
            'checkoutURL' => [
                'name' => tra('Checkout URL'),
                'description' => tra('Where to go to when the "Check-out" button is clicked but before the payment invoice is generated') . ' ' . tr('(Default empty: Goes to tiki-payment.php)'),
                'filter' => 'url',
                'default' => '',
            ],
            'postPaymentURL' => [
                'name' => tra('Post-Payment URL'),
                'description' => tra('Where to go to once the payment has been generated, will append "?invoice=xx" parameter on the URL for use in pretty trackers etc.') . ' ' . tr('(Default empty: Goes to tiki-payment.php)'),
                'filter' => 'url',
                'default' => '',
            ],
            'showWeight' => [
                'name' => tra('Show Total Weight'),
                'description' => tra('Shows the weight of the items in the cart') . ' (y/n)',
                'filter' => 'alpha',
                'default' => 'n',
            ],
            'weightUnit' => [
                'name' => tra('Weight Unit'),
                'description' => tra('Shown after the weight'),
                'filter' => 'alpha',
                'default' => 'g',
            ],
            'showItemButtons' => [
                'name' => tra('Show Item Buttons'),
                'description' => tra('Shows add, remove and delete buttons on items') . ' (y/n)',
                'filter' => 'alpha',
                'default' => 'n',
            ],
        ],
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_cart($mod_reference, &$module_params)
{
    global $jitRequest;

    $smarty = TikiLib::lib('smarty');
    $access = TikiLib::lib('access');
    $cartlib = TikiLib::lib('cart');

    $info = module_cart_info();
    $defaults = [];
    foreach ($info['params'] as $key => $param) {
        $defaults[$key] = $param['default'];
    }

    if (! empty($module_params['ajax']) && $module_params['ajax'] === 'y') {
        $smarty->assign('json_data', ' data-params=\'' . json_encode(array_filter($module_params)) . '\'');
    } else {
        $smarty->assign('json_data', '');
    }

    $module_params = array_merge($defaults, $module_params);

    if ($jitRequest->update->text() && $cart = $jitRequest->cart->asArray()) {
        foreach ($cart as $code => $quantity) {
            $cartlib->update_quantity($code, $quantity);
        }

        if ($module_params['ajax'] !== 'y') {
            $access->redirect($_SERVER['REQUEST_URI'], tra('The quantities in your cart were updated.'));
        }
    }

    if (isset($_POST['checkout'])) {
        if ($module_params['checkoutURL']) {
            $access->redirect($module_params['checkoutURL']);
        } else {
            $invoice = $cartlib->requestPayment();

            if ($invoice) {
                if ($module_params['postPaymentURL']) {
                    $delimiter = (strpos($module_params['postPaymentURL'], '?') === false) ? '?' : '&';
                    $access->redirect($module_params['postPaymentURL'] . $delimiter . 'invoice=' . (int)$invoice, tr('The order was recorded and is now awaiting payment. Reference number is %0.', $invoice));
                } else {
                    $access->redirect('tiki-payment.php?invoice=' . (int)$invoice, tr('The order was recorded and is now awaiting payment. Reference number is %0.', $invoice));
                }
            }
        }
    }

    $smarty->assign('cart_total', $cartlib->get_total_padded());
    $smarty->assign('cart_content', $cartlib->get_content());
    $smarty->assign('cart_weight', $cartlib->get_total_weight());
    $smarty->assign('cart_count', $cartlib->get_count());
}
