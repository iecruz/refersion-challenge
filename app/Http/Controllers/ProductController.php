<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Oseintow\Shopify\Shopify;

/**
 * Shopify Product Webhook Controller
 */
class ProductController extends Controller
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

    /**
     * HELPER: Get Variant SKUs
     *
     * @param array $variants
     * @return array
     */
    private function getVariantSku(array $aVariants) : array
    {
        return array_map(function ($aItem) {
            return $aItem['sku'];
        }, $aVariants);
    }

    /**
     * Filter out Refersion SKUs from variants
     *
     * @param array $aVariantSku
     * @return array
     */
    private function filterSku(array $aVariantSku) : array
    {
        $sRefersionSkuCode = 'rfsnadid';

        return array_filter($aVariantSku, function ($sSku) use ($sRefersionSkuCode) {
            return strpos($sSku, $sRefersionSkuCode);
        });
    }

    /**
     * Verify Webhook
     *
     * @param $oData
     * @param $sHmacHeader
     * @return boolean
     */
    private function verifyWebhook($oData, $sHmacHeader) : bool
    {
        $sHmacHeader = $oRequest->server('HTTP_X_SHOPIFY_HMAC_SHA256');
    
        return $this->shopify->verifyWebHook($oData, $sHmacHeader);
    }

    /**
     * Get affiiate ID from SKU
     *
     * @param string $sSku
     * @return string
     */
    private function getAffiliateId(string $sSku) : string
    {
        $sId    = explode('rfsnadid:', $sSku);
        return end($sId);
    }

    /**
     * Conversion Trigger
     *
     * @param string $sAffiliateId
     * @return void
     */
    private function createConversionTrigger(string $sAffiliateId) : array
    {
        $oCh    = curl_init();
        $sUrl   = 'https://www.refersion.com/api/new_affiliate_trigger';

        $aData  = array(
            'refersion_public_key'  => \Config::get('constants.refersion_public_key'),
            'refersion_secret_key'  => \Config::get('constants.refersion_secret_key'),
            'affiliate_code'        => '6905',
            'type'                  => 'SKU',
            'trigger'               => $sAffiliateId
        );

        $oPayload   = json_encode($aData);

        $aOption    = array(
            CURLOPT_URL             => $sUrl,
            CURLOPT_RETURNTRANSFER  => TRUE,
            CURLOPT_POST            => TRUE,
            CURLOPT_POSTFIELDS      => $oPayload,
            CURLOPT_HTTPHEADER      => array(
                'Content-Type: application/json'
            )
        );
        curl_setopt_array($oCh, $aOption);
        $oResult = curl_exec($oCh);

        return json_decode($oResult, TRUE);
    }

    /**
     * Callback URL for product/create Shopify Webhook
     *
     * @param Request $request
     * @return void
     */
    public function automateConversionTrigger(Request $oRequest)
    {
        $oData          = $oRequest->getContent();
        $aItem          = json_decode($oData, TRUE);
        $aVariantSku    = $this->getVariantSku($aItem['variants']);
        $aRefersionSku  = $this->filterSku($aVariantSku);
        $aResult        = array();

        foreach ($aRefersionSku as $sSku) {
            $sId        = $this->getAffiliateId($sSku);
            $aResult[]  = $this->createConversionTrigger($sId);
        }

        Log::info($aResult);
    }
}
