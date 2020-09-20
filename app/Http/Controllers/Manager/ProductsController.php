<?php

namespace App\Http\Controllers\Manager;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Activitylog\Contracts\Activity;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;


use App\CartItem;
use App\Count;
use App\Manager;
use App\OrderItem;
use App\Product;
use App\ProductImage;
use App\ProductCategory;
use App\StockKeepingUnit;
use App\Vendor;
use App\VendorSubscription;
use App\WishlistItem;

use Image;
use Auth;

class ProductsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:manager');
    }

    public function getProductsCount(Request $request){
        return response()->json([
            'pending' => count(Product::whereIn('product_state', $request->pending)->get()),
            'live' => count(Product::whereIn('product_state', $request->live)->get()),
            'rejected' => count(Product::whereIn('product_state', $request->rejected)->get()),
            'inactive' => count(Product::whereIn('product_state', $request->inactive)->get()),
            'total' => count(Product::get()),
        ]);
    }

    public function getProductsRecords(Request $request){
        switch ($request->type) {
            case 'Pending':
                $states = [2];
                break;

            case 'Live':
                $states = [1];
                break;

            case 'Rejected':
                $states = [3];
                break;

            case 'Inactive':
                $states = [5];
                break;
            
            default:
                $states = [1, 2, 3, 5];
                break;
        }
        return response()->json([
            'pending' => count(Product::whereIn('product_state', [2])->get()),
            'live' => count(Product::whereIn('product_state', [1])->get()),
            'rejected' => count(Product::whereIn('product_state', [3])->get()),
            'inactive' => count(Product::whereIn('product_state', [5])->get()),
            'total' => count(Product::get()),
            'records' => Product::whereIn('product_state', $states)->with('skus', 'vendor', 'images', 'state')->get()
        ]);
    }

    public function doProductsAction(Request $request){
        $product = Product::where('id', $request->id)->first();
        switch ($request->action) {
            case 'approve':
                /*--- Check for subscription and allowance for new product ---*/
                // if (is_null(VendorSubscription::where('vs_vendor_id', $product->product_vid)->with('package')->first()) OR VendorSubscription::where('vs_vendor_id', $product->product_vid)->with('package')->first()->vs_days_left < 1) {
                //     $errors[0] = "Vendor has no active subscriptions";
                //     return response()->json([
                //         'type' => 'error',
                //         'message' => $errors
                //     ]);
                // }elseif(Product::where('product_vid', $product->product_vid)->whereIn('product_state', [1])->get()->count() >= VendorSubscription::where('vs_vendor_id', $product->product_vid)->with('package')->first()->package->vs_package_product_cap){
                //     $errors[0] = "Upload limit for vendor reached on current vendor subscription";
                //     return response()->json([
                //         'type' => 'error',
                //         'message' => $errors
                //     ]);
                // }

                /*--- change product state ---*/
                Product::
                    where([
                        ['id', "=", $request->id]
                    ])->update([
                        'product_state' => 1
                    ]);
                /*--- log activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Product Approved';
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." approved product ".$request->id);

                $message = "Product ".$request->id." approved successfully.";
                break;
            case 'disapprove':

                /*--- change product state ---*/
                Product::
                    where([
                        ['id', "=", $request->id]
                    ])->update([
                        'product_state' => 2
                    ]);
                /*--- log activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Product Disapproved';
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." disapproved product ".$request->id);

                $message = "Product ".$request->id." disapproved successfully.";
                break;
            case 'delete':
                /*--- change product state ---*/
                Product::
                    where([
                        ['id', "=", $request->id]
                    ])->update([
                        'product_state' => 4
                    ]);
                /*--- log activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Product Deleted';
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." deleted product ".$request->id);

                $message = "Product ".$request->id." deleted successfully.";
                break;

            case 'reject':
                /*--- change product state ---*/
                Product::
                    where([
                        ['id', "=", $request->id]
                    ])->update([
                        'product_state' => 3
                    ]);
                /*--- log activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Product Rejected';
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." rejected product ".$request->id);

                $message = "Product ".$request->id." rejected successfully.";
                break;
            
            default:
                # code...
                break;
        }
        switch ($request->type) {
            case 'Pending':
                $states = [2];
                break;

            case 'Live':
                $states = [1];
                break;

            case 'Rejected':
                $states = [3];
                break;

            case 'Inactive':
                $states = [5];
                break;
            
            default:
                $states = [1, 2, 3, 5];
                break;
        }
        return response()->json([
            'type' => 'success',
            'message' => $message,
            'updated' => $product->updated_at,
            'pending' => count(Product::whereIn('product_state', [2])->get()),
            'live' => count(Product::whereIn('product_state', [1])->get()),
            'rejected' => count(Product::whereIn('product_state', [3])->get()),
            'inactive' => count(Product::whereIn('product_state', [5])->get()),
            'total' => count(Product::whereIn('product_state', $states)->get()),
            'records' => Product::whereIn('product_state', $states)->with('skus', 'vendor', 'images', 'state')->get()
        ]);
    }

    public function doProductAction(Request $request){
        $product = Product::where('id', $request->id)->first();
        switch ($request->action) {
            case 'approve':
                /*--- Check for subscription and allowance for new product ---*/
                // if (is_null(VendorSubscription::where('vs_vendor_id', $product->product_vid)->with('package')->first()) OR VendorSubscription::where('vs_vendor_id', $product->product_vid)->with('package')->first()->vs_days_left < 1) {
                //     $errors[0] = "Vendor has no active subscriptions";
                //     return response()->json([
                //         'type' => 'error',
                //         'message' => $errors
                //     ]);
                // }elseif(Product::where('product_vid', $product->product_vid)->whereIn('product_state', [1])->get()->count() >= VendorSubscription::where('vs_vendor_id', $product->product_vid)->with('package')->first()->package->vs_package_product_cap){
                //     $errors[0] = "Upload limit for vendor reached on current vendor subscription";
                //     return response()->json([
                //         'type' => 'error',
                //         'message' => $errors
                //     ]);
                // }

                /*--- change product state ---*/
                Product::
                    where([
                        ['id', "=", $request->id]
                    ])->update([
                        'product_state' => 1
                    ]);
                /*--- log activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Product Approved';
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." approved product ".$request->id);

                $message = "Product ".$request->id." approved successfully.";
                break;
            case 'disapprove':

                /*--- change product state ---*/
                Product::
                    where([
                        ['id', "=", $request->id]
                    ])->update([
                        'product_state' => 2
                    ]);
                /*--- log activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Product Disapproved';
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." disapproved product ".$request->id);

                $message = "Product ".$request->id." disapproved successfully.";
                break;
            case 'delete':
                /*--- change product state ---*/
                Product::
                    where([
                        ['id', "=", $request->id]
                    ])->update([
                        'product_state' => 4
                    ]);
                /*--- log activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Product Deleted';
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." deleted product ".$request->id);

                $message = "Product ".$request->id." deleted successfully.";
                break;

            case 'reject':
                /*--- change product state ---*/
                Product::
                    where([
                        ['id', "=", $request->id]
                    ])->update([
                        'product_state' => 3
                    ]);
                /*--- log activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Product Rejected';
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." rejected product ".$request->id);

                $message = "Product ".$request->id." rejected successfully.";
                break;
            
            default:
                # code...
                break;
        }
    
        return response()->json([
            'type' => 'success',
            'message' => $message,
            'records' => Product::
            where('id', $request->id)
            ->with('state')
            ->first()
            ->toArray(),
            'updated' => Product::where('id', '=', $request->id)->first()->updated_at
        ]);
    }

    public function getProductOptions(Request $request){
       return response()->json([
            'category' => ProductCategory::orderBy('pc_description')->where('pc_level', 3)->get()->toArray(),
            'vendor' => Vendor::orderBy('name')->get()->toArray()
        ]);
    }

    public function addProduct(Request $request){
        $validator = Validator::make($request->all(), [
            'vendor' => 'required',
            'name' => 'required',
            'features' => 'required',
            'category' => 'required',
            'settlement_price' => 'required',
            'selling_price' => 'required',
            'discount' => 'required',
            'delivery_duration' => 'required',
            'delivery_charge' => 'required',
            'availability' => 'required',
            'pay_on_delivery' => 'required',
            'verified' => 'required',
            'images' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->all() as $key => $value) {
                array_set($errors, $key, $value);
            }
            return response()->json([
                "type" => "error",
                "message" => $errors
            ]);
        }

        /*--- Check for subscription and allowance for new product ---*/
        // if (is_null(VendorSubscription::where('vs_vendor_id', $request->vendor)->with('package')->first()) OR VendorSubscription::where('vs_vendor_id', $request->vendor)->with('package')->first()->vs_days_left < 1) {
        //     $errors[0] = "Vendor has no active subscriptions";
        //     return response()->json([
        //         'type' => 'error',
        //         'message' => $errors
        //     ]);
        // }elseif(Product::where('product_vid', $request->vendor)->whereIn('product_state', [1])->get()->count() >= VendorSubscription::where('vs_vendor_id', $request->vendor)->with('package')->first()->package->vs_package_product_cap){
        //     $errors[0] = "Upload limit for vendor reached on current vendor subscription";
        //     return response()->json([
        //         'type' => 'error',
        //         'message' => $errors
        //     ]);
        // }

        /*--- Validate Images ---*/
        for ($i=0; $i < sizeof($request->images); $i++) { 
            if(!($request->images[$i]->getClientOriginalExtension() == "jpg" OR $request->images[$i]->getClientOriginalExtension() == "jpeg")){
                $errors[0] = "Images must be of type jpg";
                return response()->json([
                    'type' => 'error',
                    'message' => $errors
                ]);
            }

            list($width, $height) = getimagesize($request->images[$i]);
            if ($width != $height or $height < 600) {
                $errors[0] = "Images must be minimum height 600px with aspect ratio of 1";
                return response()->json([
                    'type' => 'error',
                    'message' => $errors
                ]);
            }

            if(filesize($request->images[$i]) > 5000000){
                $errors[0] = "One or more images exceed the allowed size for upload.";
                return response()->json([
                'type' => 'error',
                'message' => $errors
            ]);
            }
        }

        /*--- Validate and generate Product Slug ---*/
        if((Product::where([
            ['product_vid', '=', $request->vendor],
            ['product_name', '=', $request->name]
        ])->get()->count()) > 0){
            $product_slug_count = Product::where([
                ['product_vid', '=', $request->vendor],
                ['product_name', '=', $request->name]
            ])->get()->count();
            $product_slug_count++;
            $product_slug = str_slug($request->name)."-".$product_slug_count;
        }else{
            $product_slug = str_slug($request->name);
        }

        /*--- Generate product id and set detail variables ---*/
        $count = Count::first();
        $count->product_count++;

        $product = New Product;
        $product_id = "P-".date("Ymd")."-".$count->product_count;
        $product->id = $product_id;
        $product->product_vid = $request->vendor;
        $product->product_name = ucwords(strtolower($request->name));
        $product->product_slug = $product_slug;
        $product->product_features = $request->features;
        $product->product_cid = $request->category;
        $product->product_settlement_price = $request->settlement_price;
        $product->product_selling_price = $request->selling_price;
        $product->product_discount = $request->discount;
        $product->product_dd = $request->delivery_duration;
        $product->product_dc = $request->delivery_charge;
        $product->verified = $request->verified;
        $product->pay_on_delivery = $request->pay_on_delivery;
        $product->product_description = $request->description;
        $product->product_tags = $request->tags;
        $product->product_type = $request->availability;
        $product->product_state = 2;
        $product->product_views = 0;


        /*--- Save product stock --- */
        $count->sku_count++;

        $sku = new StockKeepingUnit;
        $sku->id                        = "S-".($count->sku_count);
        $sku->sku_product_id            = $product_id;
        $sku->sku_variant_description   = $request->input('description0');
        $sku->sku_selling_price         = $product->product_selling_price;
        $sku->sku_settlement_price      = $product->product_settlement_price;
        $sku->sku_discount              = $product->product_discount;
        $sku->sku_stock_left            = $request->input('stock0');
        $sku->save();

        for ($i=1; $i < $request->newSKUCount; $i++) { 
            if ((ucfirst(trim($request->input('description'.$i))) != "None") AND ($request->input('stock'.$i) >= 0)) {
                //insert sku
                $count->sku_count++;

                $sku = new StockKeepingUnit;
                $sku->id                        = "S-".($count->sku_count);
                $sku->sku_product_id            = $product_id;
                $sku->sku_variant_description   = $request->input('description'.$i);
                $sku->sku_selling_price         = $product->product_selling_price;
                $sku->sku_settlement_price      = $product->product_settlement_price;
                $sku->sku_discount              = $product->product_discount;
                $sku->sku_stock_left            = $request->input('stock'.$i);
                $sku->save();

            }
        }

        /*--- Save product images --- */
        for ($i=0; $i < sizeof($request->images); $i++) { 
                    
            $product_image = new ProductImage;
            $product_image->pi_product_id = $product_id;
            $product_image->pi_path = $product_id.rand(1000, 9999);

            $img = Image::make($request->images[$i]);

            //save original image
            $img->save('app/assets/img/products/original/'.$product_image->pi_path.'.jpg');

            //save main image
            $img->resize(600, 600);
            $img->insert('portal/images/watermark/stamp.png', 'center');
            $img->save('app/assets/img/products/main/'.$product_image->pi_path.'.jpg');

            //save thumbnail
            $img->resize(300, 300);
            $img->save('app/assets/img/products/thumbnails/'.$product_image->pi_path.'.jpg');

            //store image details
            $product_image->save();
        }


        /*--- Save product --- */
        $product->save();
        $count->save();

        /*--- log activity ---*/
        activity()
        ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
        ->tap(function(Activity $activity) {
            $activity->subject_type = 'System';
            $activity->subject_id = '0';
            $activity->log_name = 'Product Added';
        })
        ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." added product ".$product_id);

        return response()->json([
            'type' => 'success',
            'message' => "Product added successfully"
        ]);
    }

    public function getProductCount(Request $request){
        $product =  Product::
        where('id', $request->id)
        ->with('skus')
        ->first()
        ->toArray();

        /*--- Build SKU array ---*/
        $sku_array = [];
        for ($i=0; $i < sizeof($product["skus"]); $i++) { 
            $sku_array[$i] = $product["skus"][$i]["id"];
        }


        /*--- Stats ---*/
        $wishlist = WishlistItem::
            where('wi_product_id', $product["id"])
            ->count();

        $cart = CartItem::
        whereIn('ci_sku', $sku_array)
        ->count();

        $purchases = OrderItem::
        whereIn('oi_sku', $sku_array)
        ->whereIn('oi_state', [2, 3, 4])
        ->count();

        return response()->json([
            'updated' => Product::where('id', '=', $request->id)->first()->updated_at,
            'images' => ProductImage::where('pi_product_id', '=', $request->id)->get()->count(),
            'stock' => StockKeepingUnit::where('sku_product_id', '=', $request->id)->sum('sku_stock_left'),
            'wishlist' => $wishlist,
            'cart' => $cart,
            'purchases' => $purchases
        ]);
    }

    public function getProductRecords(Request $request){
        $product =  Product::
        where('id', $request->id)
        ->with('skus')
        ->first()
        ->toArray();

        /*--- Build SKU array ---*/
        $sku_array = [];
        for ($i=0; $i < sizeof($product["skus"]); $i++) { 
            $sku_array[$i] = $product["skus"][$i]["id"];
        }


        /*--- Stats ---*/
        $wishlist = WishlistItem::
            where('wi_product_id', $product["id"])
            ->count();

        $cart = CartItem::
        whereIn('ci_sku', $sku_array)
        ->count();

        $purchases = OrderItem::
        whereIn('oi_sku', $sku_array)
        ->whereIn('oi_state', [2, 3, 4])
        ->count();

        return response()->json([
            'updated' => Product::where('id', '=', $request->id)->first()->updated_at,
            'images' => ProductImage::where('pi_product_id', '=', $request->id)->get()->count(),
            'stock' => StockKeepingUnit::where('sku_product_id', '=', $request->id)->sum('sku_stock_left'),
            'wishlist' => $wishlist,
            'cart' => $cart,
            'purchases' => $purchases,
            'records' => Product::
            where('id', $request->id)
            ->with('images', 'skus', 'vendor', 'state')
            ->first()
            ->toArray()
        ]);
    }

    public function updateProductRecord(Request $request){
        $product = Product::where('id', '=', $request->id)->first();
        $validator = Validator::make($request->all(), [
            'vendor' => 'required',
            'name' => 'required',
            'features' => 'required',
            'category' => 'required',
            'settlement_price' => 'required',
            'selling_price' => 'required',
            'discount' => 'required',
            'delivery_duration' => 'required',
            'delivery_charge' => 'required',
            'availability' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->all() as $key => $value) {
                array_set($errors, $key, $value);
            }
            return response()->json([
                "type" => "error",
                "message" => $errors
            ]);
        }

         /*--- Validate and generate Product Slug ---*/
         if((Product::where([
            ['product_vid', '=', $request->vendor],
            ['product_name', '=', $request->name],
            ['id', '<>', $request->id]
        ])->get()->count()) > 0){
            $product_slug_count = Product::where([
                ['product_vid', '=', $request->vendor],
                ['product_name', '=', $request->name]
            ])->get()->count();
            $product_slug_count++;
            $product_slug = str_slug($request->name)."-".$product_slug_count;
        }else{
            $product_slug = str_slug($request->name);
        }

        $product->product_vid = $request->vendor;
        $product->product_name = ucwords(strtolower($request->name));
        $product->product_slug = $product_slug;
        $product->product_features = $request->features;
        $product->product_cid = $request->category;
        $product->product_settlement_price = $request->settlement_price;
        $product->product_selling_price = $request->selling_price;
        $product->product_discount = $request->discount;
        $product->product_dd = $request->delivery_duration;
        $product->product_dc = $request->delivery_charge;
        $product->product_description = $request->description;
        $product->product_tags = $request->tags;
        $product->product_type = $request->availability;
        $product->save();

        StockKeepingUnit::where('sku_product_id', $product->id)->update([
            'sku_selling_price' => $request->selling_price,
            'sku_settlement_price' => $request->settlement_price,
            'sku_discount' => $request->discount
        ]);


        return response()->json([
            'type' => 'success',
            'message' => 'Product details updated successfully.',
            'updated' => Product::where('id', '=', $request->id)->first()->updated_at,
            'records' => Product::
            where('id', $request->id)
            ->with('images', 'skus', 'vendor', 'state')
            ->first()
            ->toArray()
        ]);
    }

    public function updateProductStock(Request $request){
        /*--- update old stock ---*/
        for ($i=0; $i < $request->old; $i++) { 
            $sku = StockKeepingUnit::where('id', $request->input('sku'.$i))->first();
            $sku->sku_stock_left = $request->input('stock'.$i);
            $sku->save();
        }

        /*--- add new stock (if any) ---*/
        if ($request->new > $request->old) {
             //select product
            $product = Product::where('id', $request->id)->first();
            for ($i=$request->old; $i < $request->new; $i++) { 
                if ((ucfirst(trim($request->input('description'.$i))) != "None") AND ($request->input('stock'.$i) >= 0)) {
                    //insert sku
                    $count = Count::first();
                    $count->sku_count++;

                    $sku = new StockKeepingUnit;
                    $sku->id                        = "S-".($count->sku_count);
                    $sku->sku_product_id            = $product->id;
                    $sku->sku_variant_description   = $request->input('description'.$i);
                    $sku->sku_selling_price         = $product->product_selling_price;
                    $sku->sku_settlement_price      = $product->product_settlement_price;
                    $sku->sku_discount              = $product->product_discount;
                    $sku->sku_stock_left            = $request->input('stock'.$i);
                    $sku->save();

                    //update count
                    $count->save();
                }
            }

        }

        /*--- log activity ---*/
        activity()
        ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
        ->tap(function(Activity $activity) {
            $activity->subject_type = 'System';
            $activity->subject_id = '0';
            $activity->log_name = 'Product Stock Updated';
        })
        ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." updated stock of product ".$request->id);

        return response()->json([
            'type' => 'success',
            'message' => "Stock updated successfully",
            'stock' => StockKeepingUnit::where('sku_product_id', '=', $request->id)->sum('sku_stock_left'),
            'records' => Product::
            where('id', $request->id)
            ->with('skus')
            ->first()
            ->toArray()
        ]);
    }

    public function deleteProductImage(Request $request){
        $image = ProductImage::where('id', '=', $request->image)->where('pi_product_id', '=', $request->id)->first();

        //delete files
        $main_image_path = "app/assets/img/products/main/";
        $thumbnail_image_path = "app/assets/img/products/thumbnails/";
        $original_image_path = "app/assets/img/products/original/";

        File::delete($main_image_path.$image->pi_path.'.jpg');
        File::delete($thumbnail_image_path.$image->pi_path.'.jpg');
        File::delete($original_image_path.$image->pi_path.'.jpg');

        //delete image
        $image->delete();

        /*--- log activity ---*/
        activity()
        ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
        ->tap(function(Activity $activity) {
            $activity->subject_type = 'System';
            $activity->subject_id = '0';
            $activity->log_name = 'Product Image Deleted';
        })
        ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." deleted image of product ".$request->id);


        return response()->json([
            'type' => 'success',
            'message' => "Image ".$request->image." deleted successfully",
            'images' => ProductImage::where('pi_product_id', '=', $request->id)->get()->count(),
            'records' => Product::
            where('id', $request->id)
            ->with('images')
            ->first()
            ->toArray()
        ]);
    }

    public function addProductImages(Request $request){
        $validator = Validator::make($request->all(), [
            'images' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->all() as $key => $value) {
                array_set($errors, $key, $value);
            }
            return response()->json([
                "type" => "error",
                "message" => $errors
            ]);
        }

        /*--- Validate Images ---*/
        for ($i=0; $i < sizeof($request->images); $i++) { 
            if(!($request->images[$i]->getClientOriginalExtension() == "jpg" OR $request->images[$i]->getClientOriginalExtension() == "jpeg")){
                $errors[0] = "Images must be of type jpg";
                return response()->json([
                    'type' => 'error',
                    'message' => $errors
                ]);
            }

            list($width, $height) = getimagesize($request->images[$i]);
            if ($width != $height or $height < 600) {
                $errors[0] = "Images must be minimum height 600px with aspect ratio of 1";
                return response()->json([
                    'type' => 'error',
                    'message' => $errors
                ]);
            }

            if(filesize($request->images[$i]) > 5000000){
                $errors[0] = "One or more images exceed the allowed size for upload.";
                return response()->json([
                'type' => 'error',
                'message' => $errors
            ]);
            }
        }

        /*--- Save product images --- */
        for ($i=0; $i < sizeof($request->images); $i++) { 
                    
            $product_image = new ProductImage;
            $product_image->pi_product_id = $request->id;
            $product_image->pi_path = $request->id.rand(1000, 9999);

            $img = Image::make($request->images[$i]);

            //save original image
            $img->save('app/assets/img/products/original/'.$product_image->pi_path.'.jpg');

            //save main image
            $img->resize(600, 600);
            $img->insert('portal/images/watermark/stamp.png', 'center');
            $img->save('app/assets/img/products/main/'.$product_image->pi_path.'.jpg');

            //save thumbnail
            $img->resize(300, 300);
            $img->save('app/assets/img/products/thumbnails/'.$product_image->pi_path.'.jpg');

            //store image details
            $product_image->save();
        }

        /*--- log activity ---*/
        activity()
        ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
        ->tap(function(Activity $activity) {
            $activity->subject_type = 'System';
            $activity->subject_id = '0';
            $activity->log_name = 'Product Image(s) Added';
        })
        ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." added image(s) of product ".$request->id);


        return response()->json([
            'type' => 'success',
            'message' => "Image(s) added successfully",
            'images' => ProductImage::where('pi_product_id', '=', $request->id)->get()->count(),
            'records' => Product::
            where('id', $request->id)
            ->with('images')
            ->first()
            ->toArray()
        ]);
    }



    public function updateProductBadges(Request $request){
        $product = Product::where('id', $request->id)->first();
        switch ($request->badge) {
            case 'verified':
                $product->verified = $request->value;
                $product->save();

                /*--- log activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Product Badge Updated';
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." updated verified badge of product ".$request->id);


                return response()->json([
                    'type' => 'success',
                    'message' => "Verified badge updated",
                    'updated' => $product->updated_at
                ]);


                break;

            case 'pay_on_delivery':
                $product->pay_on_delivery = $request->value;
                $product->save();

                /*--- log activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Product Badge Updated';
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." updated pay on delivery badge of product ".$request->id);


                return response()->json([
                    'type' => 'success',
                    'message' => "Pay on delivery badge updated",
                    'updated' => $product->updated_at
                ]);

                break;
            
            default:
                # do absolutely nothing
                break;

            
        }
        
    }

    public function updateStockPrices(){
        $products = Product::with('skus')->get()->toArray();
        for ($i=0; $i < sizeof($products); $i++) { 
            for ($j=0; $j < sizeof($products[$i]['skus']); $j++) { 
                $sku = StockKeepingUnit::find($products[$i]['skus'][$j]['id']);
                $sku->sku_settlement_price = $products[$i]['product_settlement_price'];
                $sku->sku_selling_price = $products[$i]['product_selling_price'];
                $sku->sku_discount = $products[$i]['product_discount'];
                $sku->save();
            }
        }
    }
}
