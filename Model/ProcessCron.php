<?php

namespace Dealsales\Deals\Model;

use Magento\Framework\App\ResourceConnection;

class ProcessCron
{
    protected $resource;

    public function __construct(
        ResourceConnection $resource
    ) {
        $this->resource = $resource;
    }

    public function proccessCommission($debug = false)
    {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $query = ('SELECT i.item_id, i.parent_item_id, i.order_id, i.product_id, i.qty_ordered, o.increment_id, c.id as coupon_id
								FROM ds19_sales_flat_order_item as i, ds19_sales_flat_order as o, ds19_productcoupons as c
								WHERE i.product_type != "configurable" AND o.`entity_id` = i.`order_id` AND c.`order_id` = o.`increment_id` AND i.`product_id` = c.`product_id` AND c.`product_type` = 0');

        $result = $readConnection->fetchAll($query);

        foreach ($result as $item) {
            $Order = Mage::getModel('sales/order')->load($item['order_id']);
            $Product = Mage::getModel('catalog/product')->load($item['product_id']);

            if ($item['parent_item_id']) {
                $Parent = Mage::getModel('sales/order_item')->load($item['parent_item_id']);
                $ShippingPrice = (Mage::helper('productshipping')->getProductShippingPrice(
                        $Product->getId(),
                        $item['qty_ordered']
                    ) / $item['qty_ordered']);

                $_item_price_incl_tax = $Parent->getPriceInclTax();
                $_item_price_excl_tax = $Parent->getPrice();
                $_item_commission_incl_tax = Mage::helper('deals')->calcCommission(
                    $Parent->getPriceInclTax(),
                    $Product->getProductCommission()
                );
                $_item_commission_excl_tax = Mage::helper('deals')->calcCommission(
                    $Parent->getPrice(),
                    $Product->getProductCommission()
                );
                $_item_commission_percentage = $Product->getProductCommission();
                $_item_revenue_incl_tax = Mage::helper('deals')->calcRevenue(
                    $Parent->getPriceInclTax(),
                    $Product->getProductCommission()
                );
                $_item_revenue_excl_tax = Mage::helper('deals')->calcRevenue(
                    $Parent->getPrice(),
                    $Product->getProductCommission()
                );
                $_item_shipping_incl_tax = Mage::helper('deals')->calcTaxes(
                    $ShippingPrice,
                    $Parent->getTaxPercent()
                )->include_tax;
                $_item_shipping_excl_tax = Mage::helper('deals')->calcTaxes(
                    $ShippingPrice,
                    $Parent->getTaxPercent()
                )->exclude_tax;
            } else {
                $Parent = Mage::getModel('sales/order_item')->load($item['item_id']);
                $ShippingPrice = (Mage::helper('productshipping')->getProductShippingPrice(
                        $Product->getId(),
                        $item['qty_ordered']
                    ) / $item['qty_ordered']);

                $_item_price_incl_tax = $Parent->getPriceInclTax();
                $_item_price_excl_tax = $Parent->getPrice();
                $_item_commission_incl_tax = Mage::helper('deals')->calcCommission(
                    $Parent->getPriceInclTax(),
                    $Product->getProductCommission()
                );
                $_item_commission_excl_tax = Mage::helper('deals')->calcCommission(
                    $Parent->getPrice(),
                    $Product->getProductCommission()
                );
                $_item_commission_percentage = $Product->getProductCommission();
                $_item_revenue_incl_tax = Mage::helper('deals')->calcRevenue(
                    $Parent->getPriceInclTax(),
                    $Product->getProductCommission()
                );
                $_item_revenue_excl_tax = Mage::helper('deals')->calcRevenue(
                    $Parent->getPrice(),
                    $Product->getProductCommission()
                );
                $_item_shipping_incl_tax = Mage::helper('deals')->calcTaxes(
                    $ShippingPrice,
                    $Parent->getTaxPercent()
                )->include_tax;
                $_item_shipping_excl_tax = Mage::helper('deals')->calcTaxes(
                    $ShippingPrice,
                    $Parent->getTaxPercent()
                )->exclude_tax;
            }

            $Commission = Mage::getModel('deals/commission')
                ->getCollection()
                ->addFieldToFilter('coupon_id', $item['coupon_id'])
                ->addFieldToFilter('order_id', $item['increment_id'])
                ->addFieldToFilter('product_id', $item['product_id'])
                ->addFieldToFilter('item_id', $item['item_id'])
                ->getFirstItem();

            if ($Commission) {
                if ($Commission->getStatus() == 0) {
                    if ($debug) {
                        echo "OK: Ordre: " . $item['increment_id'] . ' - Product ID: ' . $item['product_id'] . "<br>\n";
                    }

                    $CommissionModel = Mage::getModel('deals/commission')->load($Commission->getId());
                    $CommissionModel->setProductType($Product->getAttributeSetId());
                    $CommissionModel->setItemPriceInclTax($_item_price_incl_tax);
                    $CommissionModel->setItemPriceExclTax($_item_price_excl_tax);
                    $CommissionModel->setItemCommissionInclTax($_item_commission_incl_tax);
                    $CommissionModel->setItemCommissionExclTax($_item_commission_excl_tax);
                    $CommissionModel->setItemCommissionPercentage($_item_commission_percentage);
                    $CommissionModel->setItemCommissionPercentage($_item_commission_percentage);
                    $CommissionModel->setItemRevenueInclTax($_item_revenue_incl_tax);
                    $CommissionModel->setItemRevenueExclTax($_item_revenue_excl_tax);
                    $CommissionModel->setItemShippingInclTax($_item_shipping_incl_tax);
                    $CommissionModel->setItemShippingExclTax($_item_shipping_excl_tax);
                    $CommissionModel->setItemCreatedDate($Order->getCreatedAt());
                    $CommissionModel->setUpdatedDate(date('Y-m-d H:i:s'));
                    $CommissionModel->save();

                    $CommissionID = $CommissionModel->getId();

                    $ProductCoupon = Mage::getModel('productcoupons/productcoupons')->load($item['coupon_id']);
                    $ProductCoupon->setProductType($Product->getAttributeSetId());
                    $ProductCoupon->save();
                }
            } else {
                if ($debug) {
                    echo "Out of sync: " . $item['increment_id'] . ' - Product ID: ' . $item['product_id'] . "<br/>\n";
                }
            }
        }
    }

    public function proccessSettlements($store_id = 0, $debug = false)
    {
        $Brands = Mage::getModel('brands/brand')->getCollection();

        if ($debug) {
            echo "Looping manufacturers <br/>\n";
        }

        foreach ($Brands as $Brand) {
            if ($debug) {
                echo ' - ' . $Brand->getBrand() . "<br/>\n";
            }

            // Get all commissions for this manufacturer, joined with coupons
            $Commissions = Mage::getModel('deals/commission')->getCollection();
            $Commissions->getSelect()->joinLeft(
                ['productcoupons' => Mage::getModel('catalog/product')->getResource()->getTable('productcoupons/productcoupons')],
                'main_table.coupon_id = productcoupons.id',
                ['state as couponstate']
            );
            $Commissions->addFieldToFilter('main_table.manufacturer_id', $Brand->getId());
            $Commissions->addFieldToFilter('settlement_id', 0);
            $Commissions->addFieldToFilter('main_table.state', ['gt' => 0]);
            $Commissions->addFieldToFilter('main_table.store_id', ['eq' => $store_id]);
            $Commissions->addFieldToFilter('productcoupons.state', ['gt' => 0]);

            if ($debug) {
                echo " - - Looping commissions: <br/>\n";
            }

            if ($Commissions->count()) {
                $total_price_incl_tax = 0;
                $total_price_excl_tax = 0;
                $total_commission_incl_tax = 0;
                $total_commission_excl_tax = 0;
                $total_revenue_incl_tax = 0;
                $total_revenue_excl_tax = 0;
                $total_shipping_incl_tax = 0;
                $total_shipping_excl_tax = 0;
                $average_commission_percentage = 0;
                $commission_percentage = 0;

                foreach ($Commissions as $Commission) {
                    $_commissiondata = $Commission->getData();
                    if ($debug) {
                        echo ' - - - ' . $Commission->getOrderId() . '<br/>';
                    }

                    $total_price_incl_tax += $_commissiondata['item_price_incl_tax'];
                    $total_price_excl_tax += $_commissiondata['item_price_excl_tax'];
                    $total_commission_incl_tax += $_commissiondata['item_commission_incl_tax'];
                    $total_commission_excl_tax += $_commissiondata['item_commission_excl_tax'];
                    $total_revenue_incl_tax += $_commissiondata['item_revenue_incl_tax'];
                    $total_revenue_excl_tax += $_commissiondata['item_revenue_excl_tax'];
                    $total_shipping_incl_tax += $_commissiondata['item_shipping_incl_tax'];
                    $total_shipping_excl_tax += $_commissiondata['item_shipping_excl_tax'];
                    $commission_percentage += $_commissiondata['item_commission_percentage'];
                }
                $average_commission_percentage = $commission_percentage / $Commissions->count();

                // Get any open settlements for this manufacturer
                $Settlement = Mage::getModel('deals/settlements')
                    ->getCollection()
                    ->addFieldToFilter('manufacturer_id', $Brand->getId())
                    ->addFieldToFilter('state', ['eq' => 0])
                    ->addFieldToFilter('type', ['eq' => 0])
                    ->addFieldToFilter('store_id', ['eq' => $store_id])
                    ->getFirstItem();

                // Or create a new one
                if (!$Settlement->getId()) {
                    $Settlement = Mage::getModel('deals/settlements');
                    $Settlement->setCreatedDate(date('Y-m-d H:i:s'));
                    $Settlement->setStoreId($store_id);
                }

                // Add amounts to settlement
                $Settlement->setTotalPriceInclTax($Settlement->getTotalPriceInclTax() + $total_price_incl_tax);
                $Settlement->setTotalPriceExclTax($Settlement->getTotalPriceExclTax() + $total_price_excl_tax);
                $Settlement->setTotalCommissionInclTax($Settlement->getTotalCommissionInclTax() + $total_commission_incl_tax);
                $Settlement->setTotalCommission_exclTax($Settlement->getTotalCommission_exclTax() + $total_commission_excl_tax);
                $Settlement->setTotalRevenueInclTax($Settlement->getTotalRevenueInclTax() + $total_revenue_incl_tax);
                $Settlement->setTotalRevenueExclTax($Settlement->getTotalRevenueExclTax() + $total_revenue_excl_tax);
                $Settlement->setTotalShippingInclTax($Settlement->getTotalShippingInclTax() + $total_shipping_incl_tax);
                $Settlement->setTotalShippingExclTax($Settlement->getTotalShippingExclTax() + $total_shipping_excl_tax);
                $Settlement->setAverageCommissionPercentage(($Settlement->getAverageCommissionPercentage() + $average_commission_percentage) / 2);
                $Settlement->setManufacturerId($Brand->getId());
                $Settlement->setUpdatedDate(date('Y-m-d H:i:s'));
                $Settlement->setType(0);

                $SettlementSaved = $Settlement->save();

                if ($SettlementSaved->getId()) {
                    if ($debug) {
                        echo ' - - - Settlement saved with ID: ' . $SettlementSaved->getId();
                    }

                    // Remember to close commissions
                    foreach ($Commissions as $Commission) {
                        $Commission->setSettlementId($SettlementSaved->getId());
                        $Commission->save();
                    }
                } else {
                    if ($debug) {
                        echo " - - - Settlement failed saving ----------- !!!!!!!!!!!!!!!!!!!<br>\n";
                    }
                }
            }
        }

        // Process commissions for DS as well
        $this->proccessDSSettlements($store_id, $debug);
    }

    public function proccessDSSettlements($store_id = 0, $debug = false)
    {
        $Brands = Mage::getModel('brands/brand')->getCollection();

        if ($debug) {
            echo "Looping DS commissions <br/>\n";
        }

        // DealSales manufacturer id equals the store id
        $ds_id = $store_id;

        foreach ($Brands as $Brand) {
            //if ($debug) { echo ' - '.$Brand->getBrand() . "<br/>\n"; }

            $Commissions = Mage::getModel('deals/commission')->getCollection();
            $Commissions->getSelect()->joinLeft(
                ['productcoupons' => Mage::getModel('catalog/product')->getResource()->getTable('productcoupons/productcoupons')],
                'main_table.coupon_id = productcoupons.id',
                ['state as couponstate']
            );
            $Commissions->addFieldToFilter('main_table.manufacturer_id', $Brand->getId());
            $Commissions->addFieldToFilter(
                'main_table.manufacturer_id',
                ['gt' => 200]
            ); // Make sure manufactures are above the special limit
            $Commissions->addFieldToFilter('ds_settlement_id', 0);
            $Commissions->addFieldToFilter('main_table.state', ['gt' => 0]);
            $Commissions->addFieldToFilter('main_table.store_id', ['eq' => $store_id]);
            $Commissions->addFieldToFilter('productcoupons.state', ['gt' => 0]);

            // if ($debug) { echo " - - DS Looping commissions: <br/>\n"; }

            if ($Commissions->count()) {
                $total_price_incl_tax = 0;
                $total_price_excl_tax = 0;
                $total_commission_incl_tax = 0;
                $total_commission_excl_tax = 0;
                $total_revenue_incl_tax = 0;
                $total_revenue_excl_tax = 0;
                $total_shipping_incl_tax = 0;
                $total_shipping_excl_tax = 0;
                $average_commission_percentage = 0;
                $commission_percentage = 0;

                foreach ($Commissions as $Commission) {
                    $_commissiondata = $Commission->getData();
                    if ($debug) {
                        echo ' - - - ' . $Commission->getOrderId() . '<br/>';
                    }

                    $total_price_incl_tax += $_commissiondata['item_price_incl_tax'];
                    $total_price_excl_tax += $_commissiondata['item_price_excl_tax'];
                    $total_commission_incl_tax += $_commissiondata['item_commission_incl_tax'];
                    $total_commission_excl_tax += $_commissiondata['item_commission_excl_tax'];
                    $total_revenue_incl_tax += $_commissiondata['item_revenue_incl_tax'];
                    $total_revenue_excl_tax += $_commissiondata['item_revenue_excl_tax'];
                    $total_shipping_incl_tax += $_commissiondata['item_shipping_incl_tax'];
                    $total_shipping_excl_tax += $_commissiondata['item_shipping_excl_tax'];
                    $commission_percentage += $_commissiondata['item_commission_percentage'];
                }
                $average_commission_percentage = $commission_percentage / $Commissions->count();

                // Get any open settlements for dealsales
                $Settlement = Mage::getModel('deals/settlements')
                    ->getCollection()
                    ->addFieldToFilter('manufacturer_id', $ds_id)
                    ->addFieldToFilter('state', ['eq' => 0])
                    ->addFieldToFilter('type', ['eq' => 1])
                    ->addFieldToFilter('store_id', ['eq' => $store_id])
                    ->getFirstItem();

                // Or create a new one
                if (!$Settlement->getId()) {
                    $Settlement = Mage::getModel('deals/settlements');
                    $Settlement->setCreatedDate(date('Y-m-d H:i:s'));
                    $Settlement->setStoreId($store_id);
                }

                $Settlement->setTotalPriceInclTax($Settlement->getTotalPriceInclTax() + $total_price_incl_tax);
                $Settlement->setTotalPriceExclTax($Settlement->getTotalPriceExclTax() + $total_price_excl_tax);
                $Settlement->setTotalCommissionInclTax($Settlement->getTotalCommissionInclTax() + $total_commission_incl_tax);
                $Settlement->setTotalCommission_exclTax($Settlement->getTotalCommission_exclTax() + $total_commission_excl_tax);
                $Settlement->setTotalRevenueInclTax($Settlement->getTotalRevenueInclTax() + $total_revenue_incl_tax);
                $Settlement->setTotalRevenueExclTax($Settlement->getTotalRevenueExclTax() + $total_revenue_excl_tax);
                $Settlement->setTotalShippingInclTax($Settlement->getTotalShippingInclTax() + $total_shipping_incl_tax);
                $Settlement->setTotalShippingExclTax($Settlement->getTotalShippingExclTax() + $total_shipping_excl_tax);
                $Settlement->setAverageCommissionPercentage(($Settlement->getAverageCommissionPercentage() + $average_commission_percentage) / 2);
                $Settlement->setManufacturerId($ds_id);
                $Settlement->setUpdatedDate(date('Y-m-d H:i:s'));
                $Settlement->setType(1);

                $SettlementSaved = $Settlement->save();

                if ($SettlementSaved->getId()) {
                    if ($debug) {
                        echo ' - - - DS Settlement saved with ID: ' . $SettlementSaved->getId();
                    }

                    // Remember to close commissions
                    foreach ($Commissions as $Commission) {
                        $Commission->setDsSettlementId($SettlementSaved->getId());
                        $Commission->save();
                    }
                } else {
                    if ($debug) {
                        echo " - - - DS Settlement failed saving ----------- !!!!!!!!!!!!!!!!!!!<br>\n";
                    }
                }
            }
        }
    }

    public function proccessProductvalue($debug = false)
    {
        $ProductCollection = Mage::getModel('catalog/product')
            ->getCollection()
            ->addAttributeToSelect('deal_custom_main')
            ->addAttributeToSelect('deal_custom_childs')
            ->addAttributeToSelect('deal_start')
            ->addAttributeToSelect('price')
            ->addAttributeToSelect('product_extrasold')
            ->addAttributeToFilter('visibility', 4);

        $ChildCounter = 0;

        $now = new DateTime();

        foreach ($ProductCollection as $Product) {
            $_Product = Mage::getModel('catalog/product')->load($Product->getId());

            if ($_Product->getDealCustomMain()) {
                if ($_Product->getSku() != '1923-5047') {
                    //continue;
                }
                if ($debug) {
                    echo 'Main deal: ' . $_Product->getSku() . "<br>\n";
                }

                $Childs = json_decode($_Product->getDealCustomChilds());
                $valuedata = [];

                foreach ($Childs as $Child) {
                    $ProductChild = Mage::getModel('catalog/product')->load($Child);
                    if ($debug) {
                        echo ' - - Childdeal: ' . $ProductChild->getSku() . " - ";
                    }

                    $valuedata[$Child]['commission'] = $ProductChild->getProductCommission();
                    $valuedata[$Child]['price'] = $ProductChild->getFinalprice();

                    $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
                    $results = $readConnection->fetchAll('SELECT SUM(`qty_invoiced`) as qty FROM ' . Mage::getSingleton('core/resource')->getTableName('sales/order_item') . ' WHERE sku = "' . $ProductChild->getSku() . '" AND created_at >= ( CURDATE() - INTERVAL 30 DAY )');

                    $valuedata[$Child]['sold'] = (int)($results[0]['qty'] ? $results[0]['qty'] : 0);
                    $valuedata[$Child]['extrasold'] = (int)$ProductChild->getProductExtrasold();
                    $valuedata[$Child]['_sold'] = $valuedata[$Child]['sold'] + $valuedata[$Child]['extrasold'];
                    //$valuedata[$Child]['productvalue']	= (int) ((int) ($valuedata[$Child]['commission'] * $valuedata[$Child]['price']) / 100) * $valuedata[$Child]['_sold'];

                    $stock = $ProductChild->getStockItem();
                    if ($stock->getIsInStock()) {
                        $valuedata[$Child]['stock'] = $valuedata[$Child]['stock'] + $stock->getQty();
                    }

                    $startdate = new DateTime($ProductChild->getDealStart());
                    $valuedata[$Child]['days'] = (int)$now->diff($startdate)->format("%a");
                    $valuedata[$Child]['productvalue'] = (int)((int)($valuedata[$Child]['_sold'] * $valuedata[$Child]['price']) / ($valuedata[$Child]['days'] > 0 ? $valuedata[$Child]['days'] : 1));

                    if ($debug) {
                        echo $valuedata[$Child]['productvalue'] . ",- (" . $valuedata[$Child]['_sold'] . " sales * " . $valuedata[$Child]['price'] . ",- / " . $valuedata[$Child]['days'] . " days) (qty: " . $stock->getQty() . ")<br>\n";
                    }

                    $ProductChild->setDsavgvalue($valuedata[$Child]['productvalue']);
                    $ChildCounter++;
                }

                $MainProduct = [];
                $ChildCount = count($valuedata);

                $startdate = new DateTime($_Product->getDealStart());
                $MainProduct['days'] = (int)$now->diff($startdate)->format("%a");
                $MainProduct['sold'] = $_Product->getProductExtrasold();
                $MainProduct['price'] = (int)$_Product->getFinalprice();
                $MainProduct['productvalue'] = (int)((int)($MainProduct['sold'] * $MainProduct['price']) / ($MainProduct['days'] > 0 ? $MainProduct['days'] : 1));
                $MainProduct['stock'] = 0;

                foreach ($valuedata as $childdata) {
                    $MainProduct['sold'] = $MainProduct['sold'] + $childdata['sold'];
                    $MainProduct['productvalue'] = $MainProduct['productvalue'] + $childdata['productvalue'];
                    $MainProduct['stock'] = $MainProduct['stock'] + (int)$childdata['stock'];
                }
                $MainProduct['productvalue'] = $MainProduct['productvalue'] / ($ChildCount + 1);

                if ($debug) {
                    echo ' - Total: ' . $MainProduct['productvalue'] . ",- (qty: " . $MainProduct['stock'] . ")<br>\n";
                }

                $_Product->setStockData(
                    [
                        'is_in_stock' => ($MainProduct['stock'] > 0 ? 1 : 0),
                        'qty' => (int)$MainProduct['stock'],
                    ]
                );
                $_Product->setDsavgvalue((int)$MainProduct['productvalue']);
                $_Product->save();
            } else {
                if ($debug) {
                    echo 'Simple deal: ' . $_Product->getSku() . "    ";
                }

                $valuedata = [];
                $valuedata['commission'] = $_Product->getProductCommission();
                $valuedata['price'] = $_Product->getFinalprice();

                $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
                $results = $readConnection->fetchAll('SELECT SUM(`qty_invoiced`) as qty FROM ' . Mage::getSingleton('core/resource')->getTableName('sales/order_item') . ' WHERE sku = "' . $_Product->getSku() . '" AND created_at >= ( CURDATE() - INTERVAL 30 DAY )');

                $startdate = new DateTime($_Product->getDealStart());
                $valuedata['days'] = (int)$now->diff($startdate)->format("%a");

                $valuedata['sold'] = (int)($results[0]['qty'] ? $results[0]['qty'] : 0);
                $valuedata['extrasold'] = (int)$_Product->getProductExtrasold();
                $valuedata['_sold'] = $valuedata['sold'] + $valuedata['extrasold'];
                //$valuedata['productvalue']	= (int) ((int) ($valuedata['commission'] * $valuedata['price']) / 100) * $valuedata['_sold'];
                $valuedata['productvalue'] = (int)((int)($valuedata['_sold'] * $valuedata['price']) / ($valuedata['days'] > 0 ? $valuedata['days'] : 1));

                if ($debug) {
                    echo $valuedata['productvalue'] . ",- (" . $valuedata['_sold'] . " sales * " . $valuedata['price'] . " / " . $valuedata['days'] . " days)<br>\n";
                }

                $_Product->setDsavgvalue((int)$valuedata['productvalue']);
                $_Product->save();
            }
        }

        if ($debug) {
            echo "<br>\n<br>\n";
        }

        $ProductCollection = Mage::getModel('catalog/product')
            ->getCollection()
            ->addAttributeToSelect('name', 'deal_start')
            ->addAttributeToFilter('deal_start', ['lteq' => $now->format('Y-m-d 23:59:59')])
            //->addAttributeToFilter('deal_start',  array('gteq' => $now->modify('-3 day')->format('Y-m-d 00:00:00')))
            ->addAttributeToFilter('deal_start', ['gteq' => $now->modify('-1 day')->format('Y-m-d 23:59:59')])
            ->addAttributeToFilter('visibility', 4)
            ->setOrder('deal_start', 'DESC');

        $i = 0;
        foreach ($ProductCollection as $Product) {
            $_Product = Mage::getModel('catalog/product')->load($Product->getId());

            if ($debug) {
                echo 'Todays deal: ' . $_Product->getSku() . "  /  " . $_Product->getDealStart() . "  ";
            }

            $value = 9990 + $i;

            if ($debug) {
                echo $value . ",- <br>\n";
            }

            $_Product->setDsavgvalue($value);
            $_Product->save();
            $i++;
        }

        $ChildCounter = 0;

        if ($debug) {
            echo "<br/>\n";
        }
        if ($debug) {
            echo 'Total visible products: ' . $ProductCollection->count();
        }
        if ($debug) {
            echo "<br/>\n";
        }
        if ($debug) {
            echo 'Total childs products: ' . $ChildCounter;
        }
    }
}
