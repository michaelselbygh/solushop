<template>
    <div>
        <div class="content-wrapper">
            <div class="content-body">
                <section id="configuration">
                    <div class="row">
                        <div class="col-md-10">
                            <div class="row">
                                <div class="col-md-7">
                                    <h5 class="card-title">Details</h5>
                                    <div class="card" style="">
                                        <div class="card-content collapse show">
                                            <div class="card-body">
                                                <div class="form-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="vendor">Vendor</label>
                                                                <select class="form-control" v-model='product.vendor' style='border-radius:7px;' >
                                                                    <option v-for="vendor in options.vendor" :key="vendor.id" :value="vendor.id">
                                                                        {{ vendor.name }}
                                                                    </option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="name">Name</label>
                                                                <input v-model="product.name" class="form-control round" placeholder="Enter product name" value="" type="text" >
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="description">Description (Optional)</label>
                                                                <textarea v-model="product.description" class="form-control round" placeholder="Enter Product Description"></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="features">Highlighted Features</label>
                                                                <textarea v-model="product.features" class="form-control round" placeholder="Enter Product Highlighted Features" ></textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="category">Category</label>
                                                                <select class="form-control" v-model='product.category' style='border-radius:7px;' >
                                                                    <option v-for="category in options.category" :key="category.id" :value="category.id">
                                                                        {{ category.pc_description }}
                                                                    </option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="tags">Tags (Optional)</label>
                                                                <input v-model="product.tags" class="form-control round" placeholder="Enter product tags" value="" type="text">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                <label for="settlement_price">Settlement Price</label>
                                                                <input v-model="product.settlement_price" class="form-control round" placeholder="Enter product settlement price" value="" type="number" step="0.01" >
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                <label for="selling_price">Selling Price </label>
                                                                <input v-model="product.selling_price" class="form-control round" placeholder="Enter product selling price" value="" type="number" step="0.01" >
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                <label for="discount">Discount</label>
                                                                <input v-model="product.discount" class="form-control round" placeholder="Enter product discount" value="" type="number" step="0.01" >
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                <label for="delivery_duration">Delivery Duration</label>
                                                                <input v-model="product.delivery_duration" class="form-control round" placeholder="Enter product delivery duration" value="1" type="number" step="1" min="1" >
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                <label for="delivery_charge">Delivery Charge in GHS </label>
                                                                <input v-model="product.delivery_charge" class="form-control round" placeholder="Enter product delivery charge" value="1" type="number" step="1" min="1" >
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                <label for="type">Availability</label>
                                                                <select class="form-control" v-model='product.availability' style='border-radius:7px;' >
                                                                    <option value='0'>Available In Stock</option>
                                                                    <option value='1'>Available On Pre-Order</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <h5 class="card-title">Badges</h5>
                                    <div class="card">
                                        <div class="card-content collapse show">
                                            <div class="card-body card-dashboard">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <label for="type">Payment On Delivery</label>
                                                            <select class="form-control" v-model='product.pay_on_delivery' style='border-radius:7px;' >
                                                                <option value='0'>Yes</option>
                                                                <option value='1'>No</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label for="type">Verified</label>
                                                            <select class="form-control" v-model='product.verified' style='border-radius:7px;' >
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
                                        <div class="card-content collapse show">
                                            <div class="card-body card-dashboard">
                                                    <div class="row">
                                                        <div class="col-md-12" style="text-align: center; padding-top:5px;">
                                                            <input type="file" class="form-control-file" @change="handleFileUpload()" ref="images" id="images" multiple>
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
                                                        <div class="row">
                                                            <div class="col-md-8">
                                                                <div class="form-group">
                                                                    <input class="form-control round" name='description0' id='description0' value="None" type="text">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <input class="form-control round" name='stock0' id='stock0' value="1" min="1" type="number" >
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <input type='hidden' id='newSKUCount' name='newSKUCount' Value='1'>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-actions" style="text-align:center; padding: 0px;">
                                        <button @click="add()" class="btn btn-success">
                                                Add Product
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data(){
        return {
            product: {
                vendor: '',
                name: '',
                description: '',
                features: '',
                category: '',
                tags: '',
                settlement_price: '',
                selling_price: '',
                discount: 0,
                delivery_duration: '',
                delivery_charge: '',
                settlement_price: '',
                availability: 0,
                pay_on_delivery: 1,
                verified: 1,
                images: ''
            },
            options: {
                vendor: '',
                category: '',
            },
            loading: true,
            variations: 1,
            base_url: window.location.origin,
        }
    },
    async mounted(){
        var response = await axios.post('https://www.solushop.com.gh/portal/manager/product/options');
        this.options.vendor = response.data.vendor;
        this.options.category = response.data.category;
        this.loading = false
    },
    methods: {
        async add(){
            this.loading = true;
            let formData = new FormData();
            formData.append('vendor', this.product.vendor);
            formData.append('name', this.product.name);
            formData.append('description', this.product.description);
            formData.append('features', this.product.features);
            formData.append('category', this.product.category);
            formData.append('tags', this.product.tags);
            formData.append('settlement_price', this.product.settlement_price);
            formData.append('selling_price', this.product.selling_price);
            formData.append('discount', this.product.discount);
            formData.append('delivery_duration', this.product.delivery_duration);
            formData.append('delivery_charge', this.product.delivery_charge);
            formData.append('settlement_price', this.product.settlement_price);
            formData.append('availability', this.product.availability);
            formData.append('pay_on_delivery', this.product.pay_on_delivery);
            formData.append('verified', this.product.verified);

            for( var i = 0; i < this.product.images.length; i++ ){
                let image = this.product.images[i];
                formData.append('images[' + i + ']', image);
            }

            for (let i = 0; i < this.variations; i++) {
                formData.append('stock'+i, document.getElementById('stock'+i).value);
                formData.append('description'+i, document.getElementById('description'+i).value);
            }

            var response = await axios.post('https://www.solushop.com.gh/portal/manager/product/add', formData, {
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

                this.product.vendor = '';
                this.product.name = '';
                this.product.description = '';
                this.product.features = '';
                this.product.category = '';
                this.product.tags = '';
                this.product.settlement_price = '';
                this.product.selling_price = '';
                this.product.discount = '';
                this.product.dd = '';
                this.product.settlement_price = '';
                this.product.availability = '';
                this.product.pay_on_delivery = '';
                this.product.verified = '';
                this.product.images = '';            
                this.$refs.images = '';  
            }

            this.loading = false;
        },

        handleFileUpload(){
            this.product.images = this.$refs.images.files;
        },

        addVariation(){
            var string = "<div class='row'><div class='col-md-8'><div class='form-group'><input class='form-control round' id='description"+this.variations+"' name='description"+this.variations+"' value='None' type='text'></div></div><div class='col-md-4'><div class='form-group'><input class='form-control round' id='stock"+this.variations+"' name='stock"+this.variations+"' value='1' type='number' ></div></div></div>";
                
            $('#variations').append(string);
            this.variations++;
        }
    }
}
</script>