<?php

namespace App\Http\Controllers\Manager;

use App\Product;
use App\StockKeepingUnit;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ScriptsController extends Controller
{
    public function updateSKUs(){
        $product = Product::get();

        for ($i=0; $i < sizeof($product); $i++) { 
            StockKeepingUnit::where('sku_product_id', $product[$i]->id)->update([
                'sku_selling_price' => $product[$i]->product_selling_price,
                'sku_settlement_price' => $product[$i]->product_settlement_price,
                'sku_discount' => $product[$i]->product_discount
            ]);
        }
    }
}
