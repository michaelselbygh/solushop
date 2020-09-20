<template>
    <div>
        <div class="content-wrapper">
            <div class="content-body">
                <section id="configuration">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-8" style="margin-top: 5px;">
                                    <h5 class="card-title">Showing {{ filter }} History</h5>
                                </div>
                                <div class="col-md-4" style="text-align: right;">
                                    <button v-if="filter == 'Delivery'" @click="changeFilter('Pickup')" style="margin-bottom: 4px;" class="btn btn-info btn-sm round">
                                        <i class="ft-switch"></i> Show Pickup History
                                    </button>
                                    <button v-if="filter == 'Pickup'" @click="changeFilter('Delivery')" style="margin-bottom: 4px;" class="btn btn-info btn-sm round">
                                        <i class="ft-switch"></i> Show Delivery History
                                    </button>
                                </div>
                            </div>
                            
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
                                                    <th>Order Item ID</th>
                                                    <th>Product SKU</th>
                                                    <th>Product Description</th>
                                                    <th>Quantity</th>
                                                    <th>Recorded At</th>
                                                    <th>Recorded By</th>
                                                    <th>Recorder ID</th>
                                                </tr>
                                            </thead>
                                            <tbody> 
                                                <tr v-for="item in items" :key="item.id"> 
                                                    <td>{{ item.order_item.oi_order_id }}</td>
                                                    <td>{{ item.order_item.id }}</td>
                                                    <td>{{ item.order_item.oi_sku }}</td>
                                                    <td>{{ item.order_item.oi_name }}</td>
                                                    <td>{{ item.order_item.oi_quantity }}</td>
                                                    <td>{{ item.created_at }}</td>
                                                    <td>
                                                        <span v-if="filter == 'Pickup'">
                                                            {{ item.pui_marked_by_description }}
                                                        </span>
                                                        <span v-if="filter == 'Delivery'">
                                                            {{ item.di_marked_by_description }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span v-if="filter == 'Pickup'">
                                                            {{ item.pui_marked_by_id }}
                                                        </span>
                                                        <span v-if="filter == 'Delivery'">
                                                            {{ item.di_marked_by_id }}
                                                        </span>
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
            filter: "Delivery",
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
            this.count.deliveries.old = count.deliveries;
            this.count.pickups.new = count.pickups;
        },

        async updateCounts(){
            var response = await axios({
                                method: 'post',
                                url: 'https://www.solushop.com.gh/portal/manager/logistics/history/count',
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
                                url: 'https://www.solushop.com.gh/portal/manager/logistics/history/records',
                                data: {
                                    filter: this.filter
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
            this.items = response.data.records;
            this.setCount(response.data.count);
        },

        async changeFilter(filter){
            this.loading = true;
            this.filter = filter;
            await this.updateRecords();
            this.loading = false;
        }

    },
    
    watch: {
        items(val) {
            this.dt.destroy();
            this.$nextTick(() => {
                this.dt = $(this.$refs.items).DataTable({
                    "order": [
                        [0, 'desc']
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