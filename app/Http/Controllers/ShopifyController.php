<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Oseintow\Shopify\Shopify;

class ShopifyController extends Controller
{
    /**
     * Shopify Instance
     *
     * @var Shopify
     */
    private $shopify;

    /**
     * Constructor
     *
     * @param Shopify $oShopify
     */
    public function __construct(Shopify $oShopify)
    {
        $this->shopify = $oShopify;
    }
}
