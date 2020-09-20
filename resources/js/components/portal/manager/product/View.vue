<template>
    <div class="content-wrapper">
        <div class="content-body">
            <section id="configuration">
                <div>
                    <transition name="fade" mode="out-in">
                            <div  v-if="loading.main"  class="preloader-wrap">
                                <div class=" custom-center">
                                    <img  style="" src="https://www.solushop.com.gh/app/assets/img/loader.gif" alt="" > 
                                </div>
                            </div>
                    </transition>
                    <div v-if="product" class="row">
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-5" style="margin-top: 10px;">
                                    <h5 class="card-title">State :  <b><span v-html="product.state.ps_html"></span></b></h5>
                                </div>
                                <div class="col-md-7" style="text-align: right; margin-bottom: 5px;">
                                    <button @click="productAction('approve')" v-if="product.state.id == 2 || product.state.id == 3 || product.state.id == 5" class="btn btn-success btn-sm round">
                                        <i class="ft-check"></i>
                                    </button>
                                    <button @click="productAction('disapprove')" v-if="product.state.id == 1" class="btn btn-warning btn-sm round">
                                        <i class="ft-alert-triangle"></i>
                                    </button>
                                    <button @click="productAction('reject')" v-if="product.state.id == 2" class="btn btn-warning btn-sm round">
                                        <i class="ft-x"></i>
                                    </button>
                                    <button @click="productAction('delete')" class="btn btn-danger btn-sm round">
                                        <i class="ft-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card" style="">
                                <transition name="fade" mode="out-in">
                                    <div  v-if="loading.details"  class="div-preloader-wrap">
                                        <div class=" custom-center">
                                            <img  style="" src="https://www.solushop.com.gh/app/assets/img/loader.gif" alt="" > 
                                        </div>
                                    </div>
                                </transition>
                                <div class="card-content collapse show">
                                    <div class="card-body">
                                        <div class="form-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="vendor">Vendor</label>
                                                        <select class="form-control" v-model='product.product_vid' id="vendor" style='border-radius:7px;'>
                                                            <option v-for="vendor in options.vendor" :key="vendor.id" :value="vendor.id">
                                                                {{ vendor.name }}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="name">Name</label>
                                                        <input id="name" v-model="product.product_name" class="form-control round" placeholder="Enter product name" type="text">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="description">Description (Optional)</label>
                                                        <textarea id="description" v-model="product.product_description" class="form-control round" placeholder="Enter Product Description"></textarea>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="features">Highlighted Features</label>
                                                        <textarea id="features" v-model="product.product_features" class="form-control round" placeholder="Enter Product Highlighted Features"></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="category">Category</label>
                                                        <select class="form-control" v-model="product.product_cid" style='border-radius:7px;' >
                                                            <option v-for="category in options.category" :key="category.id" :value="category.id">
                                                                {{ category.pc_description }}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="tags">Tags (Optional)</label>
                                                        <input id="tags" v-model="product.product_tags" class="form-control round" placeholder="Enter product tags" type="text">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="settlement_price">Settlement Price</label>
                                                        <input id="settlement_price" v-model="product.product_settlement_price" class="form-control round" placeholder="Enter product settlement price" type="number" step="0.01">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="selling_price">Selling Price </label>
                                                        <input id="selling_price" v-model="product.product_selling_price" class="form-control round" placeholder="Enter product selling price" type="number" step="0.01">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="discount">Discount</label>
                                                        <input id="discount" v-model="product.product_discount" class="form-control round" placeholder="Enter product discount" type="number" step="0.01">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="dd">Avg. Delivery Duration in Days</label>
                                                        <input id="dd" v-model="product.product_dd" class="form-control round" placeholder="Enter product delivery duration" type="number" step="1">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="dc">Delivery Charge in GHS </label>
                                                        <input id="dc" v-model="product.product_dc" class="form-control round" placeholder="Enter product delivery charge" type="number" step="1">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="type">Availability</label>
                                                        <select class="form-control" name='type' v-model="product.product_type" style='border-radius:7px;'>
                                                            <option value='0'>Available In Stock</option>
                                                            <option value='1'>Available On Pre-Order</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>URL</label>
                                                <div class="row">
                                                    <div class="col-md-10">
                                                        <input class="form-control round" :value="'https://www.solushop.com.gh/shop/' + product.vendor.username + '/' + product.product_slug" type="text" readonly>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <a :href="'https://www.solushop.com.gh/shop/' + product.vendor.username + '/' + product.product_slug" target="_blank">
                                                            <button type="button" data-toggle="tooltip" data-popup="tooltip-custom" data-original-title="View"  style="height:100%; width:100%;" class="btn btn-info btn-sm round">
                                                                <i class="ft-eye"></i>
                                                            </button>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-actions" style="text-align:center; padding: 0px;">
                                                <input type="hidden" name="product_action" value="update_details"/>
                                                <button type="submit" @click="updateProductDetails()" class="btn btn-success">
                                                        Update Product Details
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <h5 class="card-title">Statistics</h5>
                            <div class="card" style="">
                                <div class="card-content collapse show">
                                    <div class="card-body">
                                        <div class="form-body">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="name">Views</label>
                                                        <input class="form-control round" :value="product.product_views" type="text" readonly>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="name">In-Carts</label>
                                                        <input class="form-control round" :value="count.cart" type="text" readonly>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="name">In-Wishlists</label>
                                                        <input class="form-control round" :value="count.wishlist" type="text" readonly>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="name">Purchases</label>
                                                        <input class="form-control round" :value="count.purchases" type="text" readonly>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    
                        </div>
                        <div class="col-md-4">
                            <h5 class="card-title">Badges</h5>
                            <div class="card">
                                <transition name="fade" mode="out-in">
                                    <div  v-if="loading.badge"  class="div-preloader-wrap">
                                        <div class=" custom-center">
                                            <img  style="" src="https://www.solushop.com.gh/app/assets/img/loader.gif" alt="" > 
                                        </div>
                                    </div>
                                </transition>
                                <div class="card-content collapse show">
                                    <div class="card-body card-dashboard">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label for="type">Pay On Delivery</label>
                                                    <select class="form-control" v-model='product.pay_on_delivery' @change="updateBadge('pay_on_delivery')" style='border-radius:7px;' >
                                                        <option value='0'>Yes</option>
                                                        <option value='1'>No</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="type">Verified</label>
                                                    <select class="form-control" v-model='product.verified' @change="updateBadge('verified')" style='border-radius:7px;' >
                                                        <option value='0'>Yes</option>
                                                        <option value='1'>No</option>
                                                    </select>
                                                </div>
                                            </div>
                                    </div>
                                </div>
                            </div>
                            <h5 class="card-title">Images</h5>
                            <div class="card">
                                <transition name="fade" mode="out-in">
                                    <div  v-if="loading.images"  class="div-preloader-wrap">
                                        <div class=" custom-center">
                                            <img  style="" src="https://www.solushop.com.gh/app/assets/img/loader.gif" alt="" > 
                                        </div>
                                    </div>
                                </transition>
                                <div class="card-content collapse show">
                                    <div class="card-body card-dashboard">
                                        <table class="table table-striped" id="product-images">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Preview</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody> 
                                                <tr v-for="image in product.images" :key="image.id">
                                                    <td>{{ image.id }}</td>
                                                    <td>
                                                        <ul class="list-unstyled users-list m-0">
                                                            <li data-toggle="tooltip" data-popup="tooltip-custom" :data-original-title="image.pi_path + '.jpg'" class="avatar avatar-sm pull-up">
                                                                <img class="media-object rounded-circle no-border-top-radius no-border-bottom-radius"
                                                                :src="'https://www.solushop.com.gh/app/assets/img/products/thumbnails/' + image.pi_path + '.jpg'">
                                                            </li>
                                                        </ul>
                                                    </td>
                                                    <td>
                                                        <a target="_blank" :href="'https://www.solushop.com.gh/app/assets/img/products/main/' + image.pi_path + '.jpg'">
                                                            <button data-toggle="tooltip" data-popup="tooltip-custom" data-original-title="View"  style="margin-top: 3px;" class="btn btn-info btn-sm round">
                                                                <i class="ft-eye"></i>
                                                            </button>
                                                        </a>
                                                        <button v-if="product.images.length > 1" @click="deleteImage(image.id)" data-toggle="tooltip" data-popup="tooltip-custom" data-original-title="Delete"  style="margin-top: 3px;" class="btn btn-danger btn-sm round">
                                                            <i class="ft-trash"></i>
                                                        </button>
                                                    </td>   
                                                </tr>
                                            </tbody>
                                        </table>
                                        <div class="row">
                                            <div class="col-md-12" style="text-align: center; padding-top:5px;">
                                                <input type="file" class="form-control-file" @change="handleFileUpload()" ref="images" id="images" multiple >
                                            </div>                         
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6" style="margin-top: 10px;">
                                    <h5 class="card-title">Stock</h5>
                                </div>
                                <div class="col-md-6" style="text-align:right; margin-bottom: 5px;">
                                    <button type="button" @click="addVariation()" class="btn btn-info btn-sm round">
                                        <i class="ft-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card" style="">
                                <transition name="fade" mode="out-in">
                                    <div  v-if="loading.stock"  class="div-preloader-wrap">
                                        <div class=" custom-center">
                                            <img  style="" src="https://www.solushop.com.gh/app/assets/img/loader.gif" alt="" > 
                                        </div>
                                    </div>
                                </transition>
                                <div class="card-content collapse show">
                                    <div class="card-body">
                                        <div class="form-body">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <div class="form-group" style="margin-bottom: 2px;">
                                                        <label for="name">Description</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group" style="margin-bottom: 2px;">
                                                        <label for="name">Quantity</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="variations">
                                                <div class="row" v-for="(sku, index) in product.skus" :key="sku.id">
                                                    <div class="col-md-8">
                                                        <div class="form-group">
                                                            <input class="form-control round" :id="'description' + index" :value="sku.sku_variant_description" type="text" readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <input class="form-control round" :id="'stock' + index" :value="sku.sku_stock_left" type="number" >
                                                        </div>
                                                    </div>
                                                    <input type='hidden' :name="'sku' + index" :id="'sku' + index" :value="sku.id">
                                                </div>
                                            </div>
                                            <!-- <input type='hidden' name='skuCount' Value='{{$i}}'/>
                                            <input type='hidden' id='newSKUCount' name='newSKUCount' Value='{{$i}}'> -->
                                            <div class="form-actions" style="text-align:center; padding: 0px;">
                                                <input type="hidden" name="product_action" value="update_stock"/>
                                                <button @click="updateStock()" class="btn btn-success">
                                                        Update Product Stock
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>

