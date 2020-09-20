<template>
    <div>
        <div class="content-wrapper">
            <div class="content-body">
                <section id="configuration">
                    <div class="row">
                        <div class="col-md-12">
                            <h5 class="card-title">Real Time Logistics Tracking</h5>
                            <div class="card" style="min-height: 615px;">
                                <transition name="fade" mode="out-in">
                                    <div  v-if="loading"  class="div-preloader-wrap">
                                        <div class=" custom-center">
                                            <img  style="" src="https://www.solushop.com.gh/app/assets/img/loader.gif" alt="" > 
                                        </div>
                                    </div>
                                </transition>
                                <div class="card-content collapse show">
                                    <div class="card-body card-dashboard">
                                        <table ref="items" id="activity-records" class="table table-striped table-bordered zero-configuration">
                                            <thead>
                                                <tr>
                                                    <th>Order ID</th>
                                                    <th>Image</th>
                                                    <th>SKU</th>
                                                    <th>Description</th>
                                                    <th>Quantity</th>
                                                    <th>Vendor</th>
                                                    <th>State</th>
                                                    <th>Last Updated</th>
                                                    <th style="min-width: 100px;">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody> 
                                                <tr v-for="item in items" :key="item.id"> 
                                                    <td>{{ item.oi_order_id }}</td>
                                                    <td>
                                                        <ul class="list-unstyled users-list m-0">
                                                            <li data-toggle="tooltip" data-popup="tooltip-custom" :data-original-title="item.oi_name" class="avatar avatar-sm pull-up">
                                                                <img class="media-object rounded-circle no-border-top-radius no-border-bottom-radius"
                                                                :src="'https://www.solushop.com.gh/app/assets/img/products/thumbnails/'+item.sku.product.images[0].pi_path+'.jpg'"
                                                                :alt="item.oi_name">
                                                            </li>
                                                        </ul>
                                                    </td>
                                                    <td>{{ item.oi_sku }}</td>
                                                    <td>{{ item.oi_name }}</td>
                                                    <td>{{ item.oi_quantity }}</td>
                                                    <td>{{ item.sku.product.vendor.name }}</td>
                                                    <td v-html="item.order_item_state.ois_html"></td>
                                                    <td>{{ item.updated_at }}</td>
                                                    <td>
                                                        <a :href="'https://www.solushop.com.gh/portal/manager/product/'+item.sku.product.id">
                                                            <button data-toggle="tooltip" data-popup="tooltip-custom" :data-original-title="'View' + item.oi_name"  style="margin-top: 3px;" class="btn btn-info btn-sm round">
                                                                <i class="ft-eye"></i>
                                                            </button>
                                                        </a>
                                                        <button v-if="item.oi_state == 2" @click="mark('Picked Up', item.id)" data-toggle="tooltip" data-popup="tooltip-custom" :data-original-title="'Mark' + item.oi_name + ' as picked up'" style="margin-top: 3px;" class="btn btn-success btn-sm round">
                                                            <i class="ft-check"></i>
                                                        </button>
                                                        <button v-if="item.oi_state == 3" @click="mark('Delivered', item.id)" data-toggle="tooltip" data-popup="tooltip-custom" :data-original-title="'Mark' + item.oi_name + ' as delivered'" style="margin-top: 3px;" class="btn btn-success btn-sm round">
                                                            <i class="ft-check"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
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
            items: null,
            loading: true,
            count: {
                pickups: {
                    old: 0,
                    new: 0
                },

                deliveries: {
                    old: null,
                    new: 0
                }  
            },
            dt: null,
            base_url: window.location.origin,
        }
    },

    async mounted(){
        this.dt = $(this.$refs.items).DataTable();
        await this.updateRecords(this.filter);
        this.loading = false;
        setInterval(this.updateCounts, 10000);
    },


    methods: {
        setCount(count){
            if (this.count.deliveries.old == null) {
                this.count.deliveries.old = count.deliveries;
                this.count.pickups.old = count.pickups;
            }
            this.count.deliveries.new = count.deliveries;
            this.count.pickups.new = count.pickups;
        },

        async updateCounts(){
            var response = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/logistics/active/count',
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
            if (this.count.deliveries.old != this.count.deliveries.new || this.count.pickups.old != this.count.pickups.new) {
                this.loading = true;

                //update old counts
                this.count.deliveries.old = this.count.deliveries.new;
                this.count.pickups.old = this.count.pickups.new;

                await this.updateRecords();
                this.loading = false;
            }
        },

        async updateRecords(){
            var response = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/logistics/active/records',
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
            this.items = response.data.records;
            this.setCount(response.data.count);
        },

        async mark(action, id){
            this.loading = true;
            var response = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/logistics/active/action',
                                data: {
                                    action: action,
                                    id: id
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
                this.loading.products = false;

                //show error message(s)
                for (let i = 0; i < response.data.message.length ; i++) {
                    this.$toast.error(response.data.message[i], "", {
                        timeout: 5000,
                    });
                }
            } else {
                //update old counts
                this.count.deliveries.old  = response.data.count.deliveries;
                this.count.pickups.old  = response.data.count.pickups;


                this.items = response.data.records;
                this.loading = false;

                //show success message
                this.$toast.success(response.data.message, "", {
                    timeout: 5000,
                });
            }
        }
    },
    
    watch: {
        items(val) {
            this.dt.destroy();
            this.$nextTick(() => {
                this.dt = $(this.$refs.items).DataTable({
                    "order": [
                        [7, 'asc']
                    ],
                    "drawCallback": function() {
                        $('[data-toggle="tooltip"]').tooltip();
                    },
                });
                $('[data-toggle="tooltip"]').tooltip();
            });
        }
    }
}
</script>