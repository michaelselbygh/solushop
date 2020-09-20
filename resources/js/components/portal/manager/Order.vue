<template>
    <div>
        <div class="content-wrapper">
            <div class="content-body">
                <section id="configuration">
                    <transition name="fade" mode="out-in">
                        <div  v-if="loading"  class="preloader-wrap">
                            <div class=" custom-center">
                                <img  style="" src="https://www.solushop.com.gh/app/assets/img/loader.gif" alt="" > 
                            </div>
                        </div>
                    </transition>
                    <div v-if="order" class="row">
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-8" style="margin-top : 10px;">
                                    <h5 class="card-title">Order Summary - <b> {{ this.id }}</b></h5>
                                </div>
                                <div class="col-md-4" style="text-align: right; margin-bottom:5px;">
                                    <button @click="action('confirm_order_payment')" v-if="[1].includes(order.order_state.id) && order.payment_type == 0" data-toggle="tooltip" data-popup="tooltip-custom" title="Confirm Payment Received"  style="margin-top: 3px;" class="btn btn-success btn-sm round">
                                        <i class="ft-check"></i>
                                    </button>
                                    <button @click="action('confirm_order')" v-if="[2].includes(order.order_state.id) || ( [1].includes(order.order_state.id) && order.payment_type == 1)" data-toggle="tooltip" data-popup="tooltip-custom" data-original-title="Confirm Order"  style="margin-top: 3px;" class="btn btn-info btn-sm round">
                                        <i class="ft-check"></i>
                                    </button>
                                    <button @click="action('cancel_order_partial_refund')" v-if="[2, 3, 4, 5].includes(order.order_state.id) && order.payment_type == 0" data-toggle="tooltip" data-popup="tooltip-custom" data-original-title="Cancel Order ( Order Items Refund Only )"  style="margin-top: 3px;" class="btn btn-warning btn-sm round">
                                        <i class="ft-x"></i>
                                    </button>
                                    <button @click="action('cancel_order_full_refund')" v-if="[2, 3, 4, 5].includes(order.order_state.id) && order.payment_type == 0" data-toggle="tooltip" data-popup="tooltip-custom" data-original-title="Cancel Order ( Full Refund  )"  style="margin-top: 3px; " class="btn btn-danger btn-sm round">
                                        <i class="ft-x"></i>
                                    </button>
                                    <button @click="action('cancel_order_no_refund')" v-if="[1, 2, 3, 4, 5].includes(order.order_state.id)" data-toggle="tooltip" data-popup="tooltip-custom" data-original-title="Cancel Order ( No Refund )"  style="margin-top: 3px; background-color: black !important; border-color: black !important" class="btn btn-success btn-sm round">
                                        <i class="ft-x"></i>
                                    </button>
                                </div>
                            </div>

                            
                            <div class="card">
                                <div class="card-content collapse show">
                                    <div class="card-body card-dashboard">
                                        <table class="table table-hover table-xl mb-0">
                                            <thead>
                                                <tr>
                                                    <th class="border-top-0">SKU</th>
                                                    <th class="border-top-0">Preview</th>
                                                    <th class="border-top-0">Price</th>
                                                    <th class="border-top-0">Quantity</th>
                                                    <th class="border-top-0">State</th>
                                                    <th class="border-top-0">Last Updated</th>
                                                    <th class="border-top-0">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody> 
                                                <tr v-for="item in order.order_items" :key="item.id">
                                                    <td>{{ item.oi_sku }}</td>
                                                    <td>
                                                        <ul class="list-unstyled users-list m-0">
                                                            <li data-toggle="tooltip" data-popup="tooltip-custom" :data-original-title="item.oi_name" class="avatar avatar-sm pull-up">
                                                                <img class="media-object rounded-circle no-border-top-radius no-border-bottom-radius"
                                                                :src="'https://www.solushop.com.gh/app/assets/img/products/thumbnails/' + item.sku.product.images[0].pi_path + '.jpg'"
                                                                :alt="item.oi_sku">
                                                            </li>
                                                        </ul>
                                                    </td>
                                                    <td>
                                                        {{ item.oi_selling_price - item.oi_discount }}
                                                    </td>
                                                    <td>
                                                        x {{ item.oi_quantity }}
                                                    </td>
                                                    <td>
                                                        <span v-html="item.order_item_state.ois_html"></span>
                                                    </td>
                                                    <td>
                                                        {{ item.updated_at }}
                                                    </td>
                                                    <td>
                                                        <a target="new" :href="'https://www.solushop.com.gh/portal/manager/product/' + item.sku.product.id">
                                                            <button data-toggle="tooltip" data-popup="tooltip-custom" :data-original-title="'View' + item.oi_name"  style="margin-top: 3px;" class="btn btn-info btn-sm round">
                                                                <i class="ft-eye"></i>
                                                            </button>
                                                        </a>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="card-footer" style="border-radius: 30px; text-align: right; border-top: none; padding-right:60px;">                
                                        <div class="row">
                                            <div class="col-md-10" style="text-align: right">
                                                <b>
                                                    <span style="font-size: 13px;">Sub-Total</span><br>
                                                    <span  v-if="order.order_scoupon && order.order_scoupon != null && order.order_scoupon != 'NULL'" style="font-size: 13px;">Discount from S-Coupon<br></span>
                                                    <span style="font-size: 13px;">Shipping</span><br>
                                                    <span style="font-size: 13px;">
                                                        Total
                                                        <span v-if="order.order_state == 1">Due</span>
                                                        <span v-else>Paid</span>
                                                    </span><br>
                                                </b>
                                            </div>
                                            <div class="col-md-2" style="text-align: right">
                                                <b>
                                                    <div id="subTotal" style="font-size: 13px;">
                                                            {{ order.order_subtotal.toFixed(2) }}
                                                    </div>
                                                    <div v-if="order.order_scoupon && order.order_scoupon != null && order.order_scoupon != 'NULL'" id="subTotal" style="font-size: 13px;">
                                                        {{ (0.01 * order.order_subtotal).toFixed(2) }}
                                                    </div>
                                                    <div id="shipping" style="font-size: 13px;">
                                                        {{ order.order_shipping.toFixed(2) }}
                                                    </div>
                                                    <div id="total" style="font-size: 13px;">
                                                        <span v-if="order.order_scoupon && order.order_scoupon != null && order.order_scoupon != 'NULL'">
                                                            {{ ((0.99 * order.order_subtotal) + order.order_shipping).toFixed(2) }}
                                                        </span>
                                                        <span v-else>{{ (order.order_subtotal + order.order_shipping).toFixed(2) }}</span>
                                                    </div>
                                                </b>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="order.profit_or_loss">
                                <h5 class="card-title" v-if="order.profit_or_loss < 0">Loss on Order 
                                    : <b style="color:red"> GH¢ {{ order.profit_or_loss.toFixed(2) }}</b>
                                    <span v-if="order.dp_shipping == 0"> 
                                        ( Shipping charge on company not included. )
                                    </span>
                                </h5>
                                <h5 class="card-title" v-else>Profit on Order 
                                    : <b style="color:green"> GH¢ {{ order.profit_or_loss.toFixed(2) }}</b>
                                    <span v-if="order.dp_shipping == 0"> 
                                        ( Shipping charge on company not included. )
                                    </span>
                                </h5>

                                <div class="card">
                                    <div class="card-content collapse show">
                                        <div class="card-body card-dashboard">
                                            <table class="table table-hover table-xl mb-0">
                                                <thead>
                                                    <tr>
                                                        <th class="border-top-0">Description</th>
                                                        <th class="border-top-0">Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody> 
                                                    <tr v-for="(pnl_item, index) in order.profit_or_loss_item.description" :key="pnl_item.description">
                                                        <td>{{ pnl_item }}</td>
                                                        <td>
                                                            <span v-if="order.profit_or_loss_item.amount[index] < 0" style="color:red"> GH¢ {{ order.profit_or_loss_item.amount[index] }}</span>
                                                            <span v-else style="color:green"> GH¢ {{ order.profit_or_loss_item.amount[index] }}</span>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h5 class="card-title">Order Details</h5>
                            <div class="card">
                                <div class="card-content collapse show">
                                    <div class="card-body card-dashboard">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <p style="font-weight:500;">Customer : </p>
                                            </div>
                                            <div class="col-md-8" >
                                                <p>{{ order.customer.first_name + " " + order.customer.last_name }}</p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <p style="font-weight:500;">Phone : </p>
                                            </div>
                                            <div class="col-md-8" >
                                                <p>{{ "0" + order.customer.phone.substr(3) }}</p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <p style="font-weight:500;">Address : </p>
                                            </div>
                                            <div class="col-md-8" >
                                                <p v-if="order.address">
                                                    {{ order.address.ca_town + " - " + order.address.ca_address }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <p style="font-weight:500;">Made At : </p>
                                            </div>
                                            <div class="col-md-8" >
                                                <p>{{ order.order_date }}</p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <p style="font-weight:500;">Payment : </p>
                                            </div>
                                            <div class="col-md-8" >
                                                <p v-if="order.payment_type == 0"> Before Delivery </p>
                                                <p v-else>On Delivery</p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <p style="font-weight:500;">State : </p>
                                            </div>
                                            <div class="col-md-8" >
                                                <span v-html="order.order_state.os_user_html"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="order.order_scoupon && order.order_scoupon != null && order.order_scoupon != 'NULL'">
                                <h5 class="card-title">Sales Associate</h5>
                                <div class="card">
                                    <div class="card-content collapse show">
                                        <div class="card-body card-dashboard">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <p style="font-weight:500;">Name :  </p>
                                                </div>
                                                <div class="col-md-8" >
                                                    <p>{{ order.coupon.sales_associate.first_name + " " + order.coupon.sales_associate.last_name }}</p>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <p style="font-weight:500;">Badge : </p>
                                                </div>
                                                <div class="col-md-8" >
                                                    <p style="font-weight:500;">{{ order.coupon.sales_associate.badge_info.sab_description }}</p>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <p style="font-weight:500;">Commission : </p>
                                                </div>
                                                <div class="col-md-8" >
                                                    <p style="font-weight:500;">{{ 100 * order.coupon.sales_associate.badge_info.sab_commission }} % on Order</p>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <p style="font-weight:500;">Coupon : </p>
                                                </div>
                                                <div class="col-md-8" >
                                                    <p style="font-weight:500;">{{ order.coupon.coupon_code }}</p>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <p style="font-weight:500;">Phone : </p>
                                                </div>
                                                <div class="col-md-8" >
                                                    <p>{{ "0" + order.coupon.sales_associate.phone.substr(3) }}</p>
                                                </div>
                                                </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="order.order_state.id == 6 && (order.dp_shipping == 0 || order.dp_shipping == null) && order.allow_shipping_entry">
                                <h5 class="card-title">Delivery Partner and Charge</h5>
                                <div class="card">
                                    <div class="card-content collapse show">
                                        <div class="card-body card-dashboard">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="delivery_partner">Partner</label>
                                                        <select class="form-control" v-model="pay.partner" style='border-radius:7px;'>
                                                                <option v-for="partner in order.delivery_partner" :key="partner.id" :value="partner.id" selected="selected">
                                                                    {{ partner.first_name + ' ' + partner.last_name }}
                                                                </option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6" >
                                                    <div class="form-group">
                                                        <label for="shipping_amount">Charge</label>
                                                        <input v-model="pay.amount" value="0" class="form-control round" type="number" step="0.01" > 
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-actions" style="text-align:center; padding: 0px;">
                                                <button @click.prevent="partner_shipping" value="record_shipping" class="btn btn-success">
                                                        Record Charge
                                                </button>
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
    </div>
</template>
<script>
export default {
    props: ['id'],
    data(){
        return {
            order: null,
            loading: true,
            count: {
                order: {
                    old: null,
                    new: null
                },
                items: {
                    old: null,
                    new: null
                }
            },

            pay: {
                partner: 1,
                amount: 0,
            },
            base_url: window.location.origin,
        }
    },

    async mounted(){
        await this.updateRecords();
        document.title = "Order, " + this.order.id;
        this.loading = false;
        setInterval(this.updateCounts, 10000);
    },


    methods: {
        setCount(count){
            if (this.count.order.old == null) {
                this.count.order.old = count.order;
                this.count.items.old = count.items;
            }
            this.count.order.new = count.order;
            this.count.items.new = count.items;
        },

        async updateCounts(){
            var response = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/order/count',
                                data : {
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

            this.setCount(response.data.count);
            if (this.count.order.old != this.count.order.new || JSON.stringify(this.count.items.old) != JSON.stringify(this.count.items.old)) {
                this.loading = true;

                //update old counts
                this.count.order.old = this.count.order.new;
                this.count.items.old = this.count.items.new;

                await this.updateRecords();
                this.loading = false;
            }
        },

        async updateRecords(){
            var response = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/order/records',
                                data : {
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

            this.order = response.data.order;
            this.setCount(response.data.count);
        },

        async action(action){
            this.loading = true;
            var response = await axios({
                method: 'post',
                url: 'https://www.solushop.com.gh/portal/manager/order/action',
                data : {
                    id : this.id,
                    action: action
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
            }

            this.order = response.data.order;
            this.count.order.old = response.data.count.order;
            this.count.items.old = response.data.count.items;

            this.loading = false;
        },

        async partner_shipping(){
            this.loading = true;
            var response = await axios({
                method: 'post',
                url: 'https://www.solushop.com.gh/portal/manager/order/shipping',
                data : {
                    id : this.id,
                    partner: this.pay.partner,
                    amount: this.pay.amount,
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
            }

            this.order = response.data.order;
            this.count.order.old = response.data.count.order;
            this.count.items.old = response.data.count.items;

            this.loading = false;
        }
    },

    watch: {
        order(val) {
            this.$nextTick(() => {
                $('[data-toggle="tooltip"]').tooltip();
            });
        }
    }
    
}
</script>