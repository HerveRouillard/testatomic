<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
$result     = $_POST['json'];
$order      = json_decode($result);
$parcel     = $order->Delivery->Parcel;
$sender     = $order->Delivery->Sender;
$products   = $order->Products;
$recipient  = $order->Delivery->Recipient;
$invoice    = $order->Invoice;
//traitement de l'obligatoire :
$needed     = array('OrderID', 'OrderKey', 'OrderDate', 'Status', 'PaymentInfo', 'Currency',
    'Delivery' => array(
        'Parcel'    => array(
            'ShippingProviderCode', 'ShippingProviderLib'
        ),
        'Recipient' => array(
            'RecipLastName', 'RecipAdr1', 'RecipCountryCode', 'RecipCity', 'RecipPhoneNumber', 'Recipemail',
        )
    ),
);
$resultat   = array();
$need_tarif = false;
// on verifie lee country lib
if (!empty($recipient->RecipCountryCode)) {
    $need_tarif = checkTarif($recipient->RecipCountryCode);
}
if ($need_tarif) {
    $needed[]                       = 'InvoicingDate';
    $needed[]                       = 'PaymentInfo';
    $needed[]                       = 'PaymentInfo';
    $needed['Delivery']['Parcel'][] = 'ParcelShippingPriceExclTax';
    $needed['Delivery']['Parcel'][] = 'ParcelShippingTax';
    $needed['Invoice']              = array('InvoiceKey', 'BillingLastName', 'BillingAdr1', 'BillingCountryLib', 'BillingCity',
        'BillingZipCode');
}

//traitement des produits
foreach ($products as $produit) {
    if (empty($produit->SKU)) {
        $resultat[] = 'SKU manquant';
    }
    if (empty($produit->Quantity)) {
        $resultat[] = 'Quantité manquante';
    }
    if ($need_tarif && empty($produit->SubTotalPriceExclTax)) {
        $resultat[] = 'SubTotalPriceExclTax';
    }
    if ($need_tarif && empty($produit->SubTotalTax)) {
        $resultat[] = 'SubTotalTax';
    }
}

// on test les produits
tester($needed, $order, $resultat);

function tester($needed, $order, &$resultat)
{
    foreach ($needed as $key => $valeur) {
        if (is_array($valeur)) {

            tester($valeur, $order->$key, $resultat);
        }
        if (is_string($valeur) && empty($order->$valeur)) {
            $resultat[] = $valeur;
        }
    }
}

function checkTarif($country_code)
{
    $no_needed = array('FR', 'BE', 'BG', 'CZ', 'DK', 'DE', 'EE', 'IE', 'EL', 'ES', 'HR', 'IT', 'CY', 'LV', 'LT', 'LU', 'HU',
        'MT', 'NL', 'AT', 'PL', 'PT', 'RO', 'SI', 'SK', 'FI', 'SE', 'UK');
    if (!in_array($country_code, $no_needed)) {
        return true;
    }
    return false;
}

echo "<pre>".print_r($resultat, true)."</pre>";