<script>
    export default {
        props: ['id'],
        data(){
            return {
                images: '',
                product: null,
                options: {
                    vendor: '',
                    category: '',
                },
                count: {
                    updated: {
                        old: null,
                        new: null
                    },
                    stock: {
                        old: null,
                        new: null
                    },
                    images: {
                        old: null,
                        new: null
                    },
                    variations: {
                        old: null,
                        new: null
                    },
                    cart: '',
                    favorites: '',
                    purchases: '',
                    views: '',
                },
                loading: {
                    main: true,
                    details: false,
                    images: false,
                    stock: false,
                    badge: false
                },
                base_url: window.location.origin,
            }
        },
        async mounted(){
            //get options
            var response = await axios.post('https://www.solushop.com.gh/portal/manager/product/options');
            this.options.vendor = response.data.vendor;
            this.options.category = response.data.category;

            await this.updateDetails();
            this.count.variations.old = this.count.variations.new = this.product.skus.length;
            document.title = this.product.product_name;
            this.loading.main = false;
            setInterval(this.updateCounts, 10000);
        },
        methods: {
            async updateDetails(){
                var response = await axios({
                                    method: 'post',
                                    url: 'https://www.solushop.com.gh/portal/manager/product/records',
                                    data: {
                                        id : this.id
                                    }
                                })
                                .catch(error => {
                                    switch (error.response.status) {
                                        case 401:
                                            this.$toast.question("User logged out. Kindly log back in.", '', {
                                                timeout: 3000,
                                                close: false,
                                                overlay: true,
                                                displayMode: 'once',
                                                id: 'question',
                                                zindex: 999,
                                                position: 'center',
                                                onClosing: function(instance, toast, closedBy){
                                                    window.location.href = "https://www.solushop.com.gh/portal/manager/login";
                                                }
                                            });
                                            break;

                                        case 419:
                                            this.$toast.question('Session expired. Refreshing automatically in 10 seconds', '', {
                                                timeout: 10000,
                                                close: false,
                                                overlay: true,
                                                displayMode: 'once',
                                                id: 'question',
                                                zindex: 999,
                                                position: 'center',
                                                onClosing: function(instance, toast, closedBy){
                                                    document.location.reload();
                                                }
                                            });
                                            

                                            break;
                                    
                                        default:
                                            break;
                                    }
                                });
                this.product = response.data.records;
                this.setCounts(response.data);
            },

            async updateCounts(){
                var response = await axios({
                                    method: 'post',
                                    url: 'https://www.solushop.com.gh/portal/manager/product/count',
                                    data: {
                                        id : this.id
                                    }
                                })
                                .catch(error => {
                                    switch (error.response.status) {
                                        case 401:
                                            this.$toast.question("User logged out. Kindly log back in.", '', {
                                                timeout: 3000,
                                                close: false,
                                                overlay: true,
                                                displayMode: 'once',
                                                id: 'question',
                                                zindex: 999,
                                                position: 'center',
                                                onClosing: function(instance, toast, closedBy){
                                                    window.location.href = "https://www.solushop.com.gh/portal/manager/login";
                                                }
                                            });
                                            break;

                                        case 419:
                                            this.$toast.question('Session expired. Refreshing automatically in 10 seconds', '', {
                                                timeout: 10000,
                                                close: false,
                                                overlay: true,
                                                displayMode: 'once',
                                                id: 'question',
                                                zindex: 999,
                                                position: 'center',
                                                onClosing: function(instance, toast, closedBy){
                                                    document.location.reload();
                                                }
                                            });
                                            

                                            break;
                                    
                                        default:
                                            break;
                                    }
                                });
                                
                this.setCounts(response.data);

                //check change in product data
                if (this.count.updated.new != this.count.updated.old) {
                    this.loading.details = true;

                    //update old counts
                    this.count.updated.old = this.count.updated.new;
                    var response = await this.getRecords();
                    this.product = response.data.records;
                    this.loading.details = false;
                }

                //check change in product images
                if (this.count.images.new != this.count.images.old) {
                    this.loading.images = true;

                    //update old counts
                    this.count.images.old = this.count.images.new;

                    var response = await this.getRecords();
                    this.product = response.data.records;
                    this.loading.images = false;
                }

                //check change in product stock
                if (this.count.stock.new != this.count.stock.old) {
                    this.loading.stock = true;

                    //update old counts
                    this.count.stock.old = this.count.stock.new;

                    var response = await this.getRecords();
                    this.product.skus = response.data.records.skus;
                    this.loading.stock = false;
                }
            },
            
            setCounts(data){

                if(this.count.updated.old == null){
                    //set old counts
                    this.count.updated.old = data.updated;
                    this.count.images.old = data.images;
                    this.count.stock.old = data.stock;
                }

                //update new counts
                this.count.updated.new = data.updated;
                this.count.images.new = data.images;
                this.count.stock.new = data.stock;

                this.count.cart = data.cart;
                this.count.wishlist = data.wishlist;
                this.count.purchases = data.purchases;

            },

            getRecords(){
                return axios({
                            method: 'post',
                            url: 'https://www.solushop.com.gh/portal/manager/product/records',
                            data: {
                                id : this.id
                            }
                        })
                        .catch(error => {
                            switch (error.response.status) {
                                case 401:
                                    this.$toast.question("User logged out. Kindly log back in.", '', {
                                        timeout: 3000,
                                        close: false,
                                        overlay: true,
                                        displayMode: 'once',
                                        id: 'question',
                                        zindex: 999,
                                        position: 'center',
                                        onClosing: function(instance, toast, closedBy){
                                            window.location.href = "https://www.solushop.com.gh/portal/manager/login";
                                        }
                                    });
                                    break;

                                case 419:
                                    this.$toast.question('Session expired. Refreshing automatically in 10 seconds', '', {
                                        timeout: 10000,
                                        close: false,
                                        overlay: true,
                                        displayMode: 'once',
                                        id: 'question',
                                        zindex: 999,
                                        position: 'center',
                                        onClosing: function(instance, toast, closedBy){
                                            document.location.reload();
                                        }
                                    });
                                    

                                    break;
                            
                                default:
                                    break;
                            }
                        });
            },

            async updateProductDetails(){
                this.loading.details = true;
                let formData = new FormData();
                formData.append('id', this.product.id);
                formData.append('vendor', this.product.product_vid);
                formData.append('name', this.product.product_name);
                formData.append('description', this.product.product_description);
                formData.append('features', this.product.product_features);
                formData.append('category', this.product.product_cid);
                formData.append('tags', this.product.product_tags);
                formData.append('settlement_price', this.product.product_settlement_price);
                formData.append('selling_price', this.product.product_selling_price);
                formData.append('discount', this.product.product_discount);
                formData.append('delivery_duration', this.product.product_dd);
                formData.append('delivery_charge', this.product.product_dc);
                formData.append('settlement_price', this.product.product_settlement_price);
                formData.append('availability', this.product.product_type);

                // for( var i = 0; i < this.product.images.length; i++ ){
                //     let image = this.product.images[i];
                //     formData.append('images[' + i + ']', image);
                // }

                // for (let i = 0; i < this.variations; i++) {
                //     formData.append('stock'+i, document.getElementById('stock'+i).value);
                //     formData.append('description'+i, document.getElementById('description'+i).value);
                // }

                var response = await axios.post('https://www.solushop.com.gh/portal/manager/product/update-records', formData, {
                    headers: {
                    'Content-Type': 'multipart/form-data'
                    }
                })
                .catch(error => {
                    switch (error.response.status) {
                        case 401:
                            this.$toast.question("User logged out. Kindly log back in.", '', {
                                timeout: 3000,
                                close: false,
                                overlay: true,
                                displayMode: 'once',
                                id: 'question',
                                zindex: 999,
                                position: 'center',
                                onClosing: function(instance, toast, closedBy){
                                    window.location.href = "https://www.solushop.com.gh/portal/manager/login";
                                }
                            });
                            break;

                        case 419:
                            this.$toast.question('Session expired. Refreshing automatically in 10 seconds', '', {
                                timeout: 10000,
                                close: false,
                                overlay: true,
                                displayMode: 'once',
                                id: 'question',
                                zindex: 999,
                                position: 'center',
                                onClosing: function(instance, toast, closedBy){
                                    document.location.reload();
                                }
                            });
                            

                            break;
                    
                        default:
                            break;
                    }
                });
                if (response.data.type == "error") {
                    //sort 
                    response.data.message.sort(function(a, b){
                        return a.length - b.length;
                    });
                    //show error message(s)
                    for (let i = 0; i < response.data.message.length ; i++) {
                        this.$toast.error(response.data.message[i], "", {
                            timeout: 5000,
                        });
                    }
                } else {
                    //show success message
                    this.$toast.success(response.data.message, "", {
                        timeout: 5000,
                    });

                    this.product = response.data.records;  
                    this.count.updated.old = response.data.updated;
                }

                this.loading.details = false;
            },

            async handleFileUpload(){
                this.loading.images = true;
                let formData = new FormData();
                formData.append('id', this.product.id);
                for( var i = 0; i < this.$refs.images.files.length; i++ ){
                    let image = this.$refs.images.files[i];
                    formData.append('images[' + i + ']', image);
                }

                var response = await axios.post('https://www.solushop.com.gh/portal/manager/product/add-images', formData, {
                    headers: {
                    'Content-Type': 'multipart/form-data'
                    }
                })
                .catch(error => {
                    switch (error.response.status) {
                        case 401:
                            this.$toast.question("User logged out. Kindly log back in.", '', {
                                timeout: 3000,
                                close: false,
                                overlay: true,
                                displayMode: 'once',
                                id: 'question',
                                zindex: 999,
                                position: 'center',
                                onClosing: function(instance, toast, closedBy){
                                    window.location.href = "https://www.solushop.com.gh/portal/manager/login";
                                }
                            });
                            break;

                        case 419:
                            this.$toast.question('Session expired. Refreshing automatically in 10 seconds', '', {
                                timeout: 10000,
                                close: false,
                                overlay: true,
                                displayMode: 'once',
                                id: 'question',
                                zindex: 999,
                                position: 'center',
                                onClosing: function(instance, toast, closedBy){
                                    document.location.reload();
                                }
                            });
                            

                            break;
                    
                        default:
                            break;
                    }
                });
                if (response.data.type == "error") {
                    //sort 
                    response.data.message.sort(function(a, b){
                        return a.length - b.length;
                    });
                    //show error message(s)
                    for (let i = 0; i < response.data.message.length ; i++) {
                        this.$toast.error(response.data.message[i], "", {
                            timeout: 5000,
                        });
                    }
                } else {
                    this.product.images = response.data.records.images;  
                    this.$refs.images = '';
                    this.count.images.old = response.data.images;

                     //show success message
                    this.$toast.success(response.data.message, "", {
                        timeout: 5000,
                    });
                }

                this.loading.images = false;


            },

            addVariation(){
                var string = "<div class='row'><div class='col-md-8'><div class='form-group'><input class='form-control round' id='description"+this.count.variations.new+"' name='description"+this.count.variations.new+"' value='None' type='text'></div></div><div class='col-md-4'><div class='form-group'><input class='form-control round' id='stock"+this.count.variations.new+"' name='stock"+this.count.variations.new+"' value='1' type='number' ></div></div></div>";
                    
                $('#variations').append(string);
                this.count.variations.new++;
            },

            updateBadge(badge){
                switch (badge) {
                    case 'verified':
                            this.saveBadge('verified', this.product.verified);
                        break;

                    case 'pay_on_delivery':
                            this.saveBadge('pay_on_delivery', this.product.pay_on_delivery);
                        break;
                
                    default:
                        break;
                }
            },

            async saveBadge(badge, value){
                var response = await axios({
                    method: 'post',
                    url: 'https://www.solushop.com.gh/portal/manager/product/update-badges',
                    data: {
                        id: this.product.id,
                        badge: badge,
                        value: value
                    }
                }).catch(error => {
                    switch (error.response.status) {
                        case 401:
                            this.$toast.question("User logged out. Kindly log back in.", '', {
                                timeout: 3000,
                                close: false,
                                overlay: true,
                                displayMode: 'once',
                                id: 'question',
                                zindex: 999,
                                position: 'center',
                                onClosing: function(instance, toast, closedBy){
                                    window.location.href = "https://www.solushop.com.gh/portal/manager/login";
                                }
                            });
                            break;

                        case 419:
                            this.$toast.question('Session expired. Refreshing automatically in 10 seconds', '', {
                                timeout: 10000,
                                close: false,
                                overlay: true,
                                displayMode: 'once',
                                id: 'question',
                                zindex: 999,
                                position: 'center',
                                onClosing: function(instance, toast, closedBy){
                                    document.location.reload();
                                }
                            });
                            

                            break;
                    
                        default:
                            break;
                    }
                });

                this.count.updated.old = response.data.updated;

                if (response.data.type == "error") {
                    //sort 
                    response.data.message.sort();
                    //show error message(s)
                    for (let i = response.data.message.length - 1; i >= 0 ; i--) {
                        this.$toast.error(response.data.message[i], "", {
                            timeout: 5000,
                        });
                    }
                } else {
                    //show success message
                    this.$toast.success(response.data.message, "", {
                        timeout: 5000,
                    });
                }
            },

            async productAction(value){
                var response = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/product/action',
                                data: {
                                    type: this.filter,
                                    id: this.product.id,
                                    action: value
                                }
                            })
                            .catch(error => {
                                switch (error.response.status) {
                                    case 401:
                                        this.$toast.question("User logged out. Kindly log back in.", '', {
                                            timeout: 3000,
                                            close: false,
                                            overlay: true,
                                            displayMode: 'once',
                                            id: 'question',
                                            zindex: 999,
                                            position: 'center',
                                            onClosing: function(instance, toast, closedBy){
                                                window.location.href = "https://www.solushop.com.gh/portal/manager/login";
                                            }
                                        });
                                        break;

                                    case 419:
                                        this.$toast.question('Session expired. Refreshing automatically in 10 seconds', '', {
                                            timeout: 10000,
                                            close: false,
                                            overlay: true,
                                            displayMode: 'once',
                                            id: 'question',
                                            zindex: 999,
                                            position: 'center',
                                            onClosing: function(instance, toast, closedBy){
                                                document.location.reload();
                                            }
                                        });
                                        

                                        break;
                                
                                    default:
                                        break;
                                }
                            });


                this.product.state = response.data.records.state;
                this.count.updated.old = response.data.updated;

                if (response.data.type == "error") {
                    //sort 
                    response.data.message.sort(function(a, b){
                        return a.length - b.length;
                    });

                    //show error message(s)
                    for (let i = 0; i < response.data.message.length ; i++) {
                        this.$toast.error(response.data.message[i], "", {
                            timeout: 5000,
                        });
                    }
                } else {
                    //show success message
                    this.$toast.success(response.data.message, "", {
                        timeout: 5000,
                    });
                }

                
            },

            async deleteImage(image){
                this.loading.images = true;
                
                let formData = new FormData();
                formData.append('id', this.product.id);
                formData.append('image', image);

                var response = await axios.post('https://www.solushop.com.gh/portal/manager/product/delete-image', formData, {
                    headers: {
                    'Content-Type': 'multipart/form-data'
                    }
                })
                .catch(error => {
                    switch (error.response.status) {
                        case 401:
                            this.$toast.question("User logged out. Kindly log back in.", '', {
                                timeout: 3000,
                                close: false,
                                overlay: true,
                                displayMode: 'once',
                                id: 'question',
                                zindex: 999,
                                position: 'center',
                                onClosing: function(instance, toast, closedBy){
                                    window.location.href = "https://www.solushop.com.gh/portal/manager/login";
                                }
                            });
                            break;

                        case 419:
                            this.$toast.question('Session expired. Refreshing automatically in 10 seconds', '', {
                                timeout: 10000,
                                close: false,
                                overlay: true,
                                displayMode: 'once',
                                id: 'question',
                                zindex: 999,
                                position: 'center',
                                onClosing: function(instance, toast, closedBy){
                                    document.location.reload();
                                }
                            });
                            

                            break;
                    
                        default:
                            break;
                    }
                });
                
                if (response.data.type == "error") {
                    //sort 
                    response.data.message.sort(function(a, b){
                        return a.length - b.length;
                    });

                    this.loading.images = false;

                    //show error message(s)
                    for (let i = 0; i < response.data.message.length ; i++) {
                        this.$toast.error(response.data.message[i], "", {
                            timeout: 5000,
                        });
                    }
                } else {
                    this.product.images = response.data.records.images;  
                    this.$refs.images = '';
                    this.count.images.old = response.data.images;

                    this.loading.images = false;

                    //show success message
                    this.$toast.success(response.data.message, "", {
                        timeout: 5000,
                    });
                }

                
            },

            async updateStock(){
                this.loading.stock = true;
                let formData = new FormData();

                for (let i = 0; i < this.count.variations.old; i++) {
                    formData.append('stock'+i, document.getElementById('stock'+i).value);
                    formData.append('description'+i, document.getElementById('description'+i).value);                    
                    formData.append('sku'+i, document.getElementById('sku'+i).value);

                }

                for (let i = this.count.variations.old; i < this.count.variations.new; i++) {
                    formData.append('stock'+i, document.getElementById('stock'+i).value);
                    formData.append('description'+i, document.getElementById('description'+i).value);
                }

                formData.append('new', this.count.variations.new);
                formData.append('old', this.count.variations.old);
                formData.append('id', this.product.id);

                var response = await axios.post('https://www.solushop.com.gh/portal/manager/product/update-stock', formData, {
                    headers: {
                    'Content-Type': 'multipart/form-data'
                    }
                })
                .catch(error => {
                    switch (error.response.status) {
                        case 401:
                            this.$toast.question("User logged out. Kindly log back in.", '', {
                                timeout: 3000,
                                close: false,
                                overlay: true,
                                displayMode: 'once',
                                id: 'question',
                                zindex: 999,
                                position: 'center',
                                onClosing: function(instance, toast, closedBy){
                                    window.location.href = "https://www.solushop.com.gh/portal/manager/login";
                                }
                            });
                            break;

                        case 419:
                            this.$toast.question('Session expired. Refreshing automatically in 10 seconds', '', {
                                timeout: 10000,
                                close: false,
                                overlay: true,
                                displayMode: 'once',
                                id: 'question',
                                zindex: 999,
                                position: 'center',
                                onClosing: function(instance, toast, closedBy){
                                    document.location.reload();
                                }
                            });
                            

                            break;
                    
                        default:
                            break;
                    }
                });
                if (response.data.type == "error") {
                    //sort 
                    response.data.message.sort(function(a, b){
                        return a.length - b.length;
                    });
                    //show error message(s)
                    for (let i = 0; i < response.data.message.length ; i++) {
                        this.$toast.error(response.data.message[i], "", {
                            timeout: 5000,
                        });
                    }
                } else {
                    //show success message
                    this.$toast.success(response.data.message, "", {
                        timeout: 5000,
                    });

                    this.product.skus = response.data.records.skus;  
                    this.count.stock.old = response.data.stock;
                }

                this.loading.stock = false;



            }



        }
    }
</script>

